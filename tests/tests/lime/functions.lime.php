<?php

$lime->comment('Testing global Sally functions (sly_*)...');

// sly_makeArray()

$lime->is(sly_makeArray(null),         array(),      'sly_makeArray(null)');
$lime->is(sly_makeArray(1),            array(1),     'sly_makeArray(1)');
$lime->is(sly_makeArray(true),         array(true),  'sly_makeArray(true)');
$lime->is(sly_makeArray(false),        array(false), 'sly_makeArray(false)');
$lime->is(sly_makeArray(array()),      array(),      'sly_makeArray(array())');
$lime->is(sly_makeArray(array(1,2,3)), array(1,2,3), 'sly_makeArray(array(1,2,3))');

// sly_makeArray()

$predicate = create_function('$x', 'return $x % 2 == 0;');

$lime->ok(sly_arrayAny($predicate,  array(1,2,3)), 'sly_arrayAny(array(1,2,3))');
$lime->ok(sly_arrayAny($predicate,  array(2)),     'sly_arrayAny(array(2))');
$lime->ok(!sly_arrayAny($predicate, array(1)),     'sly_arrayAny(array(1))');
$lime->ok(!sly_arrayAny($predicate, array()),      'sly_arrayAny(array())');

$predicate = create_function('$x', 'return true;');

$lime->ok(sly_arrayAny($predicate,  array(null)), 'sly_arrayAny("return true;", array(null))');
$lime->ok(!sly_arrayAny($predicate, array()),     'sly_arrayAny("return true;", array())');

$predicate = create_function('$x', 'return false;');

$lime->ok(!sly_arrayAny($predicate, array(null)), 'sly_arrayAny("return false;", array(null))');
$lime->ok(!sly_arrayAny($predicate, array()),     'sly_arrayAny("return false;", array())');