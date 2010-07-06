<?php

require 'addons/sally_bootstrap.php';
require 'lime/lime.php';
require 'coverage.php';

$limeOptions = array('force_colors' => isset($_SERVER['REMOTE_ADDR']));

define('SLY_TEST_LIME_IS_XML', isset($argv[1]) && $argv[1] == 'xml');
define('SLY_TEST_LIME_XDEBUG', function_exists('xdebug_start_code_coverage'));

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

if (SLY_TEST_LIME_XDEBUG) {
	xdebug_start_code_coverage(XDEBUG_CC_DEAD_CODE | XDEBUG_CC_UNUSED);
}

foreach ($registration->files as $filename) {
	include $filename;
}

if (SLY_TEST_LIME_XDEBUG) {
	$exportDir = 'coverage_reports';
	$baseDir   = realpath(dirname(__FILE__).'/../redaxo/include');
	@mkdir($exportDir, 0777, true);

	$code_coverage = new code_coverage($exportDir.'/raw.php');
	$code_coverage->saveRawData();
	$code_coverage->createReports($exportDir, $baseDir);
}

if (SLY_TEST_LIME_IS_XML) {
	print $lime->to_xml();
}
