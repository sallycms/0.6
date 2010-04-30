<?php

@set_time_limit(0);

$info     = '';
$warning  = '';
$function = rex_request('function', 'string');
$impname  = rex_request('impname', 'string');

if ($impname != '')
{
  $impname = str_replace("/", "", $impname);

  if ($function == "dbimport" && substr($impname, -4, 4) != ".sql")
    $impname = "";
  elseif ($function == "fileimport" && substr($impname, -7, 7) != ".tar.gz")
    $impname = "";
}

if ($function == "delete")
{
  // ------------------------------ FUNC DELETE
  if (unlink(getImportDir().'/'.$impname));
  $info = $I18N->msg("im_export_file_deleted");
}
elseif ($function == "dbimport")
{
  // ------------------------------ FUNC DBIMPORT

  // noch checken das nicht alle tabellen geloescht werden
  // install/temp.sql aendern
  if (isset ($_FILES['FORM']) && $_FILES['FORM']['size']['importfile'] < 1 && $impname == "")
  {
    $warning = $I18N->msg("im_export_no_import_file_chosen_or_wrong_version")."<br>";
  }
  else
  {
    if ($impname != "")
    {
      $file_temp = getImportDir().'/'.$impname;
    }
    else
    {
      $file_temp = getImportDir().'/temp.sql';
    }

    if ($impname != "" || @ move_uploaded_file($_FILES['FORM']['tmp_name']['importfile'], $file_temp))
    {
      $state = rex_a1_import_db($file_temp);
      $info = $state['message'];

      // temp datei löschen
      if ($impname == "")
      {
        @ unlink($file_temp);
      }
    }
    else
    {
      $warning = $I18N->msg("im_export_file_could_not_be_uploaded")." ".$I18N->msg("im_export_you_have_no_write_permission_in", "addons/import_export/files/")." <br>";
    }
  }

}
elseif ($function == "fileimport")
{
  // ------------------------------ FUNC FILEIMPORT

  if (isset($_FILES['FORM']) && $_FILES['FORM']['size']['importfile'] < 1 && $impname == "")
  {
    $warning = $I18N->msg("im_export_no_import_file_chosen")."<br/>";
  }
  else
  {
    if ($impname == "")
    {
      $file_temp = getImportDir().'/temp.tar.gz';
    }
    else
    {
      $file_temp = getImportDir().'/'.$impname;
    }
    if ($impname != "" || @move_uploaded_file($_FILES['FORM']['tmp_name']['importfile'], $file_temp))
    {
      $return = rex_a1_import_files($file_temp);
			if($return['state'])
      	$info = $return['message'];
			else
				$warning = $return['message'];

      // temp datei löschen
      if ($impname == "")
      {
        @ unlink($file_temp);
      }
    }
    else
    {
      $warning = $I18N->msg("im_export_file_could_not_be_uploaded")." ".$I18N->msg("im_export_you_have_no_write_permission_in", "addons/import_export/files/")." <br>";
    }
  }

}

// View anzeigen

include $REX['INCLUDE_PATH'].'/addons/import_export/templates/import.phtml';
