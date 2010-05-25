<?php

require 'addons/sally_bootstrap.php';
require 'lime/lime.php';

$registration = new lime_registration();
$registration->extension = '.lime.php';
$registration->register_dir(dirname(__FILE__).'/tests/lime');

foreach ($registration->files as $filename) {
	include $filename;
}
