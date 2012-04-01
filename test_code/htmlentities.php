<?php
scSite::_()->doTest(str_replace('.php', '', basename(__FILE__)));

echo 'Encode : ' . htmlentities($_REQUEST['a']);

if ($_REQUEST['a'] == '1')
	echo '<pre>', print_r(get_html_translation_table(), true), '</pre>';
