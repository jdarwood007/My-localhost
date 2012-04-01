<?php
$base_path = '/srv/repos/sm-smf/trunk';
$settings_path = '/srv/smf.test/svn_files';

$php_open = '<' . '?' . 'php';
$php_close = '?' . '>';
require_once(dirname(__FILE__) . '/Settings.php');

// Get.
$contents = file_get_contents($base_path . '/SSI.php');

// Edit.
$contents = strtr($contents, array(
	'require_once(dirname(__FILE__) . \'/Settings.php\');' => 'require_once(\'' . $settings_path . '/Settings.php\');',
));

// Prepare.
$contents = substr($contents, strlen($php_open), -strlen($php_close));

// Send.
eval($contents);

require_once($settings_path . '/exit_script.php');
