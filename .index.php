<?php
$files = new scReadDir();

// Test our action.
if (isset($_REQUEST['scAction']) && $files->testAction($_REQUEST['scAction']))
	$files->doAction($_REQUEST['scAction']);
else
{
	if (isset($dir_base_dir))
		$files->setBaseDir($dir_base_dir);
	if (isset($dir))
		$files->setDir($dir);
	if (isset($dir_ignores))
		$files->addIgnore($dir_ignores);
	if (isset($dir_date_string))
		$files->changeDateString($dir_date_string);
	if (isset($dir_order))
		$files->setOrder($dir_order);
	if (isset($dir_order_callback))
		$files->setOrderCallback($dir_order_callback);

	$files->template($files->readDir());
}

/*
 * Read a directory
*/
class scReadDir
{
	private $dir = null;
	private $base_dir = null;
	private $dir_title = '';
	private $ignore_files = array('.', '..', '.DS_Store', 'robots.txt', '.htaccess', 'favicon.ico', 'error_log', '.index.php', '.ftpquota');
	private $date_string = 'M d, y G:i:s';
	private $sort_method = 'krsort';
	private $sort_callback = null;

	/*
	 * Set the defaults, can be adjusted later.
	*/
	public function __construct()
	{
		$this->setBaseDir($_SERVER['DOCUMENT_ROOT']);
		$this->setDir(dirname(__FILE__));
	}

	/*
	 * Test if we are doing a action and its valid.
	 * @param $action string the action to test for.
	*/
	public function testAction($action)
	{
		if
		(
			strpos('.', $action) === false &&
			strpos(':', $action) === false &&
			strpos('/', $action) === false &&
			strpos('http', $action) === false &&
			file_exists('/srv/smf.test/index_files/' . $action . '.php')
		)
			return true;
		else
			return false;
	}

	/*
	 * Do the action, testing should of been done before hand.
	 * @param $action string the action to run.
	*/
	public function doAction($action)
	{
		require_once('/srv/smf.test/index_files/' . $action . '.php');
		exit;
	}

	/*
	 * Set the base directory, usually $_SERVER['DOCUMENT_ROOT'].
	 * @param $base_dir string The base directory.
	*/
	public function setBaseDir($base_dir)
	{
		$this->base_dir = $base_dir;
	}

	/*
	 * Set the base to scan, usually dirname(__FILE__) (of this file).
	 * @param $base_dir string The directory to scan.
	*/
	public function setDir($dir)
	{
		$this->dir = $dir;
		$this->dir_title = str_replace($this->base_dir, '', $dir);
	}

	/*
	 * Add a pattern to the ignore list of files we skip.
	 * @param $files array/string The files we wish to ignore. If this isn't an array it is converted into one.
	*/
	public function addIgnore($files)
	{
		if (!is_array($files))
			$files = array($files);

		$this->ignore_files	= array_merge($files, $this->ignore_files);
	}

	/*
	 * Change the date string we are using.
	 * @param $files string The date string using the format from php.net/date.
	*/
	public function changeDateString($date)
	{
		$this->date_string = $date;
	}

	/*
	 * When using u*sort order methods we need a callback, which can be set here. This has to be set prior to using setOrder method.
	 * @param $callback string The callback function/.
	*/
	public function setOrderCallback($callback)
	{
		if (is_callable($callback))
			$this->sort_callback = $callback;
		else
			$this->sort_callback = null;
	}

	/*
	 * Set a sort order.  Custom functions should set a u*sort method and then a callback.
	 * @param $order string The *sort function to use.
	*/
	public function setOrder($order)
	{
		if (in_array($order, array('ksort', 'krsort', 'rsort', 'sort', 'asort', 'arsort')))
			$this->sort_method = $order;
		elseif (!empty($this->sort_callback) && is_callable($this->sort_callback) && in_array($order, 'usort', 'uksort', 'uasort'))
			$this->sort_method = $order;
		else
			$this->sort_method = 'krsort';
	}

	/*
	 * Actually read a directory. We do not transverse directorys here.
	 * We could use use scandir rather than opendir+readdir, but would still need to loop anyways.
	 * @return array(
	 *			$title String The name of the file with a capital first letter.
	 *			$href String The link to the file, this is relative to make it easier.
	 *			$desc String A Description of the file.
	 * );
	*/
	public function readDir()
	{
		clearstatcache();
 
		// Prepare for reading.
		$directory_array = array();

		// Open, Read, save.
		$handle = opendir($this->dir);
		while ($file = readdir($handle))
		{
			if (in_array($file, $this->ignore_files))
				continue;

			$modified = date($this->date_string, filemtime($this->dir . '/' . $file));
			$accessed = date($this->date_string, fileatime($this->dir . '/' . $file));

			$directory_array[$file] = array(
				'title' => ucfirst($file),
				'href' => $file,
				'desc' => 'Last Modified: ' . $modified . '<br />
					Last Accessed: ' . $accessed . '<br />',
			);
		}

		// Sort them.
		$func = $this->sort_method;
		if (!empty($this->sort_callback))
			$func($directory_array, $this->sort_callback);
		elseif (!empty($func))
			$func($directory_array);

		return $directory_array;
	}

	/*
	 * The template we use.
	 * This calls back the scSite class for its header, menu and footer.
	 * @return void No return, however output is executed at this point.
	*/
	public function template($files)
	{
		if (dirname($this->dir . '/../') != $this->base_dir && $this->dir != $this->base_dir)
			scSite::_()->addNavItem('../', 'Return to Previous Directory');

		if ($this->dir . '/../' != $this->base_dir && $this->dir != $this->base_dir)
			scSite::_()->addNavItem('/', 'Return Home');

		scSite::_()->templateHeader();

		echo '
		<div class="content">
			<div class="row">';

		$i = 0;
		foreach ($files as $file)
		{
			if ($i % 3 == 0 && $i != 0)
				echo '
			</div>
			<div class="row">';

			echo '
				<div class="span5">
					<h2><a href="', $file['href'], '">', $file['title'], '</a></h2>
					<div class="well">', $file['desc'] ,'</div>
				</div>';

			++$i;
		}

		echo '
			</div>
		</div>';

		scSite::_()->templateFooter();
	}
}