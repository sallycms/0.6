<?php

$lime->comment('Testing sly_Util_Array...');

$obj = new sly_Util_Array(array());

$obj->set('FOO', 'BAR');
$lime->is($obj->get('FOO'),   'BAR',                 'get(FOO)');
$lime->is($obj->get('FOO/'),  'BAR',                 'get(FOO/)');
$lime->is($obj->get('/FOO'),  'BAR',                 'get(/FOO)');
$lime->is($obj->get('/FOO/'), 'BAR',                 'get(/FOO/)');
$lime->is($obj->get(''),      array('FOO' => 'BAR'), 'get()');
$lime->is($obj->get('//'),    array('FOO' => 'BAR'), 'get(//)');

$obj->set('FOO', 'BAR_NEU');
$lime->is($obj->get('FOO'), 'BAR_NEU', 'get(FOO) (altered value)');

// crashes PHP
// $obj->set('FOO/bar', true);
// $lime->is(true, $obj->get('FOO/bar'), 'get(FOO/bar)');

$obj->set('hallo', 12.2);
$lime->isa_ok($obj->get('hallo'), 'double', 'Does get() return the correct type?');

$obj->set('hallo', true);
$lime->isa_ok($obj->get('hallo'), 'boolean', 'Does get() return the correct type?');

$obj->set('hallo', new stdClass());
$lime->isa_ok($obj->get('hallo'), 'stdClass', 'Does get() return the correct type?');

$lime->ok($obj->has('hallo'), 'has(hallo)');
$obj->remove('hallo');
$lime->ok(!$obj->has('hallo'), 'has(hallo) ... not!');
$lime->ok(!$obj->has('does_not_exists'), 'has(does_not_exists) ... not!');
$lime->is($obj->get('does_not_exists'), null, 'get(does_not_exists) === null + Notice');

$obj->set('foo/bar', 1);
$obj->set('foo/muh', 2);

$lime->is($obj->get('foo'), array('bar' => 1, 'muh' => 2), 'get(foo) -> assoc array');

$obj->set('muh', array('hallo' => 'welt', 'xy' => array('abc' => 'yes')));
$lime->is('yes', $obj->get('muh/xy/abc'), 'get deeply nested');
$lime->is($obj->get('muh/xy'), array('abc' => 'yes'), 'get deeply nested');

$obj->remove('muh/xy');
$lime->is($obj->get('muh'), array('hallo' => 'welt'), 'get deeply nested after removing deeply nested element');

$obj->set('muh/test/loki', 5);
$lime->is($obj->get('muh/test'), array('loki' => 5), 'get deeply nested');
$lime->is($obj->get('/muh///test///'), array('loki' => 5), 'get deeply nested (strange formatting)');
$lime->is($obj->get('///muh/test'), array('loki' => 5), 'get deeply nested (strange formatting)');

$obj->set(5, 5);
$lime->is($obj->get(5), 5, 'using numbers as keys');

unset($testCasesTrue, $testCasesFalse, $obj);
