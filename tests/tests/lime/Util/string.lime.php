<?php

$lime->comment('Testing sly_Util_String...');

// isInteger()

$testCasesTrue  = array(5, -5, '1', '901', '-901');
$testCasesFalse = array(5.1, true, false, null, '01', '1.5', '-1.5', '- 7', 'hello', '123hello', ' ', "\t", '');

foreach ($testCasesTrue as $case)  $lime->ok(sly_Util_String::isInteger($case), 'isInteger()');
foreach ($testCasesFalse as $case) $lime->ok(!sly_Util_String::isInteger($case), 'isInteger()');

// startsWith()

$testCasesTrue  = array(array('', ''), array('hallo', ''), array('hallo', 'hal'), array('  hallo', '  hal'), array('1123', '1'), array(12, 1));
$testCasesFalse = array(array('', 'hallo'), array('hallo', 'hallo123'), array('hallo', 'xyz'), array('hallo', 'H'), array('hallo', ' '), array('  hallo', 0), );

foreach ($testCasesTrue as $case)  $lime->ok(sly_Util_String::startsWith($case[0], $case[1]), 'startsWith()');
foreach ($testCasesFalse as $case) $lime->ok(!sly_Util_String::startsWith($case[0], $case[1]), 'startsWith()');

// endsWith()

$testCasesTrue  = array(array('', ''), array('hallo', ''), array('hallo', 'llo'), array('  hallo', '  allo'), array('1123', '23'), array(12, 2));
$testCasesFalse = array(array('', 'hallo'), array('hallo', 'hallo123'), array('hallo', 'xyz'), array('hallo', 'H'), array('hallo', ' '), array('  hallo', 0), );

foreach ($testCasesTrue as $case)  $lime->ok(sly_Util_String::endsWith($case[0], $case[1]), 'endsWith()');
foreach ($testCasesFalse as $case) $lime->ok(!sly_Util_String::endsWith($case[0], $case[1]), 'endsWith()');

// strToUpper()

$testCases = array('hallo' => 'HALLO', 'wOrLd' => 'WORLD', 'süß' => 'SÜSS', 'The answer is 42.' => 'THE ANSWER IS 42.');
foreach ($testCases as $old => $new) $lime->is(sly_Util_String::strToUpper($old), $new, 'strToUpper("'.$old.'")');

// humanImplode()

$lime->is(sly_Util_String::humanImplode(array(), ' und '),        '',              'humanImplode()');
$lime->is(sly_Util_String::humanImplode(array(1), ' und '),       '1',             'humanImplode(1)');
$lime->is(sly_Util_String::humanImplode(array(1,2), ' und '),     '1 und 2',       'humanImplode(1, 2)');
$lime->is(sly_Util_String::humanImplode(array(1,2,3), ' und '),   '1, 2 und 3',    'humanImplode(1, 2, 3)');
$lime->is(sly_Util_String::humanImplode(array(1,2,3,4), ' und '), '1, 2, 3 und 4', 'humanImplode(1, 2, 3, 4)');

// getRandomString()

$lime->ok(strlen(sly_Util_String::getRandomString(5, 10)) >= 5,  'getRandomString(5, 10) >= 5');
$lime->ok(strlen(sly_Util_String::getRandomString(5, 10)) <= 10, 'getRandomString(5, 10) <= 10');
$lime->ok(strlen(sly_Util_String::getRandomString(5, 5)) == 5,   'getRandomString(5, 5) == 5');
$lime->ok(strlen(sly_Util_String::getRandomString(10, 5)) >= 5,  'getRandomString(10, 5) >= 5');
$lime->ok(strlen(sly_Util_String::getRandomString(10, 5)) <= 10, 'getRandomString(10, 5) <= 10');

$lime->isnt(sly_Util_String::getRandomString(5, 10), sly_Util_String::getRandomString(5, 10), 'getRandomString() != getRandomString()');

// aufräumen

unset($testCasesTrue, $testCasesFalse, $testCases, $case);
