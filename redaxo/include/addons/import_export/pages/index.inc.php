<?php

// $__before = microtime(true);
// $__mema   = memory_get_usage();

include_once $REX['INCLUDE_PATH'].'/addons/import_export/functions/function_import_export.inc.php';
include_once $REX['INCLUDE_PATH'].'/addons/import_export/functions/function_import_folder.inc.php';

// Head erzeugen

require $REX['INCLUDE_PATH'].'/layout/top.php';
rex_title($I18N->msg('im_export_importexport'), $REX['ADDON']['import_export']['SUBPAGES']);

// Script einbinden

$subpage = rex_request('subpage', 'string');
$basedir = dirname(__FILE__);

if ($subpage == 'import' && ($REX['USER']->hasPerm('import_export[import]') || $REX['USER']->isAdmin())) {
	require $basedir.'/import.inc.php';
}
else {
	require $basedir.'/export.inc.php';
}

// $time = microtime(true) - $__before;
// $memory = memory_get_usage() - $__mema;
// 
// print "time: $time, mem = ".number_format($memory, 0, ',', '.')." bytes";
// print ", peak = ".number_format(memory_get_peak_usage(), 0, ',', '.')." bytes";

// Footer erzeugen

require $REX['INCLUDE_PATH'].'/layout/bottom.php';
