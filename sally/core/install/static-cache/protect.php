<?php

// get client encoding (attention: use the one set by htaccess for mod_headers awareness)

$enc = trim($_SERVER['HTTP_ENCODING_CACHEDIR'], '/');

// check file

$file = isset($_GET['file']) ? $_GET['file'] : '';
define('FILE', $file);
define('ENC', $enc);

$realfile = realpath('protected/'.$enc.'/'.$file);   // path or false
$index    = dirname(realpath(__FILE__));             // /var/www/home/cust/data/dyn/public/sally/static-cache/

// append '/' if missing
if ($index[mb_strlen($index)-1] === DIRECTORY_SEPARATOR) {
	$index .= DIRECTORY_SEPARATOR;
}

// file not found?
if ($realfile === false) {
	header('HTTP/1.0 404 Not Found');
	die;
}

// file outside of cache dir?
if (mb_substr($realfile, 0, mb_strlen($index)) !== $index) {
	header('HTTP/1.0 403 Forbidden');
	die;
}

// jump to sally root directory
chdir('___JUMPER___');

// include project specific access rules
ob_start();
$allowAccess = false;
include 'develop/checkpermission.php';
$errors = ob_get_clean();

// jump back (at 88 mph)
chdir(dirname(__FILE__));

// disable sending the file when any kind of errors occured
if (!empty($errors)) {
	$allowAccess = false;
}

if (!$allowAccess) {
	header('HTTP/1.0 403 Forbidden');
	die;
}

$file  = basename(FILE);
$pos   = mb_strrpos($file, '.');
$ext   = strtolower($pos === false ? $file : mb_substr($file, $pos + 1));
$types = array(
	'css'  => 'text/css',
	'js'   => 'text/javascript',
	'ico'  => 'image/x-icon',
	'jpg'  => 'image/jpeg',
	'jpeg' => 'image/jpeg',
	'png'  => 'image/png',
	'gif'  => 'image/gif',
	'swf'  => 'application/x-shockwave-flash'
);

if (!isset($mime)) {
	$mime = (isset($types[$ext]) ? $types[$ext] : 'application/octet-stream').'; charset=UTF-8';
}

header('Content-Type: '.$mime);

if (ENC !== 'plain') {
	header('Content-Encoding: '.ENC);
}

readfile($realfile);
