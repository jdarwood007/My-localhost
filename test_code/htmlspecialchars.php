<?php
scSite::_()->doTest(str_replace('.php', '', basename(__FILE__)));

echo '<pre>', print_r(htmlspecialchars($_REQUEST['a']), true), '</pre>';
