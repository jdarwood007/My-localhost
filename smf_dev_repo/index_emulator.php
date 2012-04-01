<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 'on');

$base_path = '/srv/repos/svn/sm-smf/trunk';
$settings_path = '/srv/smf.test/svn_files';

$php_open = '<' . '?' . 'php';

// The revision.
$revision = trim(str_replace('Revision: ', '', shell_exec('svn info ' . $base_path . ' | grep "Revision"')));

// Get.
$contents = file_get_contents($base_path . '/index.php');

// Edit.
$contents = strtr($contents, array(
	'require_once(dirname(__FILE__) . \'/Settings.php\');' => 'require(\'' . $settings_path . '/Settings.php\');',
));

// A fast way to destroy our session.
if (isset($_GET['destory_session']))
{
	$contents = file_get_contents($base_path . '/SSI.php');
	$contents = substr($contents, strlen($php_open));
	eval($contents);

	require_once($sourcedir . '/Subs-Auth.php');
	setLoginCookie(-3600, 0);
	if (isset($_SESSION['login_' . $cookiename]))
		unset($_SESSION['login_' . $cookiename]);

	// Try really try.
	@session_destroy();
	unset($_SESSION, $_COOKIE);
	@setcookie ($modSettings['cookie_name'], '', time() - 3600);

	Header('location: ' . str_replace('session_destroy', '', $_SERVER['HTTP_REFERER']));
	exit;
}

// Prepare.
$contents = substr($contents, strlen($php_open));

$GLOBALS['true_start'] = microtime(true);
eval($contents);
$GLOBALS['true_end'] = microtime(true);

// This used to be included via the php append ini
// but since we come from nginx nowadays we and have total control over the script execution, we handle this.
// A shutdown function doesn't seem to always work and seems to be hit an miss, so avoiding it.
require_once($settings_path . '/exit_script.php');