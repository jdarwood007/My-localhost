<?php
scSite::_()->doTest(str_replace('.php', '', basename(__FILE__)));

echo 'Encode : ' . base64_encode($_REQUEST['a']);
echo '

<hr />';
echo 'Decode : ' . base64_decode($_REQUEST['a']);

echo '
<hr />';

echo '</pre> Decode Serarlized: <pre>' . print_r(unserialize(base64_decode($_REQUEST['a'])), true);