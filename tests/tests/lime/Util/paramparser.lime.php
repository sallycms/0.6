<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

$lime->comment('Testing sly_Util_ParamParser...');

$filename = dirname(__FILE__).'/___tmp__template.php';
$testfile = <<<TESTFILE
<?php

print "Hallo Welt!";

/**
 * Dieses Template ist ein Beispiel. Bla bla bla.
 *
 * @sly
 * @sly test_boolean true
 * @sly test_int     45
 * @sly test_double  194.234
 * @sly test_array   [foo, bar, 34, true]
 * @sly test_hash    {name: blub, second: 34}
 * @sly invalid      {foo:}
 * @sly test_string  Dies ist nur ein kleiner String. Nichts besonderes.
 *
 * Hier folgt weiterer Text.
 *
 * @sly second_group reached!
 *
 */

\$x = 4;
print \$x + 5;
TESTFILE;

file_put_contents($filename, $testfile);
unset($testfile);

$parser = new sly_Util_ParamParser($filename);
$params = array(
		'test_boolean' => true,
		'test_int'     => 45,
		'test_double'  => 194.234,
		'test_array'   => array('foo', 'bar', 34, true),
		'test_hash'    => array('name' => 'blub', 'second' => 34),
		'invalid'      => '{foo:}',
		'test_string'  => 'Dies ist nur ein kleiner String. Nichts besonderes.',
		'second_group' => 'reached!'
);

// getParams() soll eine Warning werfen
$lime->is_deeply(@$parser->get(), $params, 'get() recognizes all parameters');
$lime->is_deeply($parser->get('test_double'), 194.234, 'get() returns the correct value');
$lime->is_deeply($parser->get('missing', 'mydefault'), 'mydefault', 'get() correctly returns the default value for non-existing params');

// Sicherstellen, dass die Datei nicht bei jedem Aufruf neu geparsed wird.

$testfile = <<<TESTFILE
<?php

print "Hallo Welt!";

/**
 * @sly test_boolean false
 */

\$x = 5;
TESTFILE;

file_put_contents($filename, $testfile);
unset($testfile);

$lime->is_deeply($parser->get('test_boolean'), true, 'get() correctly caches the results');

// Überprüfen, ob ein neuer Parser (kein Singleton!) die frischen Werte erfasst.

$parser = new sly_Util_ParamParser($filename);
$params = array('test_boolean' => false);

$lime->is_deeply($parser->get(), $params, '__construct() creates a fresh object');
$lime->is_deeply($parser->get('test_boolean'), false, 'get() correctly returns the new value');

unlink($filename);
unset($parser, $filename);
