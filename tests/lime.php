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

$lime = new lime_test(null, array('force_colors' => isset($_SERVER['REMOTE_ADDR'])));

$lime->info('SallyCMS Unit Tests');
$lime->info('= = = = = = = = = =');
$lime->info('Let\'s see how many bugs we can find :-)');

foreach ($registration->files as $filename) {
	include $filename;
}
