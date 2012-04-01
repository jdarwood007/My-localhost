<?php
$syntax = new scSyntax();

scSite::_()->templateHeader('PHP Recusrive Syntax Checker');

$syntax->main();

// Only do this if we have output.
if (isset($_REQUEST['dir']))
{
	// End buffering, this is may be a big directory!
	while (ob_get_level())
		ob_end_flush();

	if (substr($_REQUEST['dir'], 0, 1) != '/')
		$dir = $_SERVER['DOCUMENT_ROOT'] . '/' . $_REQUEST['dir'];
	else
		$dir = $_REQUEST['dir'];

	$syntax->scanDir($dir, isset($_REQUEST['all']));
}

scSite::_()->templateFooter();
exit;

/*
 * Checks the PHP syntax of files massivly.
*/
class scSyntax
{
	/* The php cli binary, It needs -l to do the actual check. */
	private $php_bin = '/srv/software/php/v5/bin/php.dSYM';

	private $debug = false;

	/*
	 * The main output with all our options.
	 * @return void No return, however output is executed at this point.
	*/
	public function main()
	{
		global $txt;
			echo '
		<form method="post"class="form-horizontal well">
		<fieldset>
				<div class="control-group">
					<label>Directory Path</label>
					<div class="controls"><input type="input" name="dir" value="', dirname(__FILE__), '" size="100"/></div>
				</div>

				<div class="control-group">
					<label>Options</label>
					<div class="controls">
						<label><input type="checkbox" name="all" value="1" /> Show all files and Directories?<label>
					</div>
				</div>

				<div class="control-group">
					<input type="submit" name="i" value="Go Scan" class="btn btn-primary btn-large" />
				</div>
			</fieldset>
		</form>';
	}

	/*
	 * Scans the directory, outputs the results.
	 * @param $dir string The directory to scan.
	 * @return void No return, however output is executed at this point.
	*/
	public function scanDir($dir, $all)
	{
		$results = $this->recursive_scandir($dir);

		echo '
			<div class="well">
				<legend>Results of scan of: ', $dir, '</legend>
				<ol>';

		$this->print_recursive_results($results);

		echo '
				</ol>
			</div>';
	}

	/*
	 * Does the actual directory results.
	 * @Note This is not the cleanest or best way to do this. Should rewrite.
	 * @param $results string The results of the scan.
	 * @return void No return, however output is executed at this point.
	*/
	public function print_recursive_results($result)
	{
		global $all_files;

		foreach ($result as $full_file => $item)
		{
			if (is_array($item))
			{
				if ($this->debug)
					echo '
			<li>Directory: ' . $full_file . '</li>';

				$this->print_recursive_results($item);
			}
			elseif (empty($item))
				continue;
			elseif (strpos(trim($item), ':Errors parsing') != false || !empty($_REQUEST['all']))
			{
				$sent_data = true;

				echo '
		<li>', $item, implode('<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $all_files[$full_file]), '</li>';
			}
		}
	}

	/*
	 * Scans a directory, finds all php files and does syntax checks on them.
	 * @Note This is not the cleanest or best way to do this. Should rewrite.
	 * @param $dir string The directory to scan.
	 * @return array The results are returned.
	*/
	public function recursive_scandir($dir)
	{
		global $all_files;
		$files = scandir($dir);
		unset($files[0], $files[1]);
		foreach ($files as $file)
		{
			unset($status);

			if ($file == '.svn' || $file == '.git')
				continue;

			if (is_dir($dir . '/' . $file))
			{
				$all_files[$dir . '/' . $file] = 'directory';
				$return[$dir . '/' . $file] = $this->recursive_scandir($dir . '/' . $file);
				continue;
			}
			else
			{
				if (substr($file, -4) != '.php')
					continue;
				if (!file_exists($dir . '/' . $file))
					continue;

				$var = exec($this->php_bin . ' -l ' . $dir . '/' . $file, $status);
				if (strpos(trim(str_replace(array($dir, $file, '/'), '', $var)), 'Errors parsing') != false)
					$var .= implode(', ', $status);

				$all_files[$dir . '/' . $file] = $status;
				$return[$dir . '/' . $file] = $dir . '/' . $file . ' :' . str_replace(array($dir, $file, '/'), '', $var);
				continue;
			}
		}

		if (empty($return))
			$return[] = $dir . ': Directory';

		return $return;
	}
}