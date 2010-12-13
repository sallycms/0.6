<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

// ==== generic ================================================================

$lime->comment('Testing sly_Cache (generic)...');
$lime->isnt(sly_Cache::generateKey('1'), sly_Cache::generateKey(1), 'generateKey() takes the datatype into account');

// ==== sly_Cache_Blackhole ====================================================

$lime->comment('Testing sly_Cache (BabelCache_Blackhole)...');

$cache = sly_Cache::factory('BabelCache_Blackhole');
$lime->isa_ok($cache, 'BabelCache_Blackhole', 'factory() uses the given cache class');
$lime->ok($cache->set('foo', 'bar', 1), 'set() returns true');
$lime->is_deeply($cache->get('foo', 'bar', 2), 2, 'get() always returns the default');
$lime->ok(!$cache->exists('foo', 'bar'), 'exists() always returns false');

// ==== multiple strategies ====================================================

$implementations = array('Memory', 'Filesystem', 'APC', 'XCache', 'eAccelerator', 'Memcache', 'Memcached', 'ZendServer');

foreach ($implementations as $system) {
	$className = 'BabelCache_'.$system;

	if (!call_user_func(array($className, 'isAvailable'))) {
		$lime->comment('Skipping tests for '.$className.' (not available)...');
		continue;
	}

	$lime->comment('Testing sly_Cache ('.$className.')...');

	$cache = sly_Cache::factory($className);

	$cache->set('tests',           'foo', 'bar');
	$cache->set('tests.test.deep', 'foo2', null);

	$cache->flush('tests', true);

	$lime->ok(!$cache->exists('tests', 'foo'), 'flush() really flushes all values');
	$lime->ok(!$cache->exists('tests.test.deep', 'foo2'), 'flush() flushes recursively');

	$cache->set('tests2',           'foo', 'bar');
	$cache->set('tests2',           'foo1', 1);
	$cache->set('tests2',           'foo2', false);
	$cache->set('tests2.blub',      'foo3', true);
	$cache->set('tests2.test.deep', 'foo4', null);
	$cache->set('tests2.johnny',    'foo5', new stdClass());
	$cache->set('tests2',           'foo6', 3.41);

	$lime->is_deeply($cache->get('tests2', 'foo'), 'bar', 'set() can store strings');
	$lime->is_deeply($cache->get('tests2', 'foo1'), 1, 'set() can store integers');
	$lime->is_deeply($cache->get('tests2', 'foo2'), false, 'set() can store false');
	$lime->is_deeply($cache->get('tests2.blub', 'foo3'), true, 'set() can store true');
	$lime->is_deeply($cache->get('tests2.test.deep', 'foo4'), null, 'set() can store null');
	$lime->isa_ok($cache->get('tests2.johnny', 'foo5'), 'stdClass', 'set() can store objects');
	$lime->is_deeply($cache->get('tests2', 'foo6'), 3.41, 'set() can store floats');

	$lime->ok($cache->exists('tests2', 'foo6'), 'exists() works');

	$cache->flush('tests2', true);
}

unset($cache, $className, $implementations, $system);
