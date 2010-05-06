<?php

error_reporting(0);
ini_set('display_errors', 'Off');

if (empty($_GET['f'])) {
	die('Keine Datei angegeben.');
}

$projectBase = rtrim(realpath('../../'), '/\\').'/';
$lastMTime = empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? 0 : strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);

if ($lastMTime > 0 && $lastMTime <= time()) {
	$file      = (string) $_GET['f'];
	$cacheFile = $projectBase.'data/dyn/internal/sally/css-cache/mtimes.txt';
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

$_GET['wv_f'] = $_GET['f']; // Wir brauchen diesen Wert für's Caching...

chdir($projectBase.'redaxo/include/lib/Scaffold');
include 'index.php';

// Cache aufräumen, wenn er zu groß geworden ist...
// Da in der remove()-Methode des Caches nur der Hash der angeforderten Datei
// ankommt, können wir die Liste dort leider nicht aufräumen.

if (isset($caches) && count($caches) > 50) {
	unlink($cacheFile);
}
