<?php

if ($REX['REDAXO']) {
	$I18N->appendFile($REX['INCLUDE_PATH'].'/addons/import_export/lang/');
	
	if (is_object($REX['USER'])) {
		$REX['PERM'][] = 'import_export[export]';
		$REX['PERM'][] = 'import_export[import]';
	
		$REX['ADDON']['import_export']['SUBPAGES'] = array(array('', $I18N->msg('im_export_export')));
		
		if ($REX['USER']->hasPerm('import_export[import]') || $REX['USER']->isAdmin()) {
			$REX['ADDON']['import_export']['SUBPAGES'][] = array('import', $I18N->msg('im_export_import'));
		}
	}
}

$REX['ADDON']['rxid']['import_export']        = '1';
$REX['ADDON']['page']['import_export']        = 'import_export';
$REX['ADDON']['name']['import_export']        = $REX['REDAXO'] ? $I18N->msg('im_export_importexport') : 'Im-/Export';
$REX['ADDON']['perm']['import_export']        = 'import_export[export]';
$REX['ADDON']['version']['import_export']     = file_get_contents(dirname(__FILE__).'/version');
$REX['ADDON']['author']['import_export']      = 'Christoph Mewes, Jan Kristinus';
$REX['ADDON']['supportpage']['import_export'] = 'www.webvariants.de';

// Menü aufbauen



// Autoloading initialisieren

function _sly_a1_autoload($className)
{
	$class   = $className['subject'];
	$classes = array(
		'sly_A1_PEAR'            => 'class.pear.php',
		'sly_A1_Archive_Tar'     => 'class.tar.php',
		'sly_A1_Helper'          => 'class.helper.php',
		'sly_A1_Import_Database' => 'class.import.database.php',
		'sly_A1_Export_Database' => 'class.export.database.php',
		'sly_A1_Import_Files'    => 'class.import.files.php',
		'sly_A1_Export_Files'    => 'class.export.files.php'
	);
	
	if (isset($classes[$class])) {
		require_once dirname(__FILE__).'/classes/'.$classes[$class];
		return '';
	}
}

rex_register_extension('__AUTOLOAD', '_sly_a1_autoload');
