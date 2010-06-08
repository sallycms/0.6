<?php

error_reporting(0);
ini_set('display_errors', 'Off');

if (empty($_GET['f'])) {
	die('Keine Datei angegeben.');
}

$projectBase = rtrim(realpath('../../'), '/\\').'/';
$cacheDir    = $projectBase.'data/dyn/internal/sally/css-cache';
$lastMTime   = empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? 0 : strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);

if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);

if ($lastMTime > 0 && $lastMTime <= time()) {
	$file      = (string) $_GET['f'];
	$cacheFile = $cacheDir.'/mtimes.txt';
	$caches    = file_exists($cacheFile) ? file($cacheFile) : array();
	$now       = time();
	$lifetime  = 3600;
	
	foreach ($caches as $line) {
		list ($filename, $mtime) = explode(':', trim($line));
		
		if ($filename == $file && ($mtime + $lifetime) > time()) {
			header('HTTP/1.1 304 Not Modified');
			header('Last-Modified: '.gmdate('D, d M Y H:i:s T', $mtime));
			header('Expires: '.gmdate('D, d M Y H:i:s T', $mtime + $lifetime));
			header('Cache-Control: no-cache, max-age='.$lifetime);
			exit;
		}
	}
}

// Wir brauchen diesen Wert für's Caching...
$_GET['wv_f'] = substr(preg_replace('#[\x00-\x1F]#', '', $_GET['f']), 0, 100);

chdir($projectBase.'redaxo/include/lib/Scaffold');
include 'index.php';

// Cache aufräumen, wenn er zu groß geworden ist...
// Da in der remove()-Methode des Caches nur der Hash der angeforderten Datei
// ankommt, können wir die Liste dort leider nicht aufräumen.

if (isset($caches) && count($caches) > 50) {
	unlink($cacheFile);
}
