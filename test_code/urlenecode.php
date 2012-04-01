<?php
scSite::_()->doTest(str_replace('.php', '', basename(__FILE__)));

echo '<pre>', print_r(urlencode($_REQUEST['a']), true), '</pre>';
echo '<pre>', print_r(urldecode($_REQUEST['a']), true), '</pre>';