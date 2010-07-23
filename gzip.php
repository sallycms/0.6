<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

error_reporting(0);
ini_set('display_errors', 'off');

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

// E-Tag-Behandlung

$etag = !empty($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : '';
$md5  = substr(md5_file($file), 0, 10);

if ($etag == '"'.$md5.'"') { // ETag steht in "..."
	header('HTTP/1.1 304 Not Modified');
	exit();
}

// Last-Modified-Since-Behandlung

$last  = !empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : -1;
$mtime = filemtime($file);

if ($last > 0 && $last == $mtime) {
	header('HTTP/1.1 304 Not Modified');
	exit();
}

// gzip starten

if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
	ob_start('ob_gzhandler');
}

// Content-Type senden

$extension = strtolower(substr($file, strrpos($file, '.')));
if (isset($mimetypes[$extension])) header('Content-Type: '.$mimetypes[$extension]);

// Headers

header('Last-Modified: '.date('r', $mtime));
header('ETag: "'.$md5.'"');

if (!empty($_GET['forever'])) {
	header('Expires: Thu, 15 Apr '.(date('Y')+2).' 20:00:00 GMT');
}

// Content senden

readfile($file);
