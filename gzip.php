<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/* On pages with heavy load, piping files through this script may cause
 * high CPU usage. To avoid the re-compression on every hit, this script can
 * cache the gzippped files. Just switch this constant to true and make sure
 * that this script will be able to create data/dyn/internal/gzip-cache/ to
 * store it's files.
 */

define('CACHE_FILES', false);

error_reporting(0);
ini_set('display_errors', 'off');

$file = realpath(@$_GET['file']);
if ($file === false) die;

// Prüfen, ob der Request in ein erlaubtes Verzeichnis unterhalb dieser Datei abzielt.
$script_path = pathinfo(realpath(__FILE__), PATHINFO_DIRNAME);
$file_path   = pathinfo($file, PATHINFO_DIRNAME);
if (substr($file_path, 0, strlen($script_path)) !== $script_path) die;

$mimetypes = array(
	'.css'  => 'text/css',
	'.js'   => 'text/javascript', // application/javascript wäre richtiger, wird aber nicht überall unterstützt (http://en.wikipedia.org/wiki/Client-side_JavaScript)
	'.png'  => 'image/png',
	'.jpg'  => 'image/jpeg',
	'.jpeg' => 'image/jpeg',
	'.gif'  => 'image/gif'
);

if (!file_exists($file)) die;
if (!preg_match('#\.(css|js|png|jpg|jpeg|gif)$#i', $file)) die;

// Last-Modified-Since-Behandlung

$last  = !empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : -1;
$mtime = filemtime($file);

if ($last > 0 && $last == $mtime) {
	header('HTTP/1.1 304 Not Modified');
	exit();
}

// Content-Type senden

$extension = strtolower(substr($file, strrpos($file, '.')));
if (isset($mimetypes[$extension])) header('Content-Type: '.$mimetypes[$extension]);

// Headers

header('Last-Modified: '.date('r', $mtime));
header('Expires: Thu, 15 Apr '.(date('Y')+1).' 20:00:00 GMT');

// gzip starten

if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
	if (CACHE_FILES) {
		$dir       = 'data/dyn/internal/gzip-cache';
		$cacheFile = $dir.'/'.md5($file).$extension.'.gz';

		if (is_dir(dirname($dir)) && !is_dir($dir)) {
			mkdir($dir, 0777);
			clearstatcache();
		}

		if (is_dir($dir)) {
			if (!file_exists($cacheFile)) {
				$out = gzopen($cacheFile, 'wb');
				$in  = fopen($file, 'rb');

				while (!feof($in)) {
					$buf = fread($in, 4096);
					gzwrite($out, $buf);
				}

				fclose($in);
				gzclose($out);
			}

			$file = $cacheFile;
			header('Content-Encoding: gzip');
			header('Content-Length: '.filesize($file));
		}
		else {
			ob_start('ob_gzhandler');
		}
	}
	else {
		ob_start('ob_gzhandler');
	}
}

readfile($file);
