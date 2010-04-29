<?php

// Da diese Funktion im Setup direkt eingebunden wird
// hier das I18N Objekt ggf. erstellen

if ($REX['REDAXO'] && !isset($I18N)) {
	global $I18N;
	require_once(dirname(dirname(__FILE__)).'/config.inc.php');
}

/**
 * Importiert den SQL Dump $filename in die Datenbank
 *
 * @param string Pfad + Dateinamen zur SQL-Datei
 *
 * @return array Gibt ein Assoc. Array zurück.
 *               'state' => boolean (Status ob fehler aufgetreten sind)
 *               'message' => Evtl. Status/Fehlermeldung
 */
function rex_a1_import_db($filename)
{
	$importer = new sly_A1_Import_Database($filename);
	return $importer->import();
}

/**
 * Importiert das Tar-Archiv $filename in den Ordner /files
 *
 * @param string Pfad + Dateinamen zum Tar-Archiv
 *
 * @return array Gibt ein Assoc. Array zur�ck.
 *               'state' => boolean (Status ob fehler aufgetreten sind)
 *               'message' => Evtl. Status/Fehlermeldung
 */
function rex_a1_import_files($filename)
{
  global $REX, $I18N;

  $return = array ();
  $return['state'] = false;

  if ($filename == '' || substr($filename, -7, 7) != ".tar.gz")
  {
    $return['message'] = $I18N->msg("im_export_no_import_file_chosen")."<br />";
    return $return;
  }

  // Ordner /files komplett leeren
  rex_deleteFiles($REX['INCLUDE_PATH']."/../../files");

  $tar = new rex_tar;

  // ----- EXTENSION POINT
  $tar = rex_register_extension_point('A1_BEFORE_FILE_IMPORT', $tar);

  $tar->openTAR($filename);
  if (!$tar->extractTar())
  {
    $msg = $I18N->msg('im_export_problem_when_extracting').'<br />';
    if (count($tar->message) > 0)
    {
      $msg .= $I18N->msg('im_export_create_dirs_manually').'<br />';
      foreach($tar->message as $_message)
      {
        $msg .= rex_absPath($_message).'<br />';
      }
    }
  }
  else
  {
    $msg = $I18N->msg('im_export_file_imported').'<br />';
  }

  // ----- EXTENSION POINT
  $tar = rex_register_extension_point('A1_AFTER_FILE_IMPORT', $tar);

  $return['state'] = true;
  $return['message'] = $msg;
  return $return;
}
