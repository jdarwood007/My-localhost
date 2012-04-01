<?php
/* This script is auto called with a prepend from fpm via:
php_admin_value[auto_prepend_file] = /srv/smf.test/index_files/main.php

Calls to this come via the Singleton:
scSite::_()->templateHeader('Hello World Title');
scSite::_()->templateFooter();
*/
$scSite = scSite::_();

// If we need to give a asset, do that over anything else.
if (isset($_REQUEST['scAsset']))
	$scSite->giveAsset($_REQUEST['scAsset']);

/*
 * My main site array with stuff regarding my template
*/
class scSite
{
	/* The only setting, the location of the assets */
	public $assets = '/srv/smf.test/index_files/assets';

	/* The navigation menu stuff. */
	private $navItems = array();
	private $addNavItemDrop = array(
			array('?scAction=new_install', 'Install SMF'),
			array('?scAction=clean_svn', 'Clean SVN checkout'),
			array('?scAction=modpacking', 'Package Modifications'),
			array('?scAction=php_syntax_check', 'PHP Syntax check')
	);
	private $additionalHeaders = '';

	/* The instance id */
	public static $instance = 0;

	/*
	 * Create a object and store it
	 * @return int The resource id of the object.
	*/
	public static function _()
	{
		if (!(self::$instance instanceof scSite))
			self::$instance = new scSite;

		return self::$instance;
	}

	/*
	 * Set some defaults.
	*/
	public function __construct()
	{
		// Set a default timezone if one isn't.
		date_default_timezone_set("America/Los_Angeles");

		// Enable error reporting for our development site.
		error_reporting(E_ALL);
		ini_set('display_errors', '1');
	}

	/*
	 * Display a asset
	*/
	public function giveAsset($asset)
	{
		switch ($asset)
		{
			case 'css':
				$this->doCSS();
				break;

			case 'js':
				$this->doJS();
				break;
		}

		exit;
	}

	/*
	 * Adds a item to the navigation menu.
	 * @param $href String The url to link to.
	 * @param $name String The name/link name of the link.
	*/
	public function addNavItem($href, $name)
	{
		$this->navItems[] = array($href, $name);
	}

	/*
	 * Adds a item to the navigation drop menu.
	 * @param $href String The url to link to.
	 * @param $name String The name/link name of the link.
	*/
	public function addNavItemDrop($href, $name)
	{
		$this->addNavItemDrop[] = array($href, $name);
	}

	/*
	 * Adds some additional HTML to the <head>.
	 * @param $html String The additional html for <head>.
	*/
	public function addHeader($html)
	{
		$this->additionalHeaders .= $html;
	}

	/*
	 * Show the header porition of the template.
	 * @param $title string The page title. Should be html cleaned.
	 * @return void No return, however output is executed at this point.
	*/
	public function templateHeader($title = '')
	{
		if (empty($title))
			$title = isset($this->title) ? $this->title : (isset($GLOBALS['txt']['header']) ? $GLOBALS['txt']['header'] : 'SleePyCode');

		echo '<!DOCTYPE html><!-- HTML 5 -->
	<html dir="', !empty($txt['lang_rtl']) ? 'rtl' : 'ltr', '" lang="en-US">
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
		<title>', $title, '</title>
		<link rel="stylesheet" href="?scAsset=css" type="text/css" media="all">
		<script type="text/javascript" src="?scAsset=js"></script>',
		$this->additionalHeaders, '
	</head>

	<body>
		<header class="navbar navbar-fixed-top">
			<div class="navbar-inner">
				<div class="container">
					<a class="brand" href="#" title="I am a old Easter Egg">$c: ', $title, '</a>';

		if (!empty($this->navItems) || !empty($this->addNavItemDrop))
		{
			echo '
					<ul class="nav">';

			foreach ($this->navItems as $item)
				echo '
						<li><a href="', $item[0], '">', $item[1], '</a></li>';

			if (!empty($this->addNavItemDrop))
			{
				echo '
						<li class="dropdown active">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown">Navigation<b class="caret"></b></a>
							<ul class="dropdown-menu">';

				foreach ($this->addNavItemDrop as $item)
					echo '
								<li><a href="', $item[0], '">', $item[1], '</a></li>';

				echo '
							</ul>
						</li>';
			}



			echo '
					</ul>';
		}

		echo '
					<ul class="nav pull-right">
						<li class="dropdown active">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown">', isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '127.0.0.1' , '<b class="caret"></b></a>
							<ul class="dropdown-menu">
								<li><a href="http://dev.test">dev.test (PHP 5.3)</a></li>
								<li><a href="http://smfMaster.test">smfMaster.test (PHP 5.3)</a></li>
								<li><a href="http://smf.test">smf.test (PHP 5.3)</a></li>
								<li><a href="http://sd.test">sd.test  (PHP 5.3)</a></li>
								<li><hr /></li>
								<li><a href="http://dev.test:81">dev.test (PHP 5.4)</a></li>
								<li><a href="http://smfMaster.test:81">smfMaster.test (PHP 5.4)</a></li>
								<li><a href="http://smf.test:81">smf.test (PHP 5.4)</a></li>
								<li><a href="http://sd.test:81">sd.test (PHP 5.4)</a></li>
							</ul>
						</li>
					</ul>
				</div>
			</div>
		</header>
		<div class="container">';
	}

	/*
	 * The footer of the page.
	 * @return void No return, however output is executed at this point.
	*/
	public function templateFooter()
	{
		echo '
		</div>

		<footer class="footer form-actions">
			<div class="container">
				&copy; SleePyCode.com ', date('Y', time()), '
			</div>
		</footer>
	</body>
	</html>';
	}

	/*
	 * Output our css files all at once by combining them.  If this wasn't for development we would cache these.
	 * @return void No return, however output is executed at this point and script terminates.
	*/
	public function doCSS()
	{
		header('Content-type: text/css');
		readfile(dirname(dirname(__FILE__)) . '/index_files/assets/bootstrap.min.css');
		readfile(dirname(dirname(__FILE__)) . '/index_files/assets/bootstrap_custom.css');
		exit;
	}

	/*
	 * Output our javascript files all at once by combining them.  If this wasn't for development we would cache these.
	 * @return void No return, however output is executed at this point and script terminates.
	*/
	public function doJS()
	{
		header('Content-type: text/javascript');
		readfile(dirname(dirname(__FILE__)) . '/index_files/assets/jquery.min.js');
		readfile(dirname(dirname(__FILE__)) . '/index_files/assets/bootstrap.min.js');
		exit;
	}

	/*
	 * This is used on our test code pages.  We ask for input and jump out when needed.
	 * @param $title string The title of the page.
	 * @param $inputs int Number of inputs we need Default is 1.
	*/
	public function doTest($title, $inputs = 1)
	{
		register_shutdown_function('scSite::doShutdown');

		$this->templateHeader('Test: ' . $title);

		echo '
			<form class="well" method="post">
				<input type="text" name="a"', isset($_REQUEST['a']) ? 'value="'. $_REQUEST['a'] . '"' : '', ' />';

		for ($i = 1; $i < $inputs; $i++)
			echo '
				<input type="text" name="n[', $i, ']"', isset($_REQUEST['n'][$i]) ? 'value="'. $_REQUEST['n'][$i] . '"' : '', ' />';

		echo '
				<input type="submit" class="btn btn-primary" />
			</form>';

		if (empty($_REQUEST['a']))
			exit;
	}

	/*
	 * This comes from a register_shutdown_function and just closes up the template.
	 * @return void No return, however output is executed at this point and script terminates.
	*/
	public static function doShutdown()
	{
		self::_()->templateFooter();
		exit;
	}
}