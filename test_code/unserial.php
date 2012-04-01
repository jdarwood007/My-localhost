<?php
scSite::_()->doTest(str_replace('.php', '', basename(__FILE__)));

echo '<pre>', print_r(unserialize($_REQUEST['a']), true), '</pre>';
echo '<pre>', print_r(serialize($_REQUEST['a']), true), '</pre>';