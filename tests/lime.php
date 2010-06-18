<?php

require 'addons/sally_bootstrap.php';
require 'lime/lime.php';

$registration = new lime_registration();
$registration->extension = '.lime.php';
$registration->register_dir(dirname(__FILE__).'/tests/lime');

if (isset($_SERVER['REMOTE_ADDR'])) {
	header('Content-Type: text/html; charset=UTF-8');
	print '<pre>';
}

$lime = new lime_test();

foreach ($registration->files as $filename) {
	include $filename;
}
