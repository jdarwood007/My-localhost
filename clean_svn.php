<?php

$cleanSVN = new scCleanSVN;

scSite::_()->templateHeader($cleanSVN->title);

if (isset($_REQUEST['action']))
{
	$time = microtime(true);

	// Just striping SVN directories?.
	if (isset($_REQUEST['action']['stripsvn'], $_POST['location']) && file_exists($_POST['location']))
	{
		$cleanSVN->stripSVN($_POST['location']);

		echo '
		<div class="alert alert-success">Directory has been purged of any svn files.</div><br />';
	}

	// Making a installer.
	if (isset($_REQUEST['action']['install']))
	{
		$cleanSVN->createInstall();

		echo '
		<div class="alert alert-success">Installer has been created.</div><br />';
	}

	// Making a updater.
	if (isset($_REQUEST['action']['update']))
	{
		$cleanSVN->createUpdate();

		echo '
		<div class="alert alert-success">Update has been created.</div><br />';
	}

	// Making a upgrader.
	if (isset($_REQUEST['action']['upgrade']))
	{
		$cleanSVN->createUpgrade();

		echo '
		<div class="alert alert-success">Upgrader has been created.</div><br />';
	}

	echo '
		<div class="alert alert-info">This script completed in ', (microtime(true) - $time), ' seconds.</div>';
}

$cleanSVN->MainMenu();

scSite::_()->templateFooter();
exit;

/*
 * Clean and do other stuff to a SVN checkout
*/
class scCleanSVN
{
	public $title = 'Simple Machines Forum SVN Checkout Cleanup Utility';

	private $disallowed_copy_files = array('.', '..', '.DS_Store', '.svn', '.cvsignore');
	private $svn_location = '/srv/repos/svn/sm-smf/trunk';
	private $new_location = '/srv/tmp/smf';
	private $chmod_old = '1021';
	private $chmod_new = '1020';
	private $chown = 'jeremy';

	// We add the .sql files in the constructor.
	private $install_files = array('install.php', 'Settings.php', 'Settings_bak.php');
	private $upgrade_files = array('upgrade.php');
	private $update_files = array('upgrade.php');

	// These files are not needed for update packages.
	private $update_junk_files = array('agreement.txt', 'attachments', 'avatars', 'cache', 'changelog.txt', 'Packages', 'Smileys', 'other');

	private $debug = false;

	/*
	 * We use glob to append all the needed sql files to the files arrays.
	*/
	public function __construct()
	{
		$this->install_files += array_map('basename', glob($this->svn_location . '/other/install_*.sql'));
		$this->update_files += array_map('basename', glob($this->svn_location . '/other/upgrade_*.sql'));
		$this->upgrade_files += array_map('basename', glob($this->svn_location . '/other/upgrade_*.sql'));
	}

	/*
	 * Removes any svn assoicated files from a directory, its children, its children children and so on.
	*/
	public function stripSVN($dir)
	{
		$this->$this->_removeSVNfiles($dir);
	}

	/*
	 * Creates a SMF installer package. However it is not compressed.
	 * This is not how the official SMF packages are created, but the directory and files should match.
	*/
	public function createInstall()
	{
		$temp = $this->new_location . '-install';

		// Remove any existing files
		if (file_exists($temp . '/index.php'))
			$this->_removeDirectory($temp);

		// Copy over the files.
		$this->_copyDirectory($this->svn_location, $temp);

		// Remove the uneeded files.
		$this->_remove_junk($temp, array_merge($this->upgrade_files, $this->update_files));

		// Update our installer files.
		$this->_MoveRequired($temp, $this->install_files);
		$this->_fixVersion($temp, $this->svn_location);

		// Remove the other stuff.
		$this->_removeDirectory($temp . '/other');
	}

	/*
	 * Creates a SMF update package. However it is not compressed.
	 * This is not how the official SMF packages are created, but the directory and files should match.
	*/
	public function createUpdate()
	{
		$temp = $this->new_location . '-update';

		// Remove any existing files
		if (file_exists($temp . '/index.php'))
			$this->_removeDirectory($temp);

		// Copy over the files.
		$this->_copyDirectory($this->svn_location, $temp);

		// Remove the uneeded files.
		$this->_remove_junk($temp, $this->install_files);

		// Update our installer files.
		$this->_MoveRequired($temp, $this->update_files);
		$this->_fixVersion($temp, $this->svn_location);

		// Remove some junk our updater doesn't need.
		$this->_removeDirectory($temp . '/other');
		$this->_remove_junk($temp, $this->update_junk_files);	
	}

	/*
	 * Creates a SMF upgrade package. However it is not compressed.
	 * This is not how the official SMF packages are created, but the directory and files should match.
	*/
	public function createUpgrade()
	{
		$temp = $this->new_location . '-update';

		// Remove any existing files
		if (file_exists($temp . '/index.php'))
			$this->_removeDirectory($temp);

		// Copy over the files.
		$this->_copyDirectory($this->svn_location, $temp);

		// Remove the uneeded files.
		$this->_remove_junk($temp, $this->install_files);

		// Update our installer files.
		$this->_MoveRequired($temp, $this->update_files);
		$this->_fixVersion($temp, $this->svn_location);

		// Remove the other stuff.
		$this->_removeDirectory($temp . '/other');
	}

	/*
	 * The main menu of what to do.
	 * @return void No return, however output is executed at this point.
	*/
	public function MainMenu()
	{
		$title = isset($this->title) ? $this->title : (isset($GLOBALS['txt']['header']) ? $GLOBALS['txt']['header'] : 'Simple Machines');

			echo '
			<form method="post" class="form-horizontal well">
				<fieldset>
					<legend>', $title, '</legend>

					<div class="control-group">
						<label>Clean SVN</label>
						<div class="controls">
							<label><input type="checkbox" name="action[stripsvn]" value="true"/> Strip .svn from a specific directory?</label>
							<input type="input" name="location" value="', dirname(__FILE__), '" size="100"/>
						</div>
					</div>

					<div class="control-group">
						<label>Packages</label>
						<div class="controls">
							<label><input type="checkbox" name="action[install]" value="true" checked="checked" /> Create Installer?</label>
							<label><input type="checkbox" name="action[update]" value="true" checked="checked" /> Create Small Update?</label>
							<label><input type="checkbox" name="action[upgrade]" value="true" /> Create Large Upgrade?</label>
						</div>
					</div>

					<div class="control-options"><input type="submit" name="i" value="Do these actions" class="btn btn-primary btn-large" /></div>
				</fieldset>
			</form>';

	}

	/*
	 * Removes some of the junk stuff we don't need. This will transverse directories as needed.
	*/
	private function _remove_junk($dir, $files)
	{
		foreach($files as $file)
		{
			if (is_dir($dir . '/' . $file))
			{
				$this->_removeDirectory($dir . '/' . $file);

				if (file_exists($dir . '/' . $file))
					rmdir($dir . '/' . $file);
			}
			elseif (file_exists($dir . '/' . $file))
				unlink($dir . '/' . $file);
		}
	}

	/*
	 * The actual function that strips all svn files from directories. This will transverse directories.
	*/
	private function _removeSVNfiles($svndir)
	{
		$num = 0;

		// Open the directory.
		if ($curdir = opendir($svndir))
		{
			// Loopy loop de-loop.
			while ($file = readdir($curdir))
			{
				// No return directories or stupid DS_Store files.
				if ($file == '.svn')
					$this->_removeDirectory($svndir . '/.svn');
				elseif (is_dir($svndir . '/' . $file) && $file != '.' && $file != '..')
					$this->_removeSVNfiles($svndir . '/' . $file);
			}
			closedir($curdir);
		}
		return $num;
	}

	/*
	 * Uses the local SVN checkout to find the revision number and changes the SMF version in index.php to include the revision number.
	 * Also this fixes adds db_show_debug to be true for administrators.
	*/
	private function _fixVersion($dir, $svn)
	{
		// Find the revision.
		$opened_file = fopen($svn . '/.svn/entries', "r");
		$junk = fread($opened_file, 13);
		fclose($opened_file);
		preg_match("~dir[0-9]*~", str_replace(array("\r", "\n", "\r\n"), '', $junk), $match);
		if (empty($match[0]))
			$revision = 'DEV ' . date('Y-m-d_H-i', time()) . ' PST';
		else
			$revision = 'REV ' . trim(str_replace('dir', '', $match[0]));

		$index_changes = array();
		// !!! TODO Still uses eregei.. I'm lazy.
		$index_changes["// Get everything started up..."] = "// We are going to override this since it is a SVN version.\rif (!eregi('action,admin', \$_SERVER['REQUEST_URI']) && !eregi('action=admin', \$_SERVER['REQUEST_URI']))\r\t\$forum_version = 'SMF " . $revision . "';\r\r// Get everything started up.";
		$index_changes["// Load the current board's information."] = "// Admins should get goodies.\r\tif (\$user_info['is_admin'])\r\t\t\$GLOBALS['db_show_debug'] = TRUE;\r\r\t// Load the current board's information...";
			
		$opened_index_file = file_get_contents($dir . '/index.php');
		file_put_contents($dir . '/index.php', strtr($opened_index_file, $index_changes));
	}

	/*
	 * Relocates the installer/upgrade files to the directory root.
	 * @return bool True on success, false otherwise.
	*/
	private function _MoveRequired($dir, $files)
	{
		$failure = false;

		foreach ($files as $file)
			if (copy($dir . '/other/' . $file, $dir . '/' . $file))
			{
				if ($this->debug)
					debug_print_backtrace();

				$failure = true;
			}

		// You are a failure.
		if ($failure)
			return false;

		return true;
	}

	/*
	 * Deletes a directory.
	 * @param $dir String the full path to the directory we are removing.
	 * @param $leave_root bool Whether we should delete the directory are in or not. Default is to delete it.
	 * @return bool True on success, false otherwise.
	*/
	private function _removeDirectory($dir, $leave_root = false)
	{
		$success = true;

		// This simply put, opens the directory, loops through it to find anything that isn't . or .. and removes it.
		$handle = opendir($dir);
		while (($item = readdir($handle)) !== false)
		{
			if ($item != '.' && $item != '..')
			{
				if (is_dir($dir . '/' . $item))
					$this->_removeDirectory($dir . '/' . $item, true);
				else
				{
					unlink($dir . '/' . $item);

					if (file_exists($dir . '/' . $item) && $this->debug)
						echo '<br />Unable to delete file: ' . $dir . '/' . $item;
					if (file_exists($dir . '/' . $item))
						$success = false;	
				}
			}
		}
		closedir($handle);

		// Now we attempt to remove the directory. 
		if (empty($leave_root))
		{
			rmdir($dir);

			if ($this->debug && file_exists($dir))
				echo '<br />Unable to delete folder: ' . $dir;
			if (file_exists($dir))
				$success = false;
		}

		// How should we return.
		return $success;
	}

	/*
	 * Copy a directory from one place to another.
	 * @Note This is a recusrsive function.
	 * @param $srcdir String Where the sauce is.
	 * @param $dstdir String Where it should go.
	 * @return $num int The number of files/folders copied. Counts transversing as well.
	*/
	private function _copyDirectory($srcdir, $dstdir)
	{
		$num = 0;

		// If it doesn't exist. create it.
		if (!is_dir($dstdir))
		{
			mkdir($dstdir, $this->chmod_new);
			chown($dstdir, $this->chown);
			chmod($dstdir, $this->chmod_new);
		}

		// Open the directory.
		if ($curdir = opendir($srcdir))
		{
			if ($this->debug > 1)
				echo '<br />Reading ' . $srcdir;

			// Loopy loop de-loop.
			while ($file = readdir($curdir))
			{
				// No return directories or stupid DS_Store files.
				if (!in_array($file, $this->disallowed_copy_files))
				{
					if ($this->debug > 1)
						echo '<br />File, ', $srcdir, '/', $file, ', is ok to copy.';

					// Set the path temporarily.
					$srcfile = $srcdir . '/' . $file;
					$dstfile = $dstdir . '/' . $file;

					// Its a file!
					if (is_file($srcfile))
					{
						// is the destination a file? Check to see if we should update it or not.
						if (is_file($dstfile))
						{
							$ow = filemtime($srcfile) - filemtime($dstfile);
							chmod($dstdir, $this->chmod_new);
							chown($dstdir, $this->chown);
						}
						else
							$ow = 1;

						// If we need to update it. Lets do that.
						if ($ow > 0)
						{
							chown($srcfile, $this->chown);
							chmod($srcfile, $this->chmod_old);

							if ($this->debug > 1)
								echo '<br />Copying ', $srcfile, ' to ', $dstfile;

							// Apple+C (Ctrl+C).
							if (copy($srcfile, $dstfile))
							{
								touch($dstfile, filemtime($srcfile));
								chown($dstdir, $this->chown);
								chmod($dstdir, $this->chmod_new);
								++$num;

								if ($this->debug > 1)
									echo '<br />Ok';
							}
							elseif ($this->debug)
								echo '<br />Error copying ', $srcfile, ' to ', $dstfile;
						}                 
					}
					// Are you recursive.
					elseif (is_dir($srcfile))
						$num += $this->_copyDirectory($srcfile, $dstfile, $verbose);
				}
				elseif ($this->debug > 2)
					echo '<br />File, ', $srcdir, '/', $file, ', is in the disallowed files array.';
			}
			closedir($curdir);
		}
		return $num;
	}

	/*
	 * change the permissions on a directory and all its file, will transverse if needed.
	 * @Note This is a recusrsive function.
	 * @Note Ignores the disallowed files at this point.
	 * @param $srcdir String The sauce directory.
	 * @return $num int The number of files/folders copied. Counts transversing as well.
	*/
	private function _chmodDirectory($srcdir)
	{
		$num = 0;

		// If its not a directory we chmod it.
		if (!is_dir($srcdir))
		{
			chmod($srcdir, $this->chmod_new);
			chown($srcdir, $this->chown);
		}
		if ($curdir = opendir($srcdir))
		{
			while ($file = readdir($curdir))
			{
				if ($file != '.' && $file != '..')
				{
					$srcfile = $srcdir . '/' . $file;
					if (is_file($srcfile))
					{
						++$num;
						chmod($srcdir, $this->chmod_new);
						chown($srcdir, $this->chown);
					}
					elseif (is_dir($srcfile))
						$num += $this->_chmodDirectory($srcfile);
					elseif ($this->debug)
						echo '<br />Unknown file ', $srcfile;
				}
			}
			closedir($curdir);
		}
		return $num;
	}
}
