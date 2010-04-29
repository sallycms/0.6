<?php

// Für größere Exports den Speicher für PHP erhöhen.
@ini_set('memory_limit', '64M');

include_once $REX['INCLUDE_PATH'].'/addons/import_export/functions/function_import_export.inc.php';
include_once $REX['INCLUDE_PATH'].'/addons/import_export/functions/function_import_folder.inc.php';

$info     = '';
$warning  = '';
$function = sly_request('function', 'string');
$filename = sly_post('filename', 'string', 'sly_'.date('Ymd'));
$type     = sly_post('type', 'string', 'sql');
$download = sly_post('download', 'boolean', false);

if ($function == 'export') {
	// Dateiname entschärfen
	
	$orig     = $filename;
	$filename = strtolower($filename);
	$filename = preg_replace('#[^\.a-z0-9_-]#', '', $filename);

	if ($filename != $orig) {
		$info = $I18N->msg('im_export_filename_updated');
		$_POST['filename'] = addslashes($filename);
	}
	else {
		$content    = '';
		$hasContent = false;
		$header     = '';
		$ext        = $type == 'sql' ? '.sql' : '.tar.gz';
		$exportPath = getImportDir().'/';
		$filename   = sly_A1_Helper::getIteratedFilename($exportPath, $filename, $ext);
		
		// Export durchführen

		if ($type == 'sql') {
			$header     = 'plain/text; charset="UTF-8"';
			$exporter   = new sly_A1_Export_Database();
			$hasContent = $exporter->export($exportPath.$filename.$ext);
		}
		elseif ($type == 'files') {
			$header      = 'tar/gzip';
			$directories = sly_postArray('directories', 'string');

			if (empty($directories)) {
				$warning = $I18N->msg('im_export_please_choose_folder');
			}
			else {
				$exporter   = new sly_A1_Export_Files($directories);
				$hasContent = $exporter->export($exportPath.$filename.$ext);
			}
		}

		if ($hasContent) {
			if ($download) {
				while (ob_get_level()) ob_end_clean();
				$filename = $filename.$ext;
				header("Content-Type: $header");
				header("Content-Disposition: attachment; filename=$filename");
				readfile($exportPath.$filename);
				unlink($exportPath.$filename);
				exit;
			}
			else {
				$info = $I18N->msg('im_export_file_generated_in').' '.strtr($filename.$ext, '\\', '/');
			}
		}
		else {
			$warning = $I18N->msg('im_export_file_could_not_be_generated').' '.$I18N->msg('im_export_check_rights_in_directory').' '.$exportPath;
		}
	}
}

// View anzeigen

include $REX['INCLUDE_PATH'].'/addons/import_export/templates/export.phtml';
