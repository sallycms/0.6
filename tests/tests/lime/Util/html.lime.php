<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

$lime->comment('Testing sly_Util_HTML...');

// isAttribute()

$testCasesTrue  = array(5, -5, '1', 'hello world', true);
$testCasesFalse = array(false, null, '', "  \t  ");

foreach ($testCasesTrue as $case)  $lime->ok(sly_Util_HTML::isAttribute($case), 'isAttribute()');
foreach ($testCasesFalse as $case) $lime->ok(!sly_Util_HTML::isAttribute($case), 'isAttribute()');

// buildAttributeString()

$data = array(
	'foo'   => 'bar',
	'hallo' => '',
	'x'     => true,
	'BAR'   => ' neu ',
	'xy'    => "\t",
	'BLUB ' => 34,
	'html'  => '<hallo>, "welt"',
	'abc'   => null
);

$expected = 'foo="bar" x="1" bar="neu" blub="34" html="&lt;hallo&gt;, &quot;welt&quot;"';

$lime->is(sly_Util_HTML::buildAttributeString($data),                 $expected,   'buildAttributeString()');
$lime->is(sly_Util_HTML::buildAttributeString(array()),               '',          'buildAttributeString()');
$lime->is(sly_Util_HTML::buildAttributeString(array('foo' => 'bar')), 'foo="bar"', 'buildAttributeString()');
$lime->is(sly_Util_HTML::buildAttributeString(array('foo' => '')),    '',          'buildAttributeString()');

// aufräumen

unset($testCasesTrue, $testCasesFalse, $data, $expected);
