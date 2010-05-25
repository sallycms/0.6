<?php

$lime           = new lime_test();
$testCasesTrue  = array(5, -5, '1', '901', '-901');
$testCasesFalse = array(5.1, true, false, null, '01', '1.5', '-1.5', '- 7', 'hello', '123hello', ' ', "\t", '');
	
foreach ($testCasesTrue as $case)  $lime->ok(sly_Util_String::isInteger($case), 'sly_Util_String::isInteger()');
foreach ($testCasesFalse as $case) $lime->ok(!sly_Util_String::isInteger($case), 'sly_Util_String::isInteger()');

$testCasesTrue  = array(array('', ''), array('hallo', ''), array('hallo', 'hal'), array('  hallo', '  hal'), array('1123', '1'), array(12, 1));
$testCasesFalse = array(array('', 'hallo'), array('hallo', 'hallo123'), array('hallo', 'xyz'), array('hallo', 'H'), array('hallo', ' '), array('  hallo', 0), );
	
foreach ($testCasesTrue as $case)  $lime->ok(sly_Util_String::startsWith($case[0], $case[1]), 'sly_Util_String::startsWith()');
foreach ($testCasesFalse as $case) $lime->ok(!sly_Util_String::startsWith($case[0], $case[1]), 'sly_Util_String::startsWith()');

$testCasesTrue  = array(array('', ''), array('hallo', ''), array('hallo', 'llo'), array('  hallo', '  allo'), array('1123', '23'), array(12, 2));
$testCasesFalse = array(array('', 'hallo'), array('hallo', 'hallo123'), array('hallo', 'xyz'), array('hallo', 'H'), array('hallo', ' '), array('  hallo', 0), );
	
foreach ($testCasesTrue as $case)  $lime->ok(sly_Util_String::endsWith($case[0], $case[1]), 'sly_Util_String::endsWith()');
foreach ($testCasesFalse as $case) $lime->ok(!sly_Util_String::endsWith($case[0], $case[1]), 'sly_Util_String::endsWith()');

unset($lime, $testCasesTrue, $testCasesFalse, $case);
