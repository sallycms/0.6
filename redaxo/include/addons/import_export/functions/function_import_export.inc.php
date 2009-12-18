<?php

// Da diese Funktion im Setup direkt eingebunden wird
// hier das I18N Objekt ggf. erstellen
if ($REX['REDAXO'] && !isset($I18N))
{
  global $I18N;
  require_once(dirname(dirname(__FILE__)).'/config.inc.php');
}


/**
 * Importiert den SQL Dump $filename in die Datenbank
 *
 * @param string Pfad + Dateinamen zur SQL-Datei
 *
 * @return array Gibt ein Assoc. Array zur�ck.
 *               'state' => boolean (Status ob fehler aufgetreten sind)
 *               'message' => Evtl. Status/Fehlermeldung
 */
function rex_a1_import_db($filename)
{
  global $REX, $I18N;

  $return = array ();
  $return['state'] = false;
  $return['message'] = '';

  $msg = '';
  $error = '';

  if ($filename == '' || substr($filename, -4, 4) != ".sql")
  {
    $return['message'] = $I18N->msg('im_export_no_import_file_chosen_or_wrong_version').'<br>';
    return $return;
  }

  $conts = rex_get_file_contents($filename);

  // Versionsstempel pr�fen
  // ## Redaxo Database Dump Version x.x
  $version = strpos($conts, '## Redaxo Database Dump Version '.$REX['VERSION']);
  if($version === false)
  {
    $return['message'] = $I18N->msg('im_export_no_valid_import_file').'. [## Redaxo Database Dump Version '.$REX['VERSION'].'] is missing';
    return $return;
  }
  // Versionsstempel entfernen
  $conts = trim(str_replace('## Redaxo Database Dump Version '.$REX['VERSION'], '', $conts));

  // Prefix pr�fen
  // ## Prefix xxx_
  if(preg_match('/^## Prefix ([a-zA-Z0-9\_]*)/', $conts, $matches) && isset($matches[1]))
  {
    // prefix entfernen
    $prefix = $matches[1];
    $conts = trim(str_replace('## Prefix '. $prefix, '', $conts));
  }
  else
  {
    // Prefix wurde nicht gefunden
    $return['message'] = $I18N->msg('im_export_no_valid_import_file').'. [## Prefix '. $REX['TABLE_PREFIX'] .'] is missing';
    return $return;
  }



  // Charset pr�fen
  // ## charset xxx_
  if(preg_match('/^## charset ([a-zA-Z0-9\_\-]*)/', $conts, $matches) && isset($matches[1]))
  {
    // charset entfernen
    $charset = $matches[1];
    $conts = trim(str_replace('## charset '. $charset, '', $conts));
    
    if($I18N->msg('htmlcharset') != $charset)
    {
	    $return['message'] = $I18N->msg('im_export_no_valid_charset').'. '.$I18N->msg('htmlcharset').' != '.$charset;
	    return $return;
    }
    
  }
  
  /*
  // Charset nicht zwingend notwendig
  else
  {
    $return['message'] = $I18N->msg('im_export_no_valid_import_file').'. [## Charset '. $I18N->msg('htmlcharset') .'] is missing]';
    return $return;
  }
  */





  // Prefix im export mit dem der installation angleichen
  if($REX['TABLE_PREFIX'] != $prefix)
  {
    // Hier case-insensitiv ersetzen, damit alle m�glich Schreibweisen (TABLE TablE, tAblE,..) ersetzt werden
    // Dies ist wichtig, da auch SQLs innerhalb von Ein/Ausgabe der Module vom rex-admin verwendet werden
    $conts = preg_replace('/(TABLE `?)' . preg_quote($prefix, '/') .'/i', '$1'. $REX['TABLE_PREFIX'], $conts);
    $conts = preg_replace('/(INTO `?)'  . preg_quote($prefix, '/') .'/i', '$1'. $REX['TABLE_PREFIX'], $conts);
    $conts = preg_replace('/(EXISTS `?)'. preg_quote($prefix, '/') .'/i', '$1'. $REX['TABLE_PREFIX'], $conts);
  }

  // ----- EXTENSION POINT
  $filesize = filesize($filename);
  $msg = rex_register_extension_point('A1_BEFORE_DB_IMPORT', $msg,
   array(
     'content' => $conts,
     'filename' => $filename,
     'filesize' => $filesize
   )
  );

  // Datei aufteilen
  $lines = explode("\n", $conts);

  $add = new rex_sql;
  $error = '';
  foreach ($lines as $line)
  {
    $line = trim($line,"\r"); // Windows spezifische extras
    $line = trim($line, ";"); // mysql 3.x

    if($line == '') continue;

    $add->setQuery($line);

    if($add->hasError())
      $error .= "\n". $add->getError();
  }

  if($error != '')
  {
    $return['message'] = trim($error);
    return $return;
  }

  $msg .= $I18N->msg('im_export_database_imported').'. '.$I18N->msg('im_export_entry_count', count($lines)).'<br />';

  // pr�fen, ob eine user tabelle angelegt wurde
  $tables = rex_sql::showTables();
  $user_table_found = in_array($REX['TABLE_PREFIX'].'user', $tables);

  if (!$user_table_found)
  {
    $create_user_table = '
    CREATE TABLE '. $REX['TABLE_PREFIX'] .'user
     (
       user_id int(11) NOT NULL auto_increment,
       name varchar(255) NOT NULL,
       description text NOT NULL,
       login varchar(50) NOT NULL,
       psw varchar(50) NOT NULL,
       status varchar(5) NOT NULL,
       rights text NOT NULL,
       login_tries tinyint(4) NOT NULL DEFAULT 0,
       createuser varchar(255) NOT NULL,
       updateuser varchar(255) NOT NULL,
       createdate int(11) NOT NULL DEFAULT 0,
       updatedate int(11) NOT NULL DEFAULT 0,
       lasttrydate int(11) NOT NULL DEFAULT 0,
       session_id varchar(255) NOT NULL,
       PRIMARY KEY(user_id)
     ) TYPE=MyISAM;';
    $db = new rex_sql;
    $db->setQuery($create_user_table);
    $error = $db->getError();
    if($error != '')
    {
      // evtl vorhergehende meldungen l�schen, damit nur der fehler angezeigt wird
      $msg = '';
      $msg .= $error;
    }
  }

  // generated neu erstellen, wenn kein Fehler aufgetreten ist
  if($error == '')
  {
    // ----- EXTENSION POINT
    $msg = rex_register_extension_point('A1_AFTER_DB_IMPORT', $msg,
     array(
       'content' => $conts,
       'filename' => $filename,
       'filesize' => $filesize
     )
    );
    $msg .= rex_generateAll();
    $return['state'] = true;
  }

  $return['message'] = $msg;

  return $return;
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

/**
 * Erstellt einen SQL Dump, der die aktuellen Datebankstruktur darstellt
 * @return string SQL Dump der Datenbank
 */
function rex_a1_export_db()
{
  global $REX,$I18N;

  $tabs = new rex_sql;
  $tabs->setquery('SHOW TABLES');
  $dump = '';

  // ----- EXTENSION POINT
  rex_register_extension_point('A1_BEFORE_DB_EXPORT');

  for ($i = 0; $i < $tabs->rows; $i++, $tabs->next())
  {
    $tab = $tabs->getValue('Tables_in_'.$REX['DB']['1']['NAME']);
    if (strstr($tab, $REX['TABLE_PREFIX']) == $tab // Nur Tabellen mit dem aktuellen Prefix
        && $tab != $REX['TABLE_PREFIX'].'user' // User Tabelle nicht exportieren
        && substr($tab, 0 , strlen($REX['TABLE_PREFIX'].$REX['TEMP_PREFIX'])) != $REX['TABLE_PREFIX'].$REX['TEMP_PREFIX']) // Tabellen die mit rex_tmp_ beginnne, werden nicht exportiert!
    {
      $cols = new rex_sql;
      $cols->setquery("SHOW COLUMNS FROM `".$tab."`");
      $query = "DROP TABLE IF EXISTS `".$tab."`;\nCREATE TABLE `".$tab."` (";

      // Spalten auswerten
      for ($j = 0; $j < $cols->rows; $j++)
      {
        $colname = $cols->getValue('Field');
        $coltype = $cols->getValue('Type');

        // Null Werte
        if ($cols->getValue('Null') == 'YES')
        {
          $colnull = 'NULL';
        }
        else
        {
          $colnull = 'NOT NULL';
        }

        // Default Werte
        if ($cols->getValue('Default') != '')
        {
          $coldef = 'DEFAULT \''. str_replace("'", "\'", $cols->getValue('Default')) .'\'';
        }
        else
        {
          $coldef = '';
        }

        // Spezial Werte
        $colextra = $cols->getValue('Extra');

        $query .= " `$colname` $coltype $colnull $coldef $colextra";
        if ($j +1 != $cols->rows)
        {
          $query .= ",";
        }
        $cols->next();
      }

      // Indizes Auswerten
      $indizes = new rex_sql();
      $indizes->setQuery('SHOW INDEX FROM `'. $tab .'`');

      $primary = array();
      $uniques = array();
      $fulltexts = array();
      for($x = 0; $x < $indizes->getRows(); $x++)
      {
        if($indizes->getValue('Index_type') == 'BTREE')
        {
          if($indizes->getValue('Key_name') != 'PRIMARY')
          {
            $uniques[$indizes->getValue('Key_name')][] = $indizes->getValue('Column_name');
          }
          else
          {
            $primary[$indizes->getValue('Key_name')][] = $indizes->getValue('Column_name');
          }
        }
        else if ($indizes->getValue('Index_type') == 'FULLTEXT')
        {
          $fulltexts[$indizes->getValue('Key_name')][] = $indizes->getValue('Column_name');
        }
        $indizes->next();
      }

      // Primary key Auswerten
      foreach($primary as $name => $columnNames)
      {
        // , UNIQUE KEY `name` (`spalten`,..)
        $query .= ", PRIMARY KEY (`". implode('`,`', $columnNames) ."`)";
      }

      // Unique Index Auswerten
      foreach($uniques as $name => $columnNames)
      {
        // , UNIQUE KEY `name` (`spalten`,..)
        $query .= ", UNIQUE KEY `". $name ."`(`". implode('`,`', $columnNames) ."`)";
      }

      // Unique Index Auswerten
      foreach($fulltexts as $name => $columnNames)
      {
        // , FULLTEXT KEY `name` (`spalten`,..)
        $query .= ", FULLTEXT KEY `". $name ."`(`". implode('`,`', $columnNames) ."`)";
      }

      $query .= ") TYPE=MyISAM;";

      $dump .= $query."\n";

      // Inhalte der Tabelle Auswerten
      $cont = new rex_sql;
      $cont->setquery("SELECT * FROM `".$tab."`");
      for ($j = 0; $j < $cont->rows; $j++, $cont->next())
      {
        $query = "INSERT INTO `".$tab."` VALUES (";
        $cols->counter = 0;
        for ($k = 0; $k < $cols->rows; $k++, $cols->next())
        {
          $con = $cont->getValue($cols->getValue("Field"));

          if (is_numeric($con))
          {
            $query .= "'".$con."'";
          }
          else
          {
            $query .= "'".addslashes($con)."'";
          }

          if ($k +1 != $cols->rows)
          {
            $query .= ",";
          }
        }
        $query .= ");";
        $dump .= str_replace(array (
          "\r\n",
          "\n"
        ), '\r\n', $query)."\n";
      }
    }
  }

  // Versionsstempel hinzuf�gen
  $dump = str_replace("\r", "", $dump);
  $header = "## Redaxo Database Dump Version ".$REX['VERSION']."\n";
  $header .= "## Prefix ". $REX['TABLE_PREFIX'] ."\n";
  $header .= "## charset ". $I18N->msg('htmlcharset') ."\n";

  $content = $header . $dump;

  // ----- EXTENSION POINT
  $content = rex_register_extension_point('A1_AFTER_DB_EXPORT', $content);

  return $content;
}

/**
 * Exportiert alle Ordner $folders aus dem Verzeichnis /files
 *
 * @param array Array von Ordnernamen, die exportiert werden sollen
 * @param string Pfad + Dateiname, wo das Tar File erstellt werden soll
 *
 * @access public
 * @return string Inhalt des Tar-Archives als String
 */
function rex_a1_export_files($folders)
{
  global $REX;

  $tar = new rex_tar;

  // ----- EXTENSION POINT
  $tar = rex_register_extension_point('A1_BEFORE_FILE_EXPORT', $tar);

  foreach ($folders as $key => $item)
  {
    // Hier HTDOCS statt INLCUDE PATH, da INLCUDE PATH absolut ist!
    _rex_a1_add_folder_to_tar($tar, $REX['HTDOCS_PATH']."redaxo/include/../../", $key);
  }

  // ----- EXTENSION POINT
  $tar = rex_register_extension_point('A1_AFTER_FILE_EXPORT', $tar);

  return $tar->toTar(null, true);
}

/**
 * F�gt einem Tar-Archiv ein Ordner von Dateien hinzu
 * @access protected
 */
function _rex_a1_add_folder_to_tar(& $tar, $path, $dir)
{
  global $REX;

  $handle = opendir($path.$dir);
  $isMediafolder = realpath($path.$dir) == $REX['MEDIAFOLDER'];
  while (false !== ($file = readdir($handle)))
  {
    // Alles exportieren, au�er ... 
    // - addons verzeichnis im mediafolder (wird bei addoninstallation wiedererstellt)
    // - svn infos
    // - tmp prefix Dateien
    
    if($file == '.' || $file == '..' || $file == '.svn')
      continue;
    
    if(substr($file, 0, strlen($REX['TEMP_PREFIX'])) == $REX['TEMP_PREFIX'])
      continue;
      
    if($isMediafolder && $file == 'addons')
      continue;
      
    if (is_dir($path.$dir."/".$file))
    {
      _rex_a1_add_folder_to_tar($tar, $path.$dir."/", $file);
    }
    else
    {
      $tar->addFile($path.$dir."/".$file, true);
    }
  }
  closedir($handle);
}

