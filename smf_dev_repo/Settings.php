<?php
/**********************************************************************************
* Settings.php                                                                    *
***********************************************************************************
* SMF: Simple Machines Forum                                                      *
* Open-Source Project Inspired by Zef Hemel (zef@zefhemel.com)                    *
* =============================================================================== *
* Software Version:           SMF 2.0 RC1                                         *
* Software by:                Simple Machines (http://www.simplemachines.org)     *
* Copyright 2006-2009 by:     Simple Machines LLC (http://www.simplemachines.org) *
*           2001-2006 by:     Lewis Media (http://www.lewismedia.com)             *
* Support, News, Updates at:  http://www.simplemachines.org                       *
***********************************************************************************
* This program is free software; you may redistribute it and/or modify it under   *
* the terms of the provided license as published by Simple Machines LLC.          *
*                                                                                 *
* This program is distributed in the hope that it is and will be useful, but      *
* WITHOUT ANY WARRANTIES; without even any implied warranty of MERCHANTABILITY    *
* or FITNESS FOR A PARTICULAR PURPOSE.                                            *
*                                                                                 *
* See the "license.txt" file for details of the Simple Machines license.          *
* The latest version can always be found at http://www.simplemachines.org.        *
**********************************************************************************/

if (isset($revision))
	$forum_version .= ' Revision:' . $revision;

########## Maintenance ##########
# Note: If $maintenance is set to 2, the forum will be unusable!  Change it to 0 to fix it.
$maintenance = 0;
$mtitle = 'Maintenance Mode';		# Title for the Maintenance Mode message.
$mmessage = 'Okay faithful users...we\'re attempting to restore an older backup of the database...news will be posted once we\'re back!';		# Description of why the forum is in maintenance mode.

########## Forum Info ##########
$mbname = 'SleePys Development Community';		# The name of your forum.
$language = 'english';		# The default language file set for the forum.
$boardurl = 'http://svn.test';		# URL to your forum's folder. (without the trailing /!)
$webmaster_email = 'noreply@svn.test';		# Email address to send emails from. (like noreply@yourdomain.com.)
$cookiename = 'svn_test';		# Name of the cookie to set for authentication.

########## Database Info ##########
# Always have the defualt (MySQL)
$db_type = 'mysql';
$db_server = 'localhost';
$db_name = '__smf_svn_trunk';
$db_user = 'smf';
$db_passwd = '';
$ssi_db_user = '';
$ssi_db_passwd = '';
$db_prefix = 'smf_';
$db_persist = 0;
$db_error_send = 1;
$db_show_debug = true;

# Maybe we are using Postgresql?
if (isset($_SERVER['HTTP_HOST']) && substr($_SERVER['HTTP_HOST'], 0, strlen('postgresql')) == 'postgresql')
{
	$boardurl = 'http://postgresql.svn.test';
	$cookiename .= '_postgresql';

	$db_type = 'postgresql';
	$db_user = 'SMF';
}
# Maybe Sqlite?
elseif (isset($_SERVER['HTTP_HOST']) && substr($_SERVER['HTTP_HOST'], 0, strlen('sqlite')) == 'sqlite')
{
	$boardurl = 'http://sqlite.svn.test';
	$cookiename .= '_sqlite';

	$db_type = 'sqlite';
	$db_name = '/srv/smf.test/svn_files/sqlite_db';
}

########## Directories/Files ##########
# Note: These directories do not have to be changed unless you move things.
$boarddir = '/srv/repos/svn/sm-smf/trunk';		# The absolute path to the forum's folder. (not just '.'!)
$sourcedir = '/srv/repos/sm-smf/trunk/Sources';		# Path to the Sources directory.
$cachedir = '/srv/smf.test/svn_files/cache';		# Path to the cache directory.
$cachedir_fix = $cachedir;

########## Error-Catching ##########
# Note: You shouldn't touch these settings.
$db_last_error = 0;

# Make sure the paths are correct... at least try to fix them.
if (!file_exists($boarddir) && file_exists(dirname(__FILE__) . '/agreement.txt'))
	$boarddir = dirname(__FILE__);
if (!file_exists($sourcedir) && file_exists($boarddir . '/Sources'))
	$sourcedir = $boarddir . '/Sources';
if (!file_exists($cachedir) && file_exists($boarddir . '/cache'))
	$cachedir = $boarddir . '/cache';