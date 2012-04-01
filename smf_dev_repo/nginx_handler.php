<?php

error_reporting(E_ALL);
ini_set('display_errors', 'on');

if ($_SERVER['SCRIPT_NAME'] == '/index.php')
	require_once('index_emulator.php');
elseif ($_SERVER['SCRIPT_NAME'] == '/upgrade.php')
	require_once('upgrade_emulator.php');
{
	phpinfo();
	exit;
}