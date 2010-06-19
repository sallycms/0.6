<?php

$lime->comment('Testing sly_Util_Directory (stateless methods)...');
$s = DIRECTORY_SEPARATOR;

function __join($paths) {
	$args = func_get_args();
	return call_user_func_array('sly_Util_Directory::join', $args);
}

function __normalize($path) {
	return sly_Util_Directory::normalize($path);
}

$lime->is(__join('a'), 'a', 'join() changes nothing if no separator found');
$lime->is(__join('a', 'b'), 'a'.$s.'b', 'join() concats two paths with DIRECTORY_SEPARATOR');
$lime->is(__join('a', 'b', 'c'), 'a'.$s.'b'.$s.'c', 'join() concats N paths with DIRECTORY_SEPARATOR');
$lime->is(__join('a', 345, 'c'), 'a'.$s.'345'.$s.'c', 'join() converts numbers to strings');
$lime->is(__join('foo/', '/bar/blub'), 'foo'.$s.'bar'.$s.'blub', 'join() ignores absolute paths in all but the first argument');
$lime->is(__join('foo/', '/bar\\blub'), 'foo'.$s.'bar'.$s.'blub', 'join() unifies the separator');
$lime->is(__join('foo///', '/bar/\\blub/'), 'foo'.$s.'bar'.$s.'blub', 'join() trims multiple separators');
$lime->is(__join('\\foo/', '/bar/blub/'), $s.'foo'.$s.'bar'.$s.'blub', 'join() recognizes absolute paths');

$lime->is(__normalize(12), 12, 'normalize() converts numbers to strings');
$lime->is(__normalize('\\foo/'), $s.'foo', 'normalize() recognizes absolute paths');
$lime->is(__normalize('foo\\'), 'foo', 'normalize() trims trailing separators');
$lime->is(__normalize('a//b//c/\\/d/x\\/'), 'a'.$s.'b'.$s.'c'.$s.'d'.$s.'x', 'normalize() can handle totally strange paths');

$lime->comment('Testing sly_Util_Directory (reading from disk, creating test files as needed)...');

$here = realpath(dirname(__FILE__));

$obj = new sly_Util_Directory($here.'/4986z9irugh3wiufzgeu');
$lime->is($obj->exists(), false, 'exists() returns false on a non-existing directory');

$obj = new sly_Util_Directory($here);
$lime->ok($obj->exists(), 'exists() returns true on an existing directory');

@mkdir($here.'/tmp/foo/bar', 0777, true);
@mkdir($here.'/tmp/.blafasel/xy', 0777, true);
@mkdir($here.'/tmp/child', 0777, true);

$obj = new sly_Util_Directory($here.'/tmp');

$files = $obj->listPlain(true, false, false, false, '');
$lime->is($files, array(), 'listPlain() returns an empty list, since no files are found');

$dirs = $obj->listPlain(false, true, false, false, 'sort');
$lime->is($dirs, array('child', 'foo'), 'listPlain() returns all three created directories');

$dirs = $obj->listPlain(false, true, false, false, 'rsort');
$lime->is($dirs, array('foo', 'child'), 'listPlain() respects the sort function');

try {
	$dirs = $obj->listPlain(true, true, true, true, 'dfsjhiu34zg5ui324r');
	$lime->fail('listPlain() should throw a sly_Exception if the sort function is unknown');
}
catch (sly_Exception $e) {
	$lime->pass('listPlain() throws the correct exception (sly_Exception) if the sort function is unknown');
}
catch (Exception $e) {
	$lime->fail('listPlain() throws the wrong exception ('.get_class($e).') if the sort function is unknown');
}

// Let's create some files to play with.

@touch($here.'/tmp/foo/.htaccess');
@touch($here.'/tmp/foo/testfile.txt');
@touch($here.'/tmp/readme');
@touch($here.'/tmp/.ignoreme');
@touch($here.'/tmp/test');
@touch($here.'/tmp/helloworld');
@touch($here.'/tmp/.blafasel/list.php');

$files = $obj->listPlain(true, false, false, false, 'sort');
$lime->is($files, array('helloworld', 'readme', 'test'), 'listPlain() returns all three files in foo/');

$dirs = $obj->listPlain(true, false, true, false, 'sort');
$lime->is($dirs, array('.ignoreme', 'helloworld', 'readme', 'test'), 'listPlain() includes dotfiles if needed');

$dirs = $obj->listPlain(true, true, false, false, 'sort');
$lime->is($dirs, array('child', 'foo', 'helloworld', 'readme', 'test'), 'listPlain() can merge files and directories');

$dirs = $obj->listPlain(true, true, true, false, 'sort');
$lime->is($dirs, array('.blafasel', '.ignoreme', 'child', 'foo', 'helloworld', 'readme', 'test'), 'listPlain() correctly includes dotfiles');

$dirs     = $obj->listPlain(true, true, true, true, 'sort');
$prefix   = $here.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR;
$expected = array($prefix.'.blafasel', $prefix.'.ignoreme', $prefix.'child', $prefix.'foo', $prefix.'helloworld', $prefix.'readme', $prefix.'test');
$lime->is($dirs, $expected, 'listPlain() returns the full path if requested (realpath)');

// Clean up

@unlink($here.'/tmp/foo/.htaccess');
@unlink($here.'/tmp/foo/testfile.txt');
@unlink($here.'/tmp/readme');
@unlink($here.'/tmp/.ignoreme');
@unlink($here.'/tmp/test');
@unlink($here.'/tmp/helloworld');
@unlink($here.'/tmp/.blafasel/list.php');

@rmdir($here.'/tmp/.blafasel/xy');
@rmdir($here.'/tmp/.blafasel');
@rmdir($here.'/tmp/foo/bar');
@rmdir($here.'/tmp/foo');
@rmdir($here.'/tmp/child');
@rmdir($here.'/tmp');

unset($here, $obj, $expected, $prefix, $dirs, $files, $s);
