<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

function hg($cmd) {
	$output = array();
	exec('hg --config progress.disable=True '.$cmd.' 2>&1', $output);
	return implode("\n", $output);
}

// Configuration

$variants = array(
	'starterkit' => array('tests' => true, 'addons' => array('image_resize', 'import_export', 'be_search', 'metainfo', 'developer_utils', 'global_settings', 'deployer', 'realurl2', 'wymeditor')),
	'lite'       => array('tests' => true, 'addons' => array()),
	'minimal'    => array('tests' => false, 'addons' => array())
);

$addonDir = 'Q:\\AddOns\\';

// Check arguments

$args = $_SERVER['argv'];

if (count($args) < 2) {
	die('Usage: php '.$args[0].' tagname [nofetch]');
}

$repo    = realpath(dirname(__FILE__).'/../');
$tag     = $args[1];
$nofetch = isset($args[2]) && $args[2] === 'nofetch';

// Check tag

chdir($repo);
$output = hg('identify -r "'.$tag.'"');

if (substr($output, 0, 6) == 'abort:') {
	die('Tag "'.$tag.'" was not found.');
}

// Create releases directory

if (!is_dir('../releases')) mkdir('../releases');
$releases = realpath('../releases');

// Create variants

foreach ($variants as $name => $settings) {
	print strtoupper($name)."\n";

	$target = sprintf('%s/sally-%s%s/sally', $releases, $tag, '-'.$name);

	// Create repository archive

	$output = array();
	$params = array(
		'-r "'.$tag.'"',
		'-X assets',
		'-X .hg_archival.txt',
		'-X .hgignore',
		'-X .hgtags',
		'-X contrib',
		'-X sally/docs'
	);

	if (!$settings['tests']) {
		$params[] = '-X sally/tests';
	}

	$params[] = '"'.$target.'"';
	print ' -> archiving...';
	chdir($repo);
	hg('archive '.implode(' ', $params));
	print "\n";

	// Create empty data dir

	chdir($target);
	@mkdir('sally/data');
	@mkdir('sally/addons');
	file_put_contents('sally/data/empty', 'This directory is intentionally left blank. Please make sure it\'s chmod to 0777.');

	// Put addOns in the archive

	if (empty($settings['addons'])) {
		file_put_contents('sally/addons/empty', 'Put all your addOns in this directory. PHP does not need writing permissions in here.');
	}
	else {
		print " -> addons...\n";

		foreach ($settings['addons'] as $addon) {
			print '    -> '.str_pad($addon.'...', 20, ' ');

			$dir = $addonDir.$addon;
			chdir($dir);

			// update the repo
			if (!$nofetch) {
				print ' fetching...';
				hg('fetch');
				print ' archiving...';
			}

			// find latest_sly05 tag
			$output = array();
			exec('hg identify -r latest_sly05 2>&1', $output);

			$output    = implode("\n", $output);
			$toArchive = substr($output, 0, 6) == 'abort:' ? 'tip' : 'latest_sly04';

			// archive the repo into our sally archive

			$params = array(
				'-X .hg_archival.txt',
				'-X .hgignore',
				'-X .hgtags',
				'-X docs',
				'-X make.bat',
				'-r '.$toArchive,
				'"'.$target.'/sally/addons/'.$addon.'"'
			);

			hg('archive '.implode(' ', $params));
			print "\n";
		}
	}

	// Add starterkit contents (templates, modules, assets, ...)

	if ($name === 'starterkit') {
		print ' -> demo project...';

		$params = array(
			'-X .hg_archival.txt',
			'-X .hgignore',
			'-X .hgtags',
			'-X make.bat',
			'-r tip',
			'"'.$target.'"'
		);

		chdir($releases);
		chdir('../demo');

		if (!$nofetch) {
			print ' fetching...';
			hg('fetch');
			print ' archiving...';
		}

		hg('archive '.implode(' ', $params));
		print "\n";
	}

	// Create archives

	chdir($target);
	print " -> compressing...\n";

	chdir('..');
	$suffix = '-'.$name;

	print '    -> zip...';
	exec('7z a -mx9 "../sally-'.$tag.$suffix.'.zip" "'.$target.'"');
	print "\n";

	print '    -> 7z...';
	exec('7z a -mx9 "../sally-'.$tag.$suffix.'.7z" "'.$target.'"');
	print "\n";
}

print 'done.'."\n";
