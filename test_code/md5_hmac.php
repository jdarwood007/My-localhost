<?php
scSite::_()->doTest(str_replace('.php', '', basename(__FILE__)), 2);

if (!function_exists('md5_hmac'))
{
	function md5_hmac($data, $key)
	{
		if (strlen($key) > 64)
			$key = pack('H*', md5($key));
		$key  = str_pad($key, 64, chr(0x00));

		$k_ipad = $key ^ str_repeat(chr(0x36), 64);
		$k_opad = $key ^ str_repeat(chr(0x5c), 64);

		return md5($k_opad . pack('H*', md5($k_ipad . $data)));
	}
}

echo 'md5_hmac hashed string : ' . md5_hmac($_REQUEST['a'], $_REQUEST['n'][1]);