<?php
$file      = realpath(@$_GET['file']);
$mimetypes = array(
	'.css' => 'text/css',
	'.js'  => 'text/javascript' // application/javascript w�re richtiger, wird aber nicht �berall unterst�tzt (http://en.wikipedia.org/wiki/Client-side_JavaScript)
);

if (!file_exists($file)) die;
$script_path = pathinfo(realpath(__FILE__), PATHINFO_DIRNAME);
$file_path = pathinfo($file, PATHINFO_DIRNAME);
if (substr($file_path, 0, strlen($script_path)) !== $script_path) die;
if (substr($file,-3) != '.js' && substr($file,-4) != '.css') die;
if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) ob_start('ob_gzhandler');

$extension = strtolower(substr($file, strrpos($file, '.')));
header('X-Test: '.$extension);
if (isset($mimetypes[$extension])) header('Content-Type: '.$mimetypes[$extension]);

readfile($file);
?>