<?php
scSite::_()->doTest(str_replace('.php', '', basename(__FILE__)));

echo 'ord:<pre>', print_r(ord($_REQUEST['a']), true), '</pre>';
echo 'chr:<pre>', print_r(chr($_REQUEST['a']), true), '</pre>';