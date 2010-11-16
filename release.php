<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

// Configuration

$variants = array(
	'full'    => array('docs' => true, 'tests' => true, 'addons' => array('image_resize', 'import_export', 'be_search')),
	'lite'    => array('docs' => true, 'tests' => true, 'addons' => array()),
	'minimal' => array('docs' => false, 'tests' => false, 'addons' => array())
);

$addonDir = 'Q:\\AddOns\\';
$cocoBin  = 'Q:\\docroot\\coco\\bin\\coco.php';

// Check arguments

$args = $_SERVER['argv'];

if (count($args) < 2) {
	die('Usage: php '.$args[0].' tagname [nofetch]');
}

$repo = dirname(__FILE__);
$tag  = $args[1];

// Check tag

$output = array();

chdir($repo);
exec('hg identify -r "'.$tag.'" 2>&1', $output);
$output = implode("\n", $output);

if (substr($output, 0, 6) == 'abort:') {
	die('Tag "'.$tag.'" was not found.');
}

// Create releases directory

if (!is_dir('../releases')) mkdir('../releases');
$releases = realpath('../releases');

// Create variants

foreach ($variants as $name => $settings) {
	printf('[%-7s] ', $name); // 7 = strlen('minimal')

	$target = sprintf('%s/sally-%s%s/sally', $releases, $tag, $name == 'full' ? '' : '-'.$name);

	// Create repository archive

	$output = array();
	$params = array(
		'-r "'.$tag.'"',
		'-X assets',
		'-X .hg_archival.txt',
		'-X .hgignore',
		'-X .hgtags',
		'-X release.php',
		'-X rebuild_lang.php',
		'-X compile-*',
	);

	if (!$settings['tests']) {
		$params[] = '-X tests';
	}

	if (!$settings['docs']) {
		$params[] = '-X docs';
	}

	$params[] = '"'.$target.'"';
	print 'archiving...';
	chdir($repo);
	exec('hg archive '.implode(' ', $params), $output);

	// Create empty data dir

	chdir($target);
	mkdir('data');
	mkdir('sally/include/addons');
	file_put_contents('data/empty', 'This directory is intentionally left blank.');

	// Generate documentation

	if ($settings['docs']) {
		chdir('docs');
		print 'coco...';
		exec('php '.$cocoBin.' . doconly 2>&0');
		chdir($target);
	}

	if (empty($settings['addons'])) {
		file_put_contents('sally/include/addons/empty', 'Put all your addOns in this directory.');
	}
	else {
		print ' addons...';

		foreach ($settings['addons'] as $addon) {
			print ' '.$addon.'...';

			$dir = $addonDir.$addon;
			chdir($dir);

			// update the repo
			if (!isset($args[2]) || $args[2] != 'nofetch') exec('hg fetch');

			// archive the repo into our sally archive

			$params = array(
				'-X .hg_archival.txt',
				'-X .hgignore',
				'-X .hgtags',
				'-X make.bat',
				'"'.$target.'/sally/include/addons/'.$addon.'"'
			);

			exec('hg archive '.implode(' ', $params));
		}
	}

	// Create archives

	chdir($target);
	print ' compressing...';

	chdir('..');
	$suffix = $name == 'full' ? '' : '-'.$name;

	print ' zip...';
	exec('7z a -mx9 "../sally-'.$tag.$suffix.'.zip" "'.$target.'"');

	print ' 7z...';
	exec('7z a -mx9 "../sally-'.$tag.$suffix.'.7z" "'.$target.'"');

	print ' tar...';
	exec('7z a "../sally-'.$tag.$suffix.'.tar" "'.$target.'"');

	print ' bz2...';
	exec('7z a -mx9 "../sally-'.$tag.$suffix.'.tar.bz2" "../sally-'.$tag.$suffix.'.tar"');

	// We don't need the tar file anymore.
	unlink('../sally-'.$tag.$suffix.'.tar');

	print PHP_EOL;
}

print 'done.'."\n";
