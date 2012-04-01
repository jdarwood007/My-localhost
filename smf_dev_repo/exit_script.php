<?php

// Get the contents man.
$contents = ob_get_contents();
ob_clean();

// If we are switching between databases correct the urls.
if ($boardurl != 'http://smfMaster.test')
	$contents = str_replace('http://smfMaster.test', $boardurl, $contents);

// Only do the Theme changer in certain cases.
if (!isset($_REQUEST['action']) || !in_array($_REQUEST['action'], array('xmlhttp', '.xml', 'viewsmfile', 'viewquery', 'verificationcode', 'suggest', 'smstats', 'quickmod', 'quickmod2', 'quotefast', 'openidreturn', 'jsoption', 'jsmodify', 'jseditor')))
	themechanger($contents);

// The true execution time.
$time = round($GLOBALS['true_end'] - $GLOBALS['true_start'], 3);
preg_match('~<p>Page created in ([\d.]+) seconds with (\d+) queries.</p>~i', $contents, $matches);
$contents = str_replace($matches[0], $matches[0] . '<p>Page REALLY created in ' . ($time) . ' seconds</p>', $contents);

echo $contents;
exit;

// A nice little theme changer.
function themechanger(&$contents)
{
	global $smcFunc, $context, $user_info;

	if (empty($_SERVER['REQUEST_URL']))
		return;

	// Figure out our location easily.
	$location = preg_replace('~[;|?]theme=(\d+)~i', '', $_SERVER['REQUEST_URL']);
	if (strpos($location, '?') !== false)
		$location = $location . ';';
	else
		$location = $location . '?';

	// Is the current theme, wrong!
	if (isset($_REQUEST['theme']))
		$user_info['theme'] = (int) $_REQUEST['theme'];
	elseif (!empty($_SESSION['id_theme']))
		$user_info['theme'] = (int) $_SESSION['id_theme'];

	// Start.
	$thestring = '
<div id="svn_theme_changer" style="display: none; position: absolute; top:1em; left: 40em; color:red; z-index:1;">
	<form method="get" action="javascript://void;">
		<select name="theme" onchange="location=\''. $location . 'theme=\' + this.options[this.selectedIndex].value">';

	// Go database!
	$request = $smcFunc['db_query']('', '
		SELECT value, id_theme
		FROM {db_prefix}themes
			WHERE id_theme != {int:no_theme}
			AND id_member = {int:guest_id}
			AND variable = {string:theme_name}',
		array(
			'no_theme' => 0,
			'guest_id' => 0,
			'theme_name' => 'name',
	));
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$thestring .= '
			<option value="' . $row['id_theme'] . '"' . ($user_info['theme'] == $row['id_theme'] ? ' selected="selected"' : ''). '>' . $row['value'] . '</option>';

	// We don't trust whether JQuery or CSS is present here. Hard code it all.
	$thestring .= '
		</select>
	</form>
</div>
<div style="position: absolute; top:0; right: 0; color:red; z-index:999; dislay: none;">
	<div style="float: right; width: 4em;">
		<span title="Theme changer Toggle">
			<a href="#1" id="svn_theme_changer_show" style="float: right; padding-right: 1em; display: block;"  onclick="document.getElementById(\'svn_theme_changer\').style.display = \'\'; document.getElementById(\'svn_theme_changer_show\').style.display = \'none\'; document.getElementById(\'svn_theme_changer_hide\').style.display = \'\';"><img src="http://smfMaster.test/Themes/default/images/smiley_select_spot.gif" alt="expand" /></a>
			<a id="svn_theme_changer_hide" style="float: right;  padding-right: 1em; display: none;" href="#2" onclick="document.getElementById(\'svn_theme_changer\').style.display = \'none\'; document.getElementById(\'svn_theme_changer_show\').style.display = \'\'; document.getElementById(\'svn_theme_changer_hide\').style.display = \'none\';"><img src="http://smfMaster.test/Themes/default/images/board_select_spot.gif" alt="collapse" /></a>
		</span>
		<br /><br />
		<a title="Destory the session" style="float: right; padding-right: 1em;" href="' . $location . '&destory_session&secs=' . $context['session_id'] . '\'"><img src="http://smfMaster.test/Themes/default/images/warning_mute.gif" alt="collapse" /></a>
	</div>
</div>
';
	
	// Hack out and display the few themes we have quickly.
	$contents = preg_replace('~<body([^>]+)>~', '<body\1>' . $thestring, $contents);

	return $contents;
}
