<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

$args = isset($_SERVER['argv']) ? $_SERVER['argv'] : array();

if (count($args) < 3) {
	die('Usage: php '.$args[0].' src-locale dst-locale');
}

$src = trim($args[1]);
$dst = trim($args[2]);

$src = preg_replace('#[^a-z0-9_-]#i', '', $src);
$dst = preg_replace('#[^a-z0-9_-]#i', '', $dst);

chdir('..');
define('BASE', realpath('sally/backend/lang'));

if (!file_exists(BASE.'/'.$src.'.yml')) {
	die('Source language file ('.$src.'.yml) could not be found.');
}

require 'sally/core/lib/sly/Exception.php';
require 'sally/core/lib/sly/Util/Directory.php';
require 'sally/core/lib/sfYaml/sfYaml.php';
require 'sally/core/lib/sfYaml/sfYamlInline.php';

$base      = new sly_Util_Directory(BASE);
$files     = $base->listRecursive(false, true);
$sources   = array();
$len       = strlen($src.'.yml');
$known     = array();
$originals = array();

foreach ($files as $file) {
	if (substr($file, -8) == '.rebuilt') {
		continue;
	}

	$short    = substr($file, strlen(BASE) + 1);
	$short    = str_replace('\\', '/', $short);
	$isSource = substr($file, -$len) == $src.'.yml';
	$data     = sfYaml::load($file);

	if ($isSource) {
		print ' SOURCE: '.$short.PHP_EOL;
		$sources[] = $file;
		$originals = array_merge($originals, $data);
	}
	else {
		print '  KNOWN: '.$short.': '.count($data).' items.'.PHP_EOL;
		$known = array_merge($known, $data);
	}

}

print PHP_EOL;
$dstName = $dst.'.yml.rebuilt';

foreach ($sources as $sourceName) {
	$targetName = dirname($sourceName).'/'.$dstName;
	copy($sourceName, $targetName);

	$targetName = realpath($targetName);
	$short      = substr($targetName, strlen(BASE) + 1);
	print str_pad('REBUILD: '.str_replace('\\', '/', $short).'...', 60, ' ');

	$source = sfYaml::load($targetName);

	// from now on, we work on a normal key:value-file, to keep the comments and whitespace.

	$lines  = file($targetName);
	$output = fopen($targetName, 'w');
	$stats  = array(0,0);

	foreach ($lines as $line) {
		if (strpos($line, ':') === false) {
			fwrite($output, $line);
			continue;
		}

		list($key, $value) = explode(':', $line, 2);

		$trimmedKey = trim($key);
		$old        = isset($known[$trimmedKey]) ? $known[$trimmedKey] : 'translate:'.$trimmedKey;
		$original   = $originals[$trimmedKey];
		$comment    = !isset($known[$trimmedKey]) ? ' # '.sfYamlInline::dump($original) : '';

		fwrite($output, $trimmedKey.': '.sfYamlInline::dump($old)."$comment\n");

		if (isset($known[$trimmedKey])) {
			$stats[0]++;
		}
		else {
			$stats[1]++;
		}
	}

	fclose($output);

	printf('%4d / %4d / %4d', $stats[0], $stats[1], array_sum($stats));
	print PHP_EOL;
}

print PHP_EOL;
print 'Done.';
