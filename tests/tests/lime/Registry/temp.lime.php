<?php

$lime->comment('Testing sly_Registry_Temp...');

$reg = sly_Registry_Temp::getInstance();

$reg->set('FOO', 'BAR');
$lime->is($reg->get('FOO'),   'BAR',                 'get(FOO)');
$lime->is($reg->get('FOO/'),  'BAR',                 'get(FOO/)');
$lime->is($reg->get('/FOO'),  'BAR',                 'get(/FOO)');
$lime->is($reg->get('/FOO/'), 'BAR',                 'get(/FOO/)');
$lime->is($reg->get(''),      array('FOO' => 'BAR'), 'get()');
$lime->is($reg->get('//'),    array('FOO' => 'BAR'), 'get(//)');

$reg->set('FOO', 'BAR_NEU');
$lime->is($reg->get('FOO'), 'BAR_NEU', 'get(FOO) (altered value)');

// crashes PHP
// $reg->set('FOO/bar', true);
// $lime->is(true, $reg->get('FOO/bar'), 'get(FOO/bar)');

$reg->set('hallo', 12.2);
$lime->isa_ok($reg->get('hallo'), 'double', 'Does get() return the correct type?');

$reg->set('hallo', true);
$lime->isa_ok($reg->get('hallo'), 'boolean', 'Does get() return the correct type?');

$reg->set('hallo', new stdClass());
$lime->isa_ok($reg->get('hallo'), 'stdClass', 'Does get() return the correct type?');

$lime->ok($reg->has('hallo'), 'has(hallo)');
$reg->remove('hallo');
$lime->ok(!$reg->has('hallo'), 'has(hallo) ... not!');
$lime->ok(!$reg->has('does_not_exists'), 'has(does_not_exists) ... not!');
$lime->is($reg->get('does_not_exists'), null, 'get(does_not_exists) === null + Notice');

$reg->set('foo/bar', 1);
$reg->set('foo/muh', 2);

$lime->is($reg->get('foo'), array('bar' => 1, 'muh' => 2), 'get(foo) -> assoc array');

$reg->set('muh', array('hallo' => 'welt', 'xy' => array('abc' => 'yes')));
$lime->is('yes', $reg->get('muh/xy/abc'), 'get deeply nested');
$lime->is($reg->get('muh/xy'), array('abc' => 'yes'), 'get deeply nested');

$reg->remove('muh/xy');
$lime->is($reg->get('muh'), array('hallo' => 'welt'), 'get deeply nested after removing deeply nested element');

$reg->set('muh/test/loki', 5);
$lime->is($reg->get('muh/test'), array('loki' => 5), 'get deeply nested');
$lime->is($reg->get('/muh///test///'), array('loki' => 5), 'get deeply nested (strange formatting)');
$lime->is($reg->get('///muh/test'), array('loki' => 5), 'get deeply nested (strange formatting)');

$reg->set(5, 5);
$lime->is($reg->get(5), 5, 'using numbers as keys');

unset($reg);
