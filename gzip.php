<?php
$file = realpath(@$_GET['file']);
if ($file === false) die;

// Prüfen, ob der Request in ein erlaubtes Verzeichnis unterhalb dieser Datei abzielt.
$script_path = pathinfo(realpath(__FILE__), PATHINFO_DIRNAME);
$file_path   = pathinfo($file, PATHINFO_DIRNAME);
if (substr($file_path, 0, strlen($script_path)) !== $script_path) die;

$mimetypes = array(
	'.css' => 'text/css',
	'.js'  => 'text/javascript' // application/javascript wäre richtiger, wird aber nicht überall unterstützt (http://en.wikipedia.org/wiki/Client-side_JavaScript)
);

if (!file_exists($file)) die;
if (substr($file,-3) != '.js' && substr($file,-4) != '.css') die;
if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) ob_start('ob_gzhandler');

$extension = strtolower(substr($file, strrpos($file, '.')));
if (isset($mimetypes[$extension])) header('Content-Type: '.$mimetypes[$extension]);

readfile($file);
