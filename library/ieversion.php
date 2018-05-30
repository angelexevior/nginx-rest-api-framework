<?php
$u = $_SERVER['HTTP_USER_AGENT'];

$isIE7  = (bool)preg_match('/msie 7./i', $u );
$isIE8  = (bool)preg_match('/msie 8./i', $u );
$isIE9  = (bool)preg_match('/msie 9./i', $u );
$isIE10 = (bool)preg_match('/msie 10./i', $u );

?>
