<?php

require 'addons/sally_bootstrap.php';
require 'lime/lime.php';

$limeOptions = array('force_colors' => isset($_SERVER['REMOTE_ADDR']));

define('SLY_TEST_LIME_IS_XML', isset($argv[1]) && $argv[1] == 'xml');

if (SLY_TEST_LIME_IS_XML) {
	require 'lime/lime.blackhole.php';
	$limeOptions['output'] = new lime_output_blackhole();
}

$registration = new lime_registration();
$registration->extension = '.lime.php';
$registration->register_dir(dirname(__FILE__).'/tests/lime');

if (!SLY_TEST_LIME_IS_XML && isset($_SERVER['REMOTE_ADDR'])) {
	header('Content-Type: text/html; charset=UTF-8');
	print '<pre>';
}

$lime = new lime_test(null, $limeOptions);

$lime->info('SallyCMS Unit Tests');
$lime->info('= = = = = = = = = =');
$lime->info('Let\'s see how many bugs we can find :-)');

error_reporting(0);

foreach ($registration->files as $filename) {
	include $filename;
}

if (SLY_TEST_LIME_IS_XML) {
	print $lime->to_xml();
}
