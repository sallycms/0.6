<?php

$lime->comment('Testing sly_Service_Template...');

$uniqid   = 'abc'.uniqid();
$filename = '__tmp__'.$uniqid.'.php';
$testfile = <<<TESTFILE
<?php

print "Hallo Welt!";

/**
 * Dieses Template ist ein Beispiel.
 *
 * @sly name    $uniqid
 * @sly title   Mein super tolles Template!!!1elf
 * @sly slots   [links, rechts]
 * @sly modules [gallery, foobar, james]
 * @sly class   [article, meta]
 * @sly custom  42
 */

\$x = 4;
print \$x + 5;
TESTFILE;

$service = sly_Service_Factory::getService('Template');
$folder  = $service->getFolder();

// Test-Template erzeugen

file_put_contents(sly_Util_Directory::join($folder, $filename), $testfile);
unset($testfile);

// Beim Aufruf wird der Cache ggf. erneuert -> Warnings für alte Templates
// Diese Warnings interessieren uns hier aber nicht, für uns geht's nur um unser
// eigenes Template.

$lime->is_deeply(@$service->get($uniqid, 'name'), $uniqid, 'get() returns the correct name');
$lime->is_deeply(@$service->getTitle($uniqid), 'Mein super tolles Template!!!1elf', 'getTitle() returns the correct title');
$lime->is_deeply(@$service->get($uniqid, 'custom'), 42, 'get() returns custom value');

unlink(sly_Util_Directory::join($folder, $filename));
unset($service, $filename, $folder, $uniqid);
