<?php
// !!! This needs rewrote for git and could use some better coding.
error_reporting(E_ALL);
ob_start();

// Some setup.
global $current_action, $this_script, $settings;
date_default_timezone_set("America/Los_Angeles");
$current_action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
$this_script = $_SERVER['PHP_SELF'];

// Massive array of settings
$settings = array

	// What is the path to our site root that we can install into?
	'site_root' => '/srv/smf.test/public_html',

	// Where is trunk?
	'install_trunk_path' => '/srv/repos/svn/sm-smf/trunk',

	// Where is our branches?
	'install_branches_path' => '/srv/repos/svn/sm-smf/branches',

	// Tag this!
	'install_tags_path' => '/srv/repos/svn/sm-smf/tags',

	// Use Tests Directory?
	'use_tests' => TRUE,

	// URL to your site root.
	'site_url' => 'http://smf.test' . (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : ''),

	// Modifications setup.
	'mods' => array(
		// Use modifications?
		'use_mods' => TRUE,

		// Use a custom directory?
		'custom_dir' => TRUE,

		// Path to mods dir, last folder should acts as the Packages folder and contain compressed or folders for mods.
		'mods_dir' => '/srv/smf.test/Mods/2_Released',
	),

	// Permissions
	'permissions' => array(
		'chmod' => 0777,
		'do_chown' => TRUE,
		'chown' => 'jeremy',
	),

	// Edit the install so you can be lazy and click right through?
	'hack_install' => array(
		'use_hacked_install' => TRUE,
		'install_setup' => array(
					'user_pass_show' => TRUE,
					'allow_custom_forumname' => FALSE,
					'allow_utf8' => TRUE,
					'user_name' => 'SleePy',
					'user_pass' => 'test',
					'user_email' => 'sleepingkiller@gmail.com',
		),
	),

	// Database Stuff. Give them master access to do anything :P
	'database' => array(
		'none' => array(
			'func' => '@echo',
			'host' => 'localhost',
			'user' => 'SMF',
			'pass' => 'smf',
			),
		'mysql' => array(
			'func' => 'mysql_connect',
			'host' => 'localhost',
			'user' => 'SMF',
			'pass' => 'smf',
			),
		'postgresql' => array(
			'func' => 'pg_connect',
			'host' => 'localhost',
			'user' => 'SMF',
			'pass' => 'smf',
			'dorg' => 'test'
			),
	),
);

if (isset($_REQUEST['action']))
	scSite::_()->addHeader('<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
		// Updates the Directory based on a value.
		function UpdateDirectory()
		{
			// Find the Value.
			var drop_field = document.getElementById("install_type");
			var dir_field = document.getElementById("directory_location");
			var test_id = "00";

			$.ajax({
				type : "GET",
				url: "?scAction=new_install&action=dircheck&this_action=' . $_REQUEST['action'] . '"
				data: "id=" + drop_field.value,
				success: function (data){
					dir_field.value = data;
				}
			});
		}
	</script>');

// Get Text and Send output now.
smLanguage();
scSite::_()->templateHeader();
smMain();
scSite::_()->templateFooter();

function smMain()
{
	global $settings, $current_action;

	if (isset($current_action) && $current_action == 'dircheck')
	{
		ob_clean();

		if ($_REQUEST['this_action'] == 'svninstall' || $_REQUEST['id'] == 'trunk')
			exit('/dev/' . smFindSVNrevisionID($settings['install_trunk_path'], '/dev'));
		else
		{
			list($area, $version) = explode('|', $_REQUEST['id']);

			if ($area == 'branch' && file_exists($settings['install_branches_path'] . '/' . $version))
				$temp = '/branch-' . $version;
			elseif ($area == 'tag' && file_exists($settings['install_tags_path'] . '/' . $version))
				$temp = '/tag-' . $version;
			else
				$temp = '/unkown/' . $area . '/' . $version;

			$string = smFindDirectory(false, null, $temp);
			exit (strtolower($temp . '/' . $string));
		}
	}	
	elseif (isset($current_action) && in_array($current_action, array('uninstall', 'install', 'svninstall')))
	{
		// Find out what install we are using, otherwise default to 0.
		if (isset($_REQUEST['do']) && ((!empty($_REQUEST['version']) && $_REQUEST['version'] == 'trunk') || $_REQUEST['action'] == 'svninstall'))
			$file_location = $settings['install_trunk_path'];
		elseif (isset($_REQUEST['do']) && strpos($_REQUEST['dir'], '/') !== false)
		{
			if (substr($_REQUEST['dir'], 0, 1) == '/')
				$_REQUEST['dir'] = substr($_REQUEST['dir'], 1);
			list($section_area, $dir) = explode('/', $_REQUEST['dir']);
			list($area, $version) = explode('|', $_REQUEST['version']);

			if ($area == 'branch' && file_exists($settings['install_branches_path'] . '/' . $version))
				$file_location = $settings['install_branches_path'] . '/' . $version;
			elseif ($area == 'tag' && file_exists($settings['install_tags_path'] . '/' . $version))
				$file_location = $settings['install_tags_path'] . '/' . $version;
			else
				$file_location = $settings['install_trunk_path'];
		}

		// Now lets figure out if we are actually doing work.
		if (in_array($current_action, array('install', 'svninstall')) && isset($_REQUEST['do']) && !empty($_REQUEST['dir']))
			smDoInstall($_REQUEST['dir'], $file_location);
		elseif ($current_action == 'uninstall' && isset($_REQUEST['do']) && !empty($_REQUEST['dir']))
			smDoRemoval($_REQUEST['dir']);
		else
			smMenuAction($current_action == 'install' ? 'install' : ($current_action == 'svninstall' ? 'svninstall' : 'uninstall'));
	}
	else
		smMenuStart();

}

// The install menu.
function smMenuAction($action = 'install')
{
	global $settings, $current_action, $txt;

	// Simply for ease of use
	if ($action == 'uninstall')
		$usage = 0;
	else
		$usage = 1;

		if ($current_action == 'svninstall')
			$installdir = 'devs/' . smFindSVNrevisionID($settings['install_trunk_path'], '/svn');
		elseif (!empty($settings['use_tests']))
			$installdir = 'test/' . smFindDirectory(!empty($_REQUEST['action']) && $_REQUEST['action'] == 'uninstall' ? 1 : 0);
		else
			$installdir = 'smf/' . time();

	echo '
	<form method="post" class="form-horizontal well">
		<fieldset>
			<legend>', $txt['install_prep'], '</legend>
			<div class="control-group">', $txt['install_prep_des'], '</div>
			<input type="hidden" name="action" value="', $_REQUEST['action'], '" />';

	// Only show for installs.
	if ($current_action == 'svninstall')
		echo '
			<div class="control-group">
				<label><strong>Select Version</strong></label>
				<div class="controls"><input type="hidden" name="version" value="trunk" /></option> ', sprintf($txt['installing_from_checkout'], $settings['install_trunk_path']), ' <input type="hidden" name="null" id="install_type" value="null" /></div>
			</div>';
	elseif ($usage)
	{
		echo '
			<div class="control-group">
				<label><strong>Select Version</strong></label>
				<div class="controls"><select name="version" id="install_type" onchange="UpdateDirectory();">';

		// Trunk.
		if (!empty($settings['install_trunk_path']))
			echo '
				<optgroup label="SMF Trunk">
					<option value="trunk">Trunk</option>
				</optgroup>';

		// Branches.
		if (!empty($settings['install_branches_path']) && file_exists($settings['install_branches_path']))
		{
			$dir = array_reverse(scandir($settings['install_branches_path']));

			echo '
				<optgroup label="SMF Branches">';

			foreach ($dir as $branch)
			{
				if ($branch == '.' || $branch == '..' || $branch == '.svn' || $branch == '1.0-import')
					continue;

				echo '
					<option value="branch|', $branch, '">',	$branch, '</option>';	
			}

			echo '
				</optgroup>';
		}

		// Tags.
		if (!empty($settings['install_tags_path']) && file_exists($settings['install_tags_path']))
		{
			$dir = array_reverse(scandir($settings['install_tags_path']));

			echo '
				<optgroup label="SMF Tags">';

			foreach ($dir as $tag)
			{
				if ($tag == '.' || $tag == '..' || $tag == '.svn')
					continue;

				echo '
					<option value="tag|', $tag, '">',	smCleanName($tag), '</option>';	
			}

			echo '
				</optgroup>';
		}

		// Loop through each install.
		// $dir = scandir('
		echo '<optgroup label="Old Installs">';
		foreach($settings['installs'] as $key => $install)
		{
			// We are not using it!
			if (empty($install['use']))
				continue;

			// It doesn't exist, what do you mean!
			if (!file_exists($settings['install_root_path'] . $install['directory']))
				continue;

			// Ok. We are good to go!
			echo '
							<option value="', $key, '"', (!empty($_REQUEST['select']) && $_REQUEST['select'] == $key ? ' selected="selected"' : '') , (!empty($install['default']) && empty($_REQUEST['select']) ? ' selected="selected"' : ''), '>', $txt['use'],' ', $install['name'], '? (', $install['directory'], ')</option>';
		}
		echo '</optgroup>';

		echo '</select></div></div>';
	}

	// Directory.
	echo '
			<div class="control-group">
				<label><strong>', $txt['directory'] , '</strong></label>
				<div class="controls">', $settings['site_root'], '/<input type="text" name="dir" id="directory_location" value="', $installdir, '"/></div>
			</div>';

	if (!empty($settings['mods']['use_mods']) && $usage)
		echo '
			<div class="control-group">
				<label><strong>Customizations</strong></label>
				<div class="controls"><input type="checkbox" name="mods" value="1" /> ', $txt['add_customize'], ' (' . $settings['mods']['mods_dir'] . ')</div>
			</div>';

	if (!empty($settings['hack_install']['use_hacked_install']) && !empty($settings['hack_install']['install_setup']['allow_utf8']) && $usage)
		echo '
			<div class="control-group">
				<label><strong>UTF-8</strong></label>
				<div class="controls"><input type="checkbox" name="utf8" value="1" /> ', $txt['use_utf8'], '</div>
			</div>';

	if (!empty($settings['hack_install']['use_hacked_install']) && !empty($settings['hack_install']['install_setup']['allow_custom_forumname']) && $usage)
		echo '
			<div class="control-group">', $txt['custom_forum_name'], ' <input type="text" name="forumname" value="', sprintf($txt['default_forum_name'], $installdir), '" /></div>';

	// Database Selection type.
	echo '
			<div class="control-group">
				<label><strong>', $txt['database_type_select'], '</strong></label>
				<div class="controls">
					', $txt['database_type_desc'], '
					<label><input type="radio" name="database_type" value="none" selected="selected" /> ', $txt['database_type_none'], '</label>';


	foreach ($settings['database'] as $name => $db)
		if (function_exists($db['func']))
			echo '
					<label><input type="radio" name="database_type" value="', $name, '" /> ', $txt['database_type_' . $name] . '</label>';

	echo '
				</div>
			</div class="control-group">
			<div class="control-group"><input type="hidden" name="do" value="1" /><input type="submit" name="submit" value="', ($current_action == 'uninstall' ? $txt['uninstall'] : $txt['install']), '" class="btn btn-primary btn-large" /></div>
		</fieldset>
	</form>';
}

// The smMenuStart, Why? because.
function smMenuStart()
{
	global $txt;
	echo '
			<h2>', $txt['select_action'], '</h2>
			<a class="btn btn-large btn-primary" href="?scAction=new_install&action=install">', $txt['install'], '</a>
			<a class="btn btn-large btn-info" href="?scAction=new_install&action=svninstall">', $txt['svninstall'], '</a>
			<a class="btn btn-large btn-danger" href="?scAction=new_install&action=uninstall">', $txt['uninstall'], '</a>';

}

// smsmMain installation.
function smDoInstall($file, $smf)
{
	global $settings, $txt;

	// Set some dirty things.
	$new['database'] = str_replace('___', '_', 'smf_' . str_replace(array('/', '.'), array('__', '-'), $file));
	$new['directory'] = $settings['site_root'] . '/' . $file;
	$new['files_dir'] = $smf;

	// Now make sure what we are getting from exists.
	if (!file_exists($smf))
		echo '
	<div class="alert alert-error">
		', $txt['install_directory_gone'], '(', $smf, ')
		', smErrors($file), '
	</div>';
	else
		echo '
	<div class="alert alert-success">', $txt['install_directory_found'], '</div>';

	// Now make sure what we are getting from exists.
	if (smDirectoryParent($new['directory'] . '/..'))
		echo '
	<div class="alert alert-success">', $txt['to_directory_found'], '</div>';
	else
		echo '
	<div class="alert alert-error">
		', $txt['to_directory_gone'], '
		', smErrors($file), '
	</div>';

	// Copy the files, hopefully.
	if (!smDirectoryCopy($smf, $new['directory']))
		echo '
	<div class="alert alert-error">
		', $txt['unable_to_copy'], '
		', smErrors($file), '
	</div>';
	else
		echo '
	<div class="alert alert-success">', $txt['copy_success'], '</div>';

	// Copy the files, hopefully.
	if (!smDirectoryMoveInstaller($new['directory']))
		echo '
	<div class="alert alert-error">
		', $txt['unable_to_move'], '
		', smErrors($file), '
	</div>';
	else
		echo '
	<div class="alert alert-success">', $txt['move_success'], '</div>';

	// Hack out the install file, we don't care really if this fails.
	if (!smPrepareInstall($new['directory'], $new['database'], !empty($_POST['database_type']) ? $_POST['database_type'] : 'mysql'))
		echo '
	<div class="alert alert-info">
		', $txt['unable_to_hack_install'], '
		', smErrors($file), '
	</div>';
	else
		echo '
	<div class="alert alert-success">', $txt['hacked_success'], '</div>';

	if (isset($_REQUEST['mods']) && smPrepareCustomizations($new['directory']))
		echo '
	<div class="alert alert-success">', $txt['mod_prep_success'], '</div>';
	elseif (isset($_REQUEST['mods']))
		echo '
	<div class="alert alert-error">', $txt['mod_prep_fail'], '</div>';

	if (smPrepareDatabase(FALSE, $new['database'], $settings['database'], !empty($_POST['database_type']) ? $_POST['database_type'] : 'mysql', $new['directory']))
		echo '
	<div class="alert alert-success">', $txt['database_addition_good'], '</div>';
	else
		echo '
	<div class="alert alert-info">', $txt['database_addition_bad'], '</div>';

	// Done.
	echo '
	<div class="well">', sprintf($txt['done_install'], $file), '</div>';
}

function smDirectoryParent($directory)
{
	global $settings;

	// We have a directory and its writable.
	if (file_exists($directory) && is_writable($directory) && file_exists($directory . '/.index.php'))
		return TRUE;

	if (!file_exists($directory) || !is_writable($directory))
	{
		// What to do if its not there.
		if (!file_exists($directory))
			@mkdir($directory, $settings['permissions']['chmod']);

		// What to do if its not there.
		if (!is_writable($directory))
			@chmod($directory, $settings['permissions']['chmod']);

		// Still not there :(
		if (!file_exists($directory))
		{
			// Find a parent
			if (!file_exists(dirname($directory)))
				@mkdir(dirname($directory), $settings['permissions']['chmod']);

			// Need Permissions?
			if (!is_writable(dirname($directory)))
				@chmod(dirname($directory), $settings['permissions']['chmod']);

			// Hopefully we can now, otherwise we failed.
			if (!file_exists(dirname($directory)))
				return FALSE;
		}
	}

	// See if we can't create a .index.php
	if (!file_exists($directory . '/.index.php'))
	{
		// What the contents should be.
		$contents = '<' . '?php
$dir = dirname(__FILE__);
$dir_order = \'ksort\';
if (dirname(__FILE__) == realpath($_SERVER[\'DOCUMENT_ROOT\']))
	include(\'/srv/smf.test/public_html/.index.php\');
else
	include($_SERVER[\'DOCUMENT_ROOT\'] . \'/.index.php\');
?' . '>';

		// Save it.
		$hd = fopen($directory . '/.index.php', 'x');
		fwrite($hd, $contents);
		fclose($hd);

		// No go?
		if (!file_exists($directory . '/.index.php'))
			return FALSE;
	}

	return TRUE;
}

// Uninstall things.
function smDoRemoval($file)
{
	global $settings, $txt;

	// Setup the bad things.
	$bad['database'] = 'smf_' . str_replace('/', '-', $file);
	$bad['directory'] = $settings['site_root'] . '/' . $file;

	echo '
<ol>';

	// Delete the database,
	if (smPrepareDatabase(TRUE, $bad['database'], $settings['database'], !empty($_POST['database_type']) ? $_POST['database_type'] : 'mysql'))
		echo '
	<li>', sprintf($txt['database_removal_good'], $bad['database']), '</li>';
	else
		echo '
	<li>', sprintf($txt['database_removal_bad'], $bad['database']), '</li>';

	// Can we get rid of the files.
	if (file_exists($bad['directory']) && !smDirectoryRemoval($bad['directory']))
		echo '
	<li>', $txt['directory_removal_bad'], '</li>';
	else
		echo '
	<li>', $txt['directory_removal_good'], '</li>';	

	echo '
	<li>', sprintf($txt['done_removal'], $file), '</li>
	<li>', sprintf($txt['reinstall'], $file), '</li>
</ol>';

}

// Hack the install file.
function smPrepareInstall($dir, $database_name, $db_type = 'mysql')
{
	global $settings;

	// Forget it if you don't like me.
	if (empty($settings['hack_install']['use_hacked_install']))
		return FALSE;

	// This gets ugly..
	$lines_to_change = array (
		'id="password1"' => 'id="password1" value="' . $settings["hack_install"]["install_setup"]["user_pass"] . '"',
		'id="password2"' => 'id="password2" value="' . $settings["hack_install"]["install_setup"]["user_pass"] . '"',
		'name="password3"' => 'name="password3" value="' . $settings['database'][$db_type]['pass'] . '"'
	);

	// The SMF 1.1 to 2.0 Beta 3 Public Installs
	$lines_to_change += array (
		"', \$db_user, '" => "{$settings['database'][$db_type]['user']}",
		"', \$db_passwd, '" => "{$settings['database'][$db_type]['pass']}",
		"', empty(\$db_name) ? 'smf' : \$db_name, '" => $database_name,
		"', \$_POST['username'], '" => "{$settings['hack_install']['install_setup']['user_name']}",
		"', \$_POST['email'], '" => "{$settings['hack_install']['install_setup']['user_email']}",
		);

	// The New SMF 2.0 Beta 4+ Install
	$lines_to_change += array (
		"', \$incontext['db']['user'], '" => "{$settings['database'][$db_type]['user']}",
		"', \$incontext['db']['pass'], '" => "{$settings['database'][$db_type]['pass']}",
		"', empty(\$incontext['db']['name']) ? 'smf' : \$incontext['db']['name'], '" => $database_name,
		"', \$incontext['username'], '" => "{$settings['hack_install']['install_setup']['user_name']}",
		"', \$incontext['email'], '" => "{$settings['hack_install']['install_setup']['user_email']}",
	);

	// Default our install type?
	if (!empty($db_type))
		$lines_to_change['<option value="'] = '<option \' . ($key == "' . $db_type . '" ? \' selected="selected"\' : \'\') . \' value="'; //"<option'. (\$key == \"' . $db_type . '\" ? ' selected=\"selected\"' : '') . ' value=\"";

	// Do we want to see our passwords?
	if (!empty($settings['hack_install']['install_setup']['show_passwords']))
		$lines_to_change['type="password"'] = 'type="text"';

	// Do we want to customize our name?
	if (!empty($settings['hack_install']['install_setup']['allow_custom_forumname']) && !empty($_REQUEST['forumname']))
		$lines_to_change["', \$txt['install_settings_name_default'], '"] = $_REQUEST['forumname'];
	elseif (strpos(trim($dir), '/dev/') !== false)
	{
		preg_match('~/dev/(\d+)~i', $dir, $matches);
		$lines_to_change["', \$txt['install_settings_name_default'], '"] = 'SMF Trunk - ' . $matches[1];
	}
	else
	{
		preg_match('~([branch|tag]+)-([\d.]+)/(\d+)~i', $dir, $matches);

		if (!empty($matches))
			$lines_to_change["', \$txt['install_settings_name_default'], '"] = 'SMF ' . $matches[2] . ' ' . ucfirst($matches[1]) . ' - ' . $matches[3];
		else
			$lines_to_change["', \$txt['install_settings_name_default'], '"] = 'SMF ' . ucfirst($dir);
	}

	// Are we expecting multiple languages to be used/tested?
	if (!empty($settings['hack_install']['install_setup']['allow_utf8']))
		$lines_to_change['name="utf8"'] = 'name="utf8" checked="checked"';

	// Simple. Get file contents. Put them back after we change them.
	$opened_file = file_get_contents($dir . '/install.php');
	file_put_contents($dir . '/install.php', strtr($opened_file, $lines_to_change));

	// Index file.
	if (strpos('SVN/', $_REQUEST['dir']) !== false)
	{
		$index_changes = array();
		$tmp = explode('-', str_replace('SVN/', '', $_REQUEST['dir']));
		$revision = trim($tmp[0]);
		$index_changes["// Get everything started up..."] = "// We are going to override this since it is a SVN version.\rif(preg_match('~action(,|=)admin~', \$_SERVER['REQUEST_URI']))\r\t\$forum_version = 'SMF REV " . $revision . "';\r\r// Get everything started up.";
		$index_changes["// Load the current board's information."] = "// Admins should get goodies.\r\tif(\$user_info['is_admin'])\r\t\t\$GLOBALS['db_show_debug'] = TRUE;\r\r\t// Load the current board's information...";
		$opened_index_file = file_get_contents($dir . '/index.php');
		file_put_contents($dir . '/index.php', strtr($opened_index_file, $index_changes));
	}

	return TRUE;
}

// Do the Database work.
function smPrepareDatabase($removal = false, $data_name = 'SMF_Dev', $db = array(), $db_type = 'mysql', $directory)
{
	global $settings;

	// No host? I guess we need default settings.
	if (empty($db) || empty($db[$db_type]))
		$db = $settings['database'];

	// Still No host? No options left.
	if (empty($db) || empty($db[$db_type]))
		return FALSE;

	// Removing the database.
	if ($removal)
		$query_prefix = 'DROP DATABASE';
	else
		$query_prefix = 'CREATE DATABASE';

	// Find the database, where is the database.
	$data = $db[$db_type];
	$data['host'] = !empty($data['host']) ? $data['host'] : 'localhost';
	$data['user'] = !empty($data['user']) ? $data['user'] : 'root';
	$data['pass'] = !empty($data['pass']) ? $data['pass'] : '';
	$data['dorg'] = !empty($data['dorg']) ? $data['dorg'] : 'test';

	if ($db_type == 'mysql')
	{
		mysql_connect($data['host'], $data['user'], $data['pass']);
		$query = 'mysql_query';

		if (!$query("{$query_prefix} `{$data_name}`;"))
			return FALSE;
	}
	elseif ($db_type == 'postgresql')
	{
		pg_connect("host=" . $data['host'] . " dbname=" . $data['dorg'] . " user=" . $data['user'] . " password=" . $data['pass'] . "");
		$query = 'pg_query';

		if (!$query("{$query_prefix} \"{$data_name}\";"))
			return FALSE;
	}

	if (!isset($query))
		return FALSE;

/* Fore future usage to many automate the install fully?

	if (!file_exists($directory . '/other/install_2-0_' . $db_type . '.sql'))
		return FALSE;
*/
}

// Do the modifications
function smPrepareCustomizations($smf, $debug = FALSE)
{
	global $settings;

	// Not enabled? What do you mean not enabled!
	if (empty($settings['mods']['use_mods']))
		return FALSE;

	// Simplify things, and prepare to get mods..
	$smf = $smf . '/Packages';
	$mods = (!$settings['mods']['custom_dir'] ? $settings['mods']['install_root_path'] . '/' : '') . $settings['mods']['mods_dir'];

	// No directory? There is nothing to copy from..
	if (!is_dir($mods))
		return FALSE;

	if (is_dir($smf . '/'))
	{
		if (smDirectoryCopy($mods, $smf, $debug))
			return TRUE;
		else
			return FALSE;
	}
	else
		return FALSE;
}

// Remove a directory.
function smDirectoryRemoval($dir)
{
	// This simply put, opens the directory, loops through it to find anything that isn't . or .. and removes it.
	$handle = opendir($dir);
	while (($item = readdir($handle)) !== FALSE)
	{
		if ($item != '.' && $item != '..')
		{
			if (is_dir($dir . '/' . $item))
				smDirectoryRemoval($dir . '/' . $item);
			else
				unlink($dir . '/' . $item);
		}
	}
	closedir($handle);

	// Now we attempt to remove the directory. 
	rmdir($dir);

	// Hopefully we had success, if we don't it will fail all the way through.
	if (!file_exists($dir))
		return TRUE;
	else
		return FALSE;
}

// Copy a directory to another.
function smDirectoryCopy($srcdir, $dstdir, $debug = FALSE)
{
	global $settings, $txt;

	$num = 0;

	// If it doesn't exist. create it.
	if (!is_dir($dstdir))
	{
		mkdir($dstdir, $settings['permissions']['chmod']);

		// Some systems we need to own the files.
		if (!empty($settings['permissions']['do_chown']))
			@chown($dstdir, $settings['permissions']['chown']);

		// Sometimes creating a directory doesn't chmod it.
		@chmod($dstdir, $settings['permissions']['chmod']);
	}

	// Open the directory.
	if ($curdir = opendir($srcdir))
	{
		// Loopy loop de-loop.
		while($file = readdir($curdir))
		{
			// No return directories or stupid DS_Store files.
			if (!in_array($file, array('.', '..', '.DS_Store', 'Thumbs_DB', '.svn')))
			{
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
						@chmod($dstdir, $settings['permissions']['chmod']);
						if (!empty($settings['permissions']['do_chown']))
							@chown($dstdir, $settings['permissions']['chown']);
					}
					else
						$ow = 1;

					// If we need to update it. Lets do that.
					if ($ow > 0)
					{
						// We speaking?
						if ($debug)
							echo sprintf($txt['coping'], $srcfile, $dstfile);

						// Copy the file hopefully.
						if (copy($srcfile, $dstfile))
						{
							if (!empty($settings['permissions']['do_chown']))
								@chown($dstdir, $settings['permissions']['chown']);

							@chmod($dstdir, $settings['permissions']['chmod']);
							if (!empty($settings['permissions']['do_chown']))
								@chown($dstdir, $settings['permissions']['chown']);
							@chmod($srcfile, $settings['permissions']['chmod']);
							@touch($dstfile, filemtime($srcfile));
							++$num;
							if ($debug)
								echo $txt['ok'], '<br />';
						}
						else
							echo sprintf($txt['error_copy'], $srcfile), '<br />';
						}                 
					}
				// Hey, we get to loopy - loop all over again.
				else if (is_dir($srcfile))
					$num += smDirectoryCopy($srcfile, $dstfile, $debug);
			}
		}
		closedir($curdir);
	}
	return $num;
}

// Move Install files.
function smDirectoryMoveInstaller($dir)
{
	$install_file = array(
		// Generic files.
		'install.php', 'Settings.php', 'Settings_bak.php',

		// 1.0 install file.
		'smf_1-0.sql',
	);

	// Grab all the other installer files.
	if (file_exists($dir . '/other/'))
		$install_file += glob($dir . '/other/install_*.sql');

	// Now copy, only if they exist.
	foreach($install_file as $file)
		if (file_exists($dir . '/other/' . $file))
			copy($dir . '/other/' . $file, $dir . '/' . $file);

	if (file_exists($dir . '/upgrade.php'))
		unlink($dir . '/upgrade.php');

	return TRUE;
}

// chmod a directory.
function smDirectoryChmod($srcdir, $perms = 0, $chown = FALSE)
{
	global $txt, $settings;
	$num = 0;

	// If we have no permissions. use default.
	if (empty($perms))
		$perms = $settings['permissions']['chmod'];

	// If its not a directory we chmod it.
	if (!is_dir($srcdir))
	{
		@chmod($srcdir, $perms);
		if ($chown)
			@chown($srcdir, $perms);
	}
	if ($curdir = opendir($srcdir))
	{
		while($file = readdir($curdir))
		{
			if (!in_array($file, array('.', '..')))
			{
				$srcfile = $srcdir . '/' . $file;
				if (is_file($srcfile))
				{
					++$num;
					@chmod($srcdir, $perms);
					if ($chown)
						@chown($srcdir, $perms);
				}
				else if (is_dir($srcfile))
					$num += smDirectoryChmod($srcfile);
				else
					echo $txt['unknown_doc'];
			}
		}
		closedir($curdir);
	}
	return $num;
}

// Find the SVN revision number
function smFindSVNrevisionID($svn_dir, $smf_dir)
{
	global $settings;

	// No file, forget it.
	if (!file_exists($svn_dir . '/.svn/entries'))
		return date('Y-m-d_H-i', time()) . 'a';

	// Open the file, read it and close it.
	$opened_file = fopen($svn_dir . '/.svn/entries', "r");
	$junk = fread($opened_file, 13);
	fclose($opened_file);

	// Pull out the revision
	preg_match("~dir([0-9]*)~mis", str_replace(array("\r", "\n", "\r\n"), '', $junk), $match);

	// If it fails.. I guess we get out.
	if (empty($match[0]) || empty($match[1]))
		return date('Y-m-d_H-i', time()) . 'b';

	// If it exists (A previous install?) Find a new one.
	if (file_exists($settings['site_root'] . $smf_dir . '/' . $match[1] . '-0'))
		return smFindDirectory(FALSE, $match[1] . '-', $smf_dir);

	// woohoo!
	return $match[1] . '-0';
}

// Detect which directory to use in our tests directory.
function smFindDirectory($reverse = FALSE, $prefix_letter = null, $dir = '')
{
	global $settings, $txt;

	if (!empty($settings['use_tests']) && empty($dir))
		$dir = '/test';

	// Our letters we are going to attempt to use.
	$betray = array(
		'0' => 'ZERO',	'1' => 'ONE',	'2' => 'TWO',
		'3' => 'TRHEE',	'4' => 'FOUR',	'5' => 'FIVE',
		'6' => 'SIX',	'7' => 'SEVEN',	'8' => 'EIGHT',
		'9' => 'NINE',
	);
	$min_id = 0; // If this is not the first, it could cause a big memory loop.
	$max_id = 99;

	// Are we going backwards?
	if ($reverse)
		$betray = array_reverse($betray);

	// Simple, loop through each alphabet letter until we find one we havnt used.
	$i = 0;
	while ($i != $max_id)
	{
		if (!file_exists($settings['site_root'] . $dir . '/' . (empty($prefix_letter) ? '0' : $prefix_letter) . $i . '/'))
		{
			$folder_id = $i;
			break;
		}
		elseif ($i == $max_id)
			break;
		else
			++$i;
	}
	
	if (isset($folder_id))
		return (empty($prefix_letter) ? '0' : $prefix_letter) . $folder_id;
	else
		return smFindDirectory($reverse, $i + 1, !empty($dir) ? $dir : '');
}

function smCleanName($version)
{
	$version = strtr($version, array(
		'smf_' => 'SMF ',
		'-' => '.',
		'beta' => ' Beta ',
		'b' => ' Beta ',
		'fix' => '.',
		'rc' => ' Release Candidate ',
		'RC' => ' Release Candidate ',
		'p' => ' Public',
	));

	// And anything that breaks.
	$version = strtr($version, array(
		'Publici' => 'PI',
		'..' => '.',
		'. ' => ' ',
	));

	return $version;
}

// Errors, we don't have errors.
function smErrors($file)
{
	global $txt;
		echo '
	<li>', $txt['errors_occurred'], '</li>
	<li>', sprintf($txt['try_uninstall'], $file), '</li>';

	// Done.
	echo '
	<li>', sprintf($txt['done_install'], $file), '</li>
</ol>';

	exit;

}

// The language.
function smLanguage()
{
	global $settings, $txt;
$txt['header'] = '<span title="Try saying that 10 times fast">SMF Automated File & Database Preperation</span>';

$txt['install_prep'] = 'Installtion Preparation';
$txt['install_prep_des'] = 'Follow the simple Step progress to setup an install.';
$txt['directory'] = 'Directory';
$txt['add_customize'] = 'Add Customizations to Packages folder?';
$txt['use_utf8'] = 'Use UTF-8?';
$txt['use'] = 'Use';
$txt['uninstall'] = 'Uninstall';
$txt['install'] = 'Install';
$txt['svninstall'] = 'SVN Install';
$txt['installing_from_checkout'] = 'Installing a SMF SVN Checkout from %s';

$txt['select_action'] = 'Select the action you wish to take!';

$txt['directory_exists'] = 'New Directory Check: Directory Existed, Please use uninstall first';
$txt['directory_gone'] = 'New Directory Check: No files existed, preparing to install';
$txt['install_directory_gone'] = 'No Directory to get from. Please use a valid path to a uninstalled forum';
$txt['install_directory_found'] = 'Source Directory Check: Files found. Ready to copy';
$txt['to_directory_gone'] = 'No Directory to put to. Please verify the path is correct';
$txt['to_directory_found'] = 'To Directory Check: Directory exists and is writable.';
$txt['unable_to_copy'] = 'Copying files: Could not Create the new install';
$txt['copy_success'] = 'Coping files: Success!';
$txt['unable_to_hack_install'] = 'Editing install: Could not Update Install.php';
$txt['hacked_success'] = 'Editing install: Successs! install has been edited!';
$txt['mod_prep_success'] = 'Mods Preparation: Success! Your customizations have been prepared for installation!';
$txt['mod_prep_fail'] = 'Mods Preparation: We where unable to prepare any customizations for install.';
$txt['errors_occurred'] = 'We had some errors while trying to get everything ready. Things may have not copied over or worked correctly';
$txt['try_uninstall'] = 'You can try to <a href="?action=uninstall&dir=%s&do">uninstall this copy</a> and try again.';
$txt['done_install'] = 'Done, <a href="' . $settings['site_url'] . '/%s/install.php" class="btn btn-primary"> Click here to start installation.</a>';
$txt['database_addition_good'] = 'Created Database successfully';
$txt['database_addition_bad'] = 'Unable to create the database';
$txt['unable_to_move'] = 'Moving files: Could not move installer files';
$txt['move_success'] = 'Moving files: Success!';

$txt['database_removal_good'] = 'Database Removal: %s Has been removed';
$txt['database_removal_bad'] = 'Database Removal: Failed to remove %s';
$txt['directory_removal_good'] = 'Directory Removal: Success!';
$txt['directory_removal_bad'] = 'Directory Removal: Unable to remove Directory';
$txt['done_removal'] = 'Done, %s has been Removed!';
$txt['reinstall'] = 'You may <a href="?action=install&dir=%s&do">install this</a> again.';

$txt['out_of_letters'] = 'Error: Out of letters.';
$txt['unknown_doc'] = 'Its not a file or a Directory.. Go figure..';
$txt['error_copy'] = 'Error: File \'%s\' could not be copied!';
$txt['ok'] = 'Ok';
$txt['coping'] = 'Copying \'%s1\' to \'%s2\'...';

$txt['custom_forum_name'] = 'Custom Forum name?';
$txt['default_forum_name'] = 'SMF %s Community';

$txt['database_type_select'] = 'Database Type';
$txt['database_type_desc'] = '(For SMF 2.0 and above)';
$txt['database_type_none'] = 'Do not use any Databases';
$txt['database_type_mysql'] = 'Use MySQL?';
$txt['database_type_postgresql'] = 'Use PostgreSQL?';
	return $txt;
}