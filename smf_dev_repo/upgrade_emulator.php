<?php
$other_path = '/srv/repos/svn/sm-smf/trunk/other';
$settings_path = '/srv/smf.test/svn_files';

$php_open = '<' . '?' . 'php';
require_once(dirname(__FILE__) . '/Settings.php');

// Is it cached? We cache this because evading this every time makes for slower ajax calls.
if (file_exists($settings_path . '/upgrade_emulate_cache') && !empty($_REQUEST['data']))
	$contents = file_get_contents($settings_path . '/upgrade_emulate_cache');
else
{
	// Get.
	$contents = file_get_contents($other_path . '/upgrade.php');

	$changes = array(
		'dirname(__FILE__)' => '\'' . $other_path . '\'',
		'require_once($upgrade_path . \'/Settings.php\');' => 'require_once(\'' . $settings_path . '/Settings.php\');',
		'require_once(dirname(__FILE__) . \'/upgrade_1-0.sql\');' => 'require_once(\'' . $other_path . '/upgrade_1-0.sql\');',
		'require_once(dirname(__FILE__) . \'/upgrade_1-1.sql\');' => 'require_once(\'' . $other_path . '/upgrade_1-1.sql\');',
		'require_once(dirname(__FILE__) . \'/upgrade_2-0_mysql.sql\');' => 'require_once(\'' . $other_path . '/upgrade_2-0_mysql.sql\');',
		'require_once(dirname(__FILE__) . \'/upgrade_2-0_postgresql.sql\');' => 'require_once(\'' . $other_path . '/upgrade_2-0_postgresql.sql\');',
		'require_once(dirname(__FILE__) . \'/upgrade_2-0_sqlite.sql\');' => 'require_once(\'' . $other_path . '/upgrade_2-0_sqlite.sql\');',
		'dirname(__FILE__) . \'/upgrade_2-0_\' . $db_type . \'.sql\'' => '\'' . $other_path . '/upgrade_2-0_\' . $db_type . \'.sql\'',
		'$boarddir . \'/Settings' => '\'' . $settings_path . '/Settings',
	);

	// We are trying to capture any future upgrade files.
	$upgrade_files = array_map('basename', glob($other_path . '/upgrade_*.sql'));
	foreach ($upgrade_files as $version)
	{
		$changes['require_once(dirname(__FILE__) . \'/'. $version . '\');'] = 'require_once(\'' . $other_path . '/'. $version . '\');';

		$temp = explode('_', $version);
		$changes['dirname(__FILE__) . \'/upgrade_' . $temp[1] . '_\' . $db_type . \'.sql');']' = '\'' . $other_path . '/upgrade_' . $temp[1] . '_\' . $db_type . \'.sql\'';
	}

	// Edit.
	$contents = strtr($contents, $changes);

	// Prepare.
	$contents = substr($contents, strlen($php_open));

	// Because this is complicated and time consuming, we want to cache it.
	file_put_contents($settings_path . '/upgrade_emulate_cache', $contents);
}

// Send.
eval($contents);

// done.
exit;