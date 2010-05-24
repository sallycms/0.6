<?php

// Jede getätigte Ausgabe würde von SnapTest als Fehlerausgabe
// interpretiert werden. Also sind wir ganz ruhig...

define('SLY_IS_TESTING', true);     /// boolean  für Testläufe muss hier true stehen.
define('SLY_TESTING_USER_ID', 1);   /// int      die ID des Users, der eingeloggt sein soll

$sallyRoot = realpath(dirname(__FILE__).'../../../');
define('SLY_TESTING_ROOT', $sallyRoot);

require SLY_TESTING_ROOT.'/redaxo/index.php';

$config = array(
	'name'          => 'Sally Backend Bootstrap',
	'version'       => '1.0',
	'author'        => 'webvariants GbR',
	'description'   => 'startet das Backend von Sally'
);
