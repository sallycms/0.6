<?php

$lime->comment('Testing sly_Util_Array...');

$obj = new sly_Util_Array(array());

$obj->set('FOO', 'BAR');
$lime->is($obj->get('FOO'),   'BAR',                 'get() handles the vanilla case');
$lime->is($obj->get('FOO/'),  'BAR',                 'get() removes trailing slashes');
$lime->is($obj->get('/FOO'),  'BAR',                 'get() removes leading slashes');
$lime->is($obj->get('/FOO/'), 'BAR',                 'get() removes both leading and trailing slashes');
$lime->is($obj->get(''),      array('FOO' => 'BAR'), 'get() handles an empty key');
$lime->is($obj->get('//'),    array('FOO' => 'BAR'), 'get() correctly gets the empty key');

$obj->set('FOO', 'BAR_NEU');
$lime->is($obj->get('FOO'), 'BAR_NEU', 'get() returns the altered value');

try {
	$obj->set('FOO/bar', true);
	$lime->fail('set() should not have been able to set this value.');
}
catch (sly_Exception $e) {
	$lime->pass('set() correctly throws an sly_Exception if trying to convert a scalar to an array.');
}
catch (Exception $e) {
	$lime->fail('set() incorrectly throws an Exception ('.get_class($e).') if trying to convert a scalar to an array.');
}

$obj->set('hallo', 12.2);
$lime->isa_ok($obj->get('hallo'), 'double', 'get() returns a double if given a double');

$obj->set('hallo', true);
$lime->isa_ok($obj->get('hallo'), 'boolean', 'get() returns a boolean if given a boolean');

$obj->set('hallo', new stdClass());
$lime->isa_ok($obj->get('hallo'), 'stdClass', 'get() returns an object if given an object');

$lime->ok($obj->has('hallo'), 'has() returns true for an existing key');
$obj->remove('hallo');
$lime->ok(!$obj->has('hallo'), 'has() returns false for a removed key');
$lime->ok(!$obj->has('does_not_exists'), 'has() returns false for a non-existing key');
$lime->is($obj->get('does_not_exists'), null, 'get() returns null for a non-existing key and hopefully throws a notice');
$lime->is($obj->get('hallo/does_not_exists'), null, 'get() returns null for a non-existing subkey and hopefully throws a notice');

$obj->set('foo/bar', 1);
$obj->set('foo///muh/', 2);

$lime->is($obj->get('foo'), array('bar' => 1, 'muh' => 2), 'get() returns the complete subtree');
$lime->is($obj->get('foo/muh'), 2, 'get() can read the value set @ foo///muh/');

$obj->set('muh', array('hallo' => 'welt', 'xy' => array('abc' => 'yes')));
$lime->is('yes', $obj->get('muh/xy/abc'), 'get() deeply nested value');
$lime->is($obj->get('muh/xy'), array('abc' => 'yes'), 'get() deeply nested value');

$obj->remove('muh///xy/');
$lime->is($obj->get('muh'), array('hallo' => 'welt'), 'get() deeply nested after removing deeply nested element');

$obj->set('muh/test/loki', 5);
$lime->is($obj->get('muh/test'), array('loki' => 5), 'get() deeply nested value');
$lime->is($obj->get('/muh///test///'), array('loki' => 5), 'get() deeply nested (strange formatting)');
$lime->is($obj->get('///muh/test'), array('loki' => 5), 'get() deeply nested (strange formatting)');

$obj->set(5, 5);
$lime->is($obj->get(5), 5, 'get() using numbers as keys');

$obj->remove('////');
$lime->is($obj->get(''), array(), 'remove() can completely wipe the object');

unset($testCasesTrue, $testCasesFalse, $obj);
