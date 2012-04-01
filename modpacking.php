<?php
// This isn't tested, but I have it just incase.
if (php_sapi_name() == 'cli')
{
	$mods = new scMods(true);
	$mods->handle_cli();
	exit;
}

$mods = new scMods();
scSite::_()->templateHeader('Customization Packaging');

// Package them?
if (isset($_POST['package']))
	$mods->doPacking();

$mods->listMods();
scSite::_()->templateFooter();

/*
 * Handling Modifications
*/
class scMods
{
	/* Our mods directory. */
	private $dir = '/srv/smf.test/Mods/2_Released';
	private $package_dir = '/srv/tmp';

	/* Files we will not package. */
	private $disallowed_files = array('.', '..', '.DS_Store', '.svn', '.git', 'error_log');

	/* The location of the tar binary. */
	private $tar_bin = '/srv/software/gnutar/bin/tar';

	/*
	 * Adds the javascript to our main template.
	 * @param $is_cli bool If this is cli we want to ignore template stuff.
	*/
	public function __construct($is_cli = false)
	{
		if ($is_cli)
			return;

		// Add our javascript to the main template call.
		scSite::_()->addHeader('
		<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
			$(document).ready(function(){
				$("#toggleChecks").click(function()
				{
					if ($(this).is(":checked"))
					{
						$(\'.scMods\').attr("checked", "checked");
					}
					else
					{
						$(\'.scMods\').removeAttr("checked");
				}
				});
			});
		</script>');
	}

	/*
	 * Lists all mods we can package.
	 * @return void No return, however output is executed at this point.
	*/
	public function listMods()
	{
		// Get the mods.
		$mods = scandir($this->dir);

		// Output.
		echo '
	<form method="post" action="?section=modpacking" class="form-horizontal well">
		<fieldset>
			<div class="control-group">
				<label><strong>Customizations</strong></label>
				<div class="controls">
					<label><input id="toggleChecks" class="input_check" type="checkbox"> Select All</label>
					<br />';

		$modOut = array();
		foreach ($mods as $mod)
		{
			if (in_array($mod, $this->disallowed_files))
				continue;

			$xmlData = simplexml_load_file($this->dir . '/' . $mod . '/package-info.xml');
			$modOut[strtolower($mod)] = $xmlData->name;
		}
		ksort($modOut);

		foreach ($modOut as $id => $name)
			echo '
					<label for="', $id, '"><input type="checkbox" class="scMods" name="mods[]" value="', $id, '" id="', $id, '" /> ', $name, '</label>';

		echo '
				</div>
			</div>
			<hr />
			<div class="control-group">
				<label><strong>Options</strong></label>
				<div class="controls">
					<label for="force" title="use the force"><input type="checkbox" name="force" id="force" /> Force repacking for existing mod versions?</label>
				</div>
			</div>

			<div class="control-group">
				<input type="submit" name="package" value="Package selected Modifications" class="btn btn-primary btn-large" />
			</div>
		</fieldset>
	</form>';
	}

	/*
	 * Packages our mods.
	 * @param $is_cli bool Whether this is from cli or not.
	*/
	function DoPacking($is_cli = false)
	{
		$force = isset($_REQUEST['force']) ? true : false;

		// This just finds what mods we want to package.
		$allowed_mods = array();
		if (isset($_REQUEST['mods']))
			foreach ($_REQUEST['mods'] as $in)
				$allowed_mods[] = trim($in);

		// Get em!
		$mods = scandir($dir);
		foreach ($mods as $mod)
		{
			global $temp_key;

			if (in_array($mod, $this->disallowed_files))
				continue;

			if (!empty($mods) && !in_array(strtolower($mod), $allowed_mods))
				continue;

			// Files in this folder.
			$files = scandir($dir . '/' . $mod);
			foreach ($files as $key => $file)
				if (in_array($file, array_merge($disallowed_files, array('images'))))
					unset($files[$key]);

			// Figure out our version, the first match is our keeper!
			preg_match('~version\s+([\d\.]+)(^\S+)?~i', file_get_contents($dir . '/' . $mod . '/Readme.txt'), $matches);

			// Nope, nope, nope!
			if (empty($matches[1]))
			{
				if ($is_cli)
					echo 'The ', $mod, ' mod can not find a valid version' . "\n";
				else
					echo '<div class="alert alert-error">The ', $mod, ' mod can not find a valid version</div>';

				continue;
			}
			elseif (file_exists($this->package_dir . '/' . $mod . '_v' . $matches[1] . '.tgz') && !$force)
			{
				if ($is_cli)
					echo 'The ', $mod, ' mod already has a ', $matches[1], ' version' . "\n";
				else
					echo '<div class="alert">The ', $mod, ' mod already has a ', $matches[1], ' version</div>';

				continue;
			}

			// Update all version information.
			foreach ($files as $file)
			{
				if (substr($file, -4) != '.xml')
					continue;

				$new_contents = preg_replace('~<version>([^<]+)</version>~i', '<version>' . $matches[1] . '</version>', file_get_contents($this->dir . '/' . $mod . '/' . $file));

				// Null is ugly!
				if (!is_null($new_contents) && !is_array($new_contents))
					file_put_contents($this->dir . '/' . $mod . '/' . $file, $new_contents);
			}

			$contents = preg_replace('~version\s+([\d\.]+)\s+\(Not\sReleased\sYet\)?~i', 'Version \1', file_get_contents($this->dir . '/' . $mod . '/Readme.txt'));
			file_put_contents($this->dir . '/' . $mod . '/Readme.txt', $contents);

			// Change our directory.
			chdir($dir . '/' . $mod);

			// Tar it!
			// ZIP: zip -0XT ../path_name.zip ./* -x .svn
			exec($tar_bin . ' -czf ' . $this->package_dir . '_v' . $matches[1] . '.tgz ' . implode(' ', $files));

			if ($is_cli)
				echo $mod, ' mod now has a version ', $matches[1], "\n";
			else
				echo '<div class="alert alert-success">', $mod, ' mod now has a version ', $matches[1], '</div>';
		}
	}

	/*
	 * Handles running this script from a shell.
	 * @return void No return, however output is executed at this point.
	*/
	public function handle_cli()
	{
		if (empty($_SERVER['argv'][1]))
		{
			echo "Usage : -- [force] modName [...]\nValid modNames:\n";

			foreach (scandir($this->dir) as $mod)
				if ($mod != '.' && $mod != '..' && $mod != '.DS_Store')
					echo "\t" . $mod;

			exit("\n");
		}

		if (in_array('force', $_SERVER['argv']))
			$_REQUEST['force'] = true;

		foreach ($_SERVER['argv'] as $in)
		{
			if (in_array($in, array(basename(__FILE__), '--', 'force')))
				continue;

			$_REQUEST['mods'][] = trim($in);
		}

		$this->doPacking(true);

		exit;
	}
}