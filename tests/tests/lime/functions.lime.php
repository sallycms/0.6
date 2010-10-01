<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

$lime->comment('Testing global Sally functions (sly_*)...');

// sly_makeArray()

$lime->is(sly_makeArray(null),         array(),      'sly_makeArray(null)');
$lime->is(sly_makeArray(1),            array(1),     'sly_makeArray(1)');
$lime->is(sly_makeArray(true),         array(true),  'sly_makeArray(true)');
$lime->is(sly_makeArray(false),        array(false), 'sly_makeArray(false)');
$lime->is(sly_makeArray(array()),      array(),      'sly_makeArray(array())');
$lime->is(sly_makeArray(array(1,2,3)), array(1,2,3), 'sly_makeArray(array(1,2,3))');

