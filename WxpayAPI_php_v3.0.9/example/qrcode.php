<?php
error_reporting(E_ERROR);
require_once 'phpqrcode/phpqrcode.php';
$url = urldecode($_GET["data"]);
if(substr($url, 0, 6) == "weixin"){
	QRcode::png($url);
}else{
	 header('HTTP/1.1 404 Not Found');
}
