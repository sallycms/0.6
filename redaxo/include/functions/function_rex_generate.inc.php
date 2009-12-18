<?php


/**
 * Funktionensammlung f�r die generierung der Artikel/Templates/Kategorien/Metainfos.. etc.
 * @package redaxo4
 * @version svn:$Id$
 */

// ----------------------------------------- Alles generieren

/**
 * L�scht den vollst�ndigen Artikel-Cache.
 */
function rex_generateAll()
{
  global $REX, $I18N;

  // ----------------------------------------------------------- generated l�schen
  rex_deleteDir($REX['INCLUDE_PATH'].'/generated', FALSE);
  
  // ----------------------------------------------------------- generiere clang
  if(($MSG = rex_generateClang()) !== TRUE)
  {
    return $MSG;
  }
  
  // ----------------------------------------------------------- message
  $MSG = $I18N->msg('delete_cache_message');

  // ----- EXTENSION POINT
  $MSG = rex_register_extension_point('ALL_GENERATED', $MSG);

  return $MSG;
}

// ----------------------------------------- ARTICLE


/**
 * L�scht die gecachten Dateien eines Artikels. Wenn keine clang angegeben, wird
 * der Artikel in allen Sprachen gel�scht.
 *
 * @param $id ArtikelId des Artikels
 * @param [$clang ClangId des Artikels]
 * 
 * @return void
 */
function rex_deleteCacheArticle($id, $clang = null)
{
  global $REX;
  
  foreach($REX['CLANG'] as $_clang => $clang_name)
  {
    if($clang !== null && $clang != $_clang)
      continue;
      
    //rex_deleteCacheArticleMeta($id, $clang);
    rex_deleteCacheArticleContent($id, $clang);
    //rex_deleteCacheArticleLists($id, $clang);
  }
}

/**
 * L�scht die gecachten Meta-Dateien eines Artikels. Wenn keine clang angegeben, wird
 * der Artikel in allen Sprachen gel�scht.
 *
 * @param $id ArtikelId des Artikels
 * @param [$clang ClangId des Artikels]
 * 
 * @return void
 */
/*function rex_deleteCacheArticleMeta($id, $clang = null)
{
  global $REX;
  
  $cachePath = $REX['INCLUDE_PATH']. DIRECTORY_SEPARATOR .'generated'. DIRECTORY_SEPARATOR .'articles'. DIRECTORY_SEPARATOR;

  foreach($REX['CLANG'] as $_clang => $clang_name)
  {
    if($clang !== null && $clang != $_clang)
      continue;
      
    @unlink($cachePath . $id .'.'. $_clang .'.article');
  }
}*/

/**
 * L�scht die gecachten Content-Dateien eines Artikels. Wenn keine clang angegeben, wird
 * der Artikel in allen Sprachen gel�scht.
 *
 * @param $id ArtikelId des Artikels
 * @param [$clang ClangId des Artikels]
 * 
 * @return void
 */
function rex_deleteCacheArticleContent($id, $clang = null)
{
  global $REX;
  
  $cachePath = $REX['INCLUDE_PATH']. DIRECTORY_SEPARATOR .'generated'. DIRECTORY_SEPARATOR .'articles'. DIRECTORY_SEPARATOR;

  foreach($REX['CLANG'] as $_clang => $clang_name)
  {
    if($clang !== null && $clang != $_clang)
      continue;
      
    @unlink($cachePath . $id .'.'. $_clang .'.content');
  }
}

/**
 * L�scht die gecachten List-Dateien eines Artikels. Wenn keine clang angegeben, wird
 * der Artikel in allen Sprachen gel�scht.
 *
 * @param $id ArtikelId des Artikels
 * @param [$clang ClangId des Artikels]
 * 
 * @return void
 */
/*function rex_deleteCacheArticleLists($id, $clang = null)
{
  global $REX;
  
  $cachePath = $REX['INCLUDE_PATH']. DIRECTORY_SEPARATOR .'generated'. DIRECTORY_SEPARATOR .'articles'. DIRECTORY_SEPARATOR;

  foreach($REX['CLANG'] as $_clang => $clang_name)
  {
    if($clang !== null && $clang != $_clang)
      continue;
      
    @unlink($cachePath . $id .'.'. $_clang .'.alist');
    @unlink($cachePath . $id .'.'. $_clang .'.clist');
  }
}*/


/**
 * Generiert den Artikel-Cache der Metainformationen.
 * 
 * @param $article_id Id des zu generierenden Artikels
 * @param [$clang ClangId des Artikels]
 * 
 * @return TRUE bei Erfolg, FALSE wenn eine ung�tlige article_id �bergeben wird, sonst eine Fehlermeldung
 */
/*function rex_generateArticleMeta($article_id, $clang = null)
{
  global $REX, $I18N;
  
  foreach($REX['CLANG'] as $_clang => $clang_name)
  {
    if($clang !== null && $clang != $_clang)
      continue;
    
    $CONT = new rex_article;
    $CONT->setCLang($_clang);
    $CONT->getContentAsQuery(); // Content aus Datenbank holen, no cache
    $CONT->setEval(FALSE); // Content nicht ausf�hren, damit in Cachedatei gespeichert werden kann
    if (!$CONT->setArticleId($article_id)) return FALSE;

    // --------------------------------------------------- Artikelparameter speichern
    $params = array(
      'article_id' => $article_id,
      'last_update_stamp' => time()
    );

    $class_vars = OORedaxo::getClassVars();
    unset($class_vars[array_search('id', $class_vars)]);
    $db_fields = $class_vars;

    foreach($db_fields as $field)
      $params[$field] = $CONT->getValue($field);

    $content = '<?php'."\n";
    foreach($params as $name => $value)
    {
      $content .='$REX[\'ART\']['. $article_id .'][\''. $name .'\']['. $_clang .'] = \''. rex_addslashes($value,'\\\'') .'\';'."\n";
    }
    $content .= '?>';
    
    $article_file = $REX['INCLUDE_PATH']."/generated/articles/$article_id.$_clang.article";
    if (rex_put_file_contents($article_file, $content) === FALSE)
    {
      return $I18N->msg('article_could_not_be_generated')." ".$I18N->msg('check_rights_in_directory').$REX['INCLUDE_PATH']."/generated/articles/";
    }
    
    // damit die aktuellen �nderungen sofort wirksam werden, einbinden!
    require ($article_file);
  }
  
  return TRUE;
}*/

/**
 * Generiert den Artikel-Cache des Artikelinhalts.
 * 
 * @param $article_id Id des zu generierenden Artikels
 * @param [$clang ClangId des Artikels]
 * 
 * @return TRUE bei Erfolg, FALSE wenn eine ung�tlige article_id �bergeben wird, sonst eine Fehlermeldung
 */
function rex_generateArticleContent($article_id, $clang = null)
{
  global $REX, $I18N;
  
  foreach($REX['CLANG'] as $_clang => $clang_name)
  {
    if($clang !== null && $clang != $_clang)
      continue;
      
    $CONT = new rex_article;
    $CONT->setCLang($_clang);
    $CONT->getContentAsQuery(); // Content aus Datenbank holen, no cache
    $CONT->setEval(FALSE); // Content nicht ausf�hren, damit in Cachedatei gespeichert werden kann
    if (!$CONT->setArticleId($article_id)) return FALSE;
  
    // --------------------------------------------------- Artikelcontent speichern
    $article_content_file = $REX['INCLUDE_PATH']."/generated/articles/$article_id.$_clang.content";
    $article_content = "?>".$CONT->getArticle();
  
    // ----- EXTENSION POINT
    $article_content = rex_register_extension_point('GENERATE_FILTER', $article_content,
      array (
        'id' => $article_id,
        'clang' => $_clang,
        'article' => $CONT
      )
    );
  
    if (rex_put_file_contents($article_content_file, $article_content) === FALSE)
    {
      return $I18N->msg('article_could_not_be_generated')." ".$I18N->msg('check_rights_in_directory').$REX['INCLUDE_PATH']."/generated/articles/";
    }
  }
  
  return TRUE;
}

/**
 * Generiert alle *.article u. *.content Dateien eines Artikels/einer Kategorie
 *
 * @param $id ArtikelId des Artikels, der generiert werden soll
 * @param $refreshall Boolean Bei True wird der Inhalte auch komplett neu generiert, bei False nur die Metainfos
 * 
 * @return TRUE bei Erfolg, FALSE wenn eine ung�tlige article_id �bergeben wird
 */
function rex_generateArticle($id, $refreshall = true)
{
  global $REX, $I18N;

  // artikel generieren
  // vorraussetzung: articel steht schon in der datenbank
  //
  // -> infos schreiben -> abhaengig von clang
  // --> artikel infos / einzelartikel metadaten
  // --> artikel content / einzelartikel content
  // --> listen generieren // wenn startpage = 1
  // ---> artikel liste
  // ---> category liste

  foreach($REX['CLANG'] as $clang => $clang_name)
  {
    $CONT = new rex_article;
    $CONT->setCLang($clang);
    $CONT->getContentAsQuery(); // Content aus Datenbank holen, no cache
    $CONT->setEval(FALSE); // Content nicht ausf�hren, damit in Cachedatei gespeichert werden kann
    if (!$CONT->setArticleId($id)) return FALSE;
      
    // ----------------------- generiere generated/articles/xx.article
//    $MSG = rex_generateArticleMeta($id, $clang);
//    if($MSG === FALSE) return FALSE;
    
    if($refreshall)
    {
      // ----------------------- generiere generated/articles/xx.content
      $MSG = rex_generateArticleContent($id, $clang);
      if($MSG === FALSE) return FALSE;
    }

    // ----- EXTENSION POINT
    $MSG = rex_register_extension_point('CLANG_ARTICLE_GENERATED', '',
      array (
        'id' => $id,
        'clang' => $clang,
        'article' => $CONT
      )
    );

    if ($MSG != '')
      echo rex_warning($MSG);

    // --------------------------------------------------- Listen generieren
//    if ($CONT->getValue("startpage") == 1)
//    {
//      rex_generateLists($id);
//      rex_generateLists($CONT->getValue("re_id"));
//    }
//    else
//    {
//      rex_generateLists($CONT->getValue("re_id"));
//    }

  }

  // ----- EXTENSION POINT
  $MSG = rex_register_extension_point('ARTICLE_GENERATED','',array ('id' => $id));

  return TRUE;
}

/**
 * L�scht einen Artikel
 *
 * @param $id ArtikelId des Artikels, der gel�scht werden soll
 * 
 * @return Erfolgsmeldung bzw. Fehlermeldung bei Fehlern.
 */
function rex_deleteArticle($id)
{
  global $I18N;

  $return = _rex_deleteArticle($id);
	return $return;
}

/**
 * L�scht einen Artikel
 * 
 * @param $id ArtikelId des Artikels, der gel�scht werden soll
 * 
 * @return TRUE wenn der Artikel gel�scht wurde, sonst eine Fehlermeldung
 */
function _rex_deleteArticle($id)
{
  global $REX, $I18N;

  // artikel loeschen
  //
  // kontrolle ob erlaubnis nicht hier.. muss vorher geschehen
  //
  // -> startpage = 0
  // --> artikelfiles l�schen
  // ---> article
  // ---> content
  // ---> clist
  // ---> alist
  // -> startpage = 1
  // --> rekursiv aufrufen

  $return = array();
	$return['state'] = FALSE;

  if ($id == $REX['START_ARTICLE_ID'])
  {
  	$return['message'] = $I18N->msg('cant_delete_sitestartarticle');
    return $return;
  }
  if ($id == $REX['NOTFOUND_ARTICLE_ID'])
  {
  	$return['message'] = $I18N->msg('cant_delete_notfoundarticle');
    return $return;
  }

  $ART = new rex_sql;
  $ART->setQuery('select * from '.$REX['TABLE_PREFIX'].'article where id='.$id.' and clang=0');

  if ($ART->getRows() > 0)
  {
    $re_id = $ART->getValue('re_id');
    $return['state'] = true;
    if ($ART->getValue('startpage') == 1)
    {
    	$return['message'] = $I18N->msg('category_deleted');
      $SART = new rex_sql;
      $SART->setQuery('select * from '.$REX['TABLE_PREFIX'].'article where re_id='.$id.' and clang=0');
      for ($i = 0; $i < $SART->getRows(); $i ++)
      {
        $return['state'] = _rex_deleteArticle($id);
        $SART->next();
      }
    }else
    {
    	$return['message'] = $I18N->msg('article_deleted');
    }

    // Rekursion �ber alle Kindkategorien ergab keine Fehler
    // => l�schen erlaubt
    if($return['state'] === true)
    {
      //rex_deleteCacheArticle($id);
      $ART->setQuery('delete from '.$REX['TABLE_PREFIX'].'article where id='.$id);
      $ART->setQuery('delete from '.$REX['TABLE_PREFIX'].'article_slice where article_id='.$id);

      // --------------------------------------------------- Listen generieren
      //rex_generateLists($re_id);
    }

    return $return;
  }
  else
  {
  	$return['message'] = $I18N->msg('category_doesnt_exist');
    return $return;
  }
}

/**
 * Generiert alle *.alist u. *.clist Dateien einer Kategorie/eines Artikels
 *
 * @param $re_id   KategorieId oder ArtikelId, die erneuert werden soll
 * 
 * @return TRUE wenn der Artikel gel�scht wurde, sonst eine Fehlermeldung
 */
/*function rex_generateLists($re_id, $clang = null)
{
  global $REX, $I18N;

  // generiere listen
  //
  //
  // -> je nach clang
  // --> artikel listen
  // --> catgorie listen
  //

  foreach($REX['CLANG'] as $_clang => $clang_name)
  {
    if($clang !== null && $clang != $_clang)
      continue;
        
    // --------------------------------------- ARTICLE LIST

    $GC = new rex_sql;
    // $GC->debugsql = 1;
    $GC->setQuery("select * from ".$REX['TABLE_PREFIX']."article where (re_id=$re_id and clang=$_clang and startpage=0) OR (id=$re_id and clang=$_clang and startpage=1) order by prior,name");
    $content = "<?php\n";
    for ($i = 0; $i < $GC->getRows(); $i ++)
    {
      $id = $GC->getValue("id");
      $content .= "\$REX['RE_ID']['$re_id']['$i'] = \"".$GC->getValue("id")."\";\n";
      $GC->next();
    }
    $content .= "\n?>";

    $article_list_file = $REX['INCLUDE_PATH']."/generated/articles/$re_id.$_clang.alist";
    if (rex_put_file_contents($article_list_file, $content) === FALSE)
    {
      return $I18N->msg('article_could_not_be_generated')." ".$I18N->msg('check_rights_in_directory').$REX['INCLUDE_PATH']."/generated/articles/";
    }

    // --------------------------------------- CAT LIST

    $GC = new rex_sql;
    $GC->setQuery("select * from ".$REX['TABLE_PREFIX']."article where re_id=$re_id and clang=$_clang and startpage=1 order by catprior,name");
    $content = "<?php\n";
    for ($i = 0; $i < $GC->getRows(); $i ++)
    {
      $id = $GC->getValue("id");
      $content .= "\$REX['RE_CAT_ID']['$re_id']['$i'] = \"".$GC->getValue("id")."\";\n";
      $GC->next();
    }
    $content .= "\n?>";

    $article_categories_file = $REX['INCLUDE_PATH']."/generated/articles/$re_id.$_clang.clist";
    if (rex_put_file_contents($article_categories_file, $content) === FALSE)
    {
      return $I18N->msg('article_could_not_be_generated')." ".$I18N->msg('check_rights_in_directory').$REX['INCLUDE_PATH']."/generated/articles/";
    }
  }
  
  return TRUE;
}*/

/**
 * L�scht einen Ordner/Datei mit Unterordnern
 *
 * @param $file Zu l�schender Ordner/Datei
 * @param $delete_folders Ordner auch l�schen? false => nein, true => ja
 * 
 * @return TRUE bei Erfolg, sonst FALSE
 */
function rex_deleteDir($file, $delete_folders = FALSE)
{
  $debug = FALSE;
  $state = TRUE;
  
  $file = rtrim($file, DIRECTORY_SEPARATOR);

  if (file_exists($file))
  {
    // Fehler unterdr�cken, falls keine Berechtigung
    if (@ is_dir($file))
    {
      $handle = opendir($file);
      if (!$handle)
      {
        if($debug)
          echo "Unable to open dir '$file'<br />\n";
          
        return FALSE;
      }

      while ($filename = readdir($handle))
      {
        if ($filename == '.' || $filename == '..')
        {
          continue;
        }

        if (!rex_deleteDir($file.DIRECTORY_SEPARATOR.$filename, $delete_folders))
        {
          $state = FALSE;
        }
      }
      closedir($handle);

      if ($state !== TRUE)
      {
        return FALSE;
      }
      

      // Ordner auch l�schen?
      if ($delete_folders)
      {
        // Fehler unterdr�cken, falls keine Berechtigung
        if (!@ rmdir($file))
        {
          if($debug)
            echo "Unable to delete folder '$file'<br />\n";
            
          return FALSE;
        }
      }
    }
    else
    {
      // Datei l�schen
      // Fehler unterdr�cken, falls keine Berechtigung
      if (!@ unlink($file))
      {
        if($debug)
          echo "Unable to delete file '$file'<br />\n";
            
        return FALSE;
      }
    }
  }
  else
  {
    if($debug)
      echo "file '$file'not found!<br />\n";
    // Datei/Ordner existiert nicht
    return FALSE;
  }

  return TRUE;
}



/**
 * L�sch allen Datei in einem Ordner
 *
 * @param $file Pfad zum Ordner
 * 
 * @return TRUE bei Erfolg, sonst FALSE
 */
function rex_deleteFiles($file)
{
  $debug = FALSE;
  
  $file = rtrim($file, DIRECTORY_SEPARATOR);

  if (file_exists($file))
  {
    // Fehler unterdr�cken, falls keine Berechtigung
    if (@ is_dir($file))
    {
      $handle = opendir($file);
      if (!$handle)
      {
        if($debug)
          echo "Unable to open dir '$file'<br />\n";
          
        return FALSE;
      }

      while ($filename = readdir($handle))
      {
        if ($filename == '.' || $filename == '..')
        {
          continue;
        }
        
	      if (!@ unlink($file))
	      {
	        if($debug)
	          echo "Unable to delete file '$file'<br />\n";
	            
	        return FALSE;
	      }
    
      }
      closedir($handle);     
    }
    else
    {
      // Datei l�schen
      // Fehler unterdr�cken, falls keine Berechtigung
    }
  }
  else
  {
    if($debug)
      echo "file '$file'not found!<br />\n";
    // Datei/Ordner existiert nicht
    return FALSE;
  }

  return TRUE;
}

/**
 * Kopiert eine Ordner von $srcdir nach $dstdir
 * 
 * @param $srcdir Zu kopierendes Verzeichnis
 * @param $dstdir Zielpfad
 * @param $startdir Pfad ab welchem erst neue Ordner generiert werden
 * 
 * @return TRUE bei Erfolg, FALSE bei Fehler
 */
function rex_copyDir($srcdir, $dstdir, $startdir = "")
{
  global $REX;
  
  $debug = FALSE;
  $state = TRUE;
  
  if(!is_dir($dstdir))
  {
    $dir = '';
    foreach(explode(DIRECTORY_SEPARATOR, $dstdir) as $dirPart)
    {
      $dir .= $dirPart . DIRECTORY_SEPARATOR;
      if(strpos($startdir,$dir) !== 0 && !is_dir($dir))
      {
        if($debug)
          echo "Create dir '$dir'<br />\n";
          
        mkdir($dir);
        chmod($dir, $REX['DIRPERM']);
      }
    }
  }
  
  if($curdir = opendir($srcdir))
  {
    while($file = readdir($curdir))
    {
      if($file != '.' && $file != '..' && $file != '.svn')
      {
        $srcfile = $srcdir . DIRECTORY_SEPARATOR . $file;    
        $dstfile = $dstdir . DIRECTORY_SEPARATOR . $file;    
        if(is_file($srcfile))
        {
          $isNewer = TRUE;
          if(is_file($dstfile))
          {
            $isNewer = (filemtime($srcfile) - filemtime($dstfile)) > 0;
          }
            
          if($isNewer)
          {
            if($debug)
              echo "Copying '$srcfile' to '$dstfile'...";
            if(copy($srcfile, $dstfile))
            {
              touch($dstfile, filemtime($srcfile));
              chmod($dstfile, $REX['FILEPERM']);
              if($debug)
                echo "OK<br />\n";
            }
            else
            {
              if($debug)
               echo "Error: File '$srcfile' could not be copied!<br />\n";
              return FALSE;
            }
          }
        }
        else if(is_dir($srcfile))
        {
          $state = rex_copyDir($srcfile, $dstfile, $startdir) && $state;
        }
      }
    }
    closedir($curdir);
  }
  return $state;
}

// ----------------------------------------- CLANG

/**
 * L�scht eine Clang
 *
 * @param $id Zu l�schende ClangId
 * 
 * @return TRUE bei Erfolg, sonst FALSE
 */
function rex_deleteCLang($clang)
{
  global $REX;

  if ($clang == 0 || !isset($REX['CLANG'][$clang]))
    return FALSE;

  $clangName = $REX['CLANG'][$clang];
  unset ($REX['CLANG'][$clang]);

  $del = new rex_sql();
  $del->setQuery("delete from ".$REX['TABLE_PREFIX']."article where clang='$clang'");
  $del->setQuery("delete from ".$REX['TABLE_PREFIX']."article_slice where clang='$clang'");
  $del->setQuery("delete from ".$REX['TABLE_PREFIX']."clang where id='$clang'");
  
  // ----- EXTENSION POINT
  rex_register_extension_point('CLANG_DELETED','',
    array (
      'id' => $clang,
      'name' => $clangName,
    )
  );

  rex_generateAll();
  
  return TRUE;
}

/**
 * Erstellt eine Clang
 *
 * @param $id   Id der Clang
 * @param $name Name der Clang
 * 
 * @return TRUE bei Erfolg, sonst FALSE
 */
function rex_addCLang($id, $name)
{
  global $REX;
  
  if(isset($REX['CLANG'][$id])) return FALSE;

  $REX['CLANG'][$id] = $name;
  $file = $REX['INCLUDE_PATH']."/clang.inc.php";
  rex_replace_dynamic_contents($file, "\$REX['CLANG'] = ". var_export($REX['CLANG'], TRUE) .";\n");
  
  $add = new rex_sql();
  $add->setQuery("select * from ".$REX['TABLE_PREFIX']."article where clang='0'");
  $fields = $add->getFieldnames();

  $adda = new rex_sql;
  // $adda->debugsql = 1;
  for ($i = 0; $i < $add->getRows(); $i ++)
  {
    $adda->setTable($REX['TABLE_PREFIX']."article");

    foreach($fields as $key => $value)
    {
      if ($value == 'pid')
        echo ''; // nix passiert
      else
        if ($value == 'clang')
          $adda->setValue('clang', $id);
        else
          if ($value == 'status')
            $adda->setValue('status', '0'); // Alle neuen Artikel offline
      else
        $adda->setValue($value, $add->escape($add->getValue($value)));
    }

    $adda->insert();
    $add->next();
  }

  $add = new rex_sql();
  $add->setQuery("insert into ".$REX['TABLE_PREFIX']."clang set id='$id',name='$name'");

  // ----- EXTENSION POINT
  rex_register_extension_point('CLANG_ADDED','',array ('id' => $id, 'name' => $name));
  
  return TRUE;
}

/**
 * �ndert eine Clang
 *
 * @param $id   Id der Clang
 * @param $name Name der Clang
 * 
 * @return TRUE bei Erfolg, sonst FALSE
 */
function rex_editCLang($id, $name)
{
  global $REX;
  
  if(!isset($REX['CLANG'][$id])) return false;

  $REX['CLANG'][$id] = $name;
  $file = $REX['INCLUDE_PATH']."/clang.inc.php";
  rex_replace_dynamic_contents($file, "\$REX['CLANG'] = ". var_export($REX['CLANG'], TRUE) .";\n");

  $edit = new rex_sql;
  $edit->setQuery("update ".$REX['TABLE_PREFIX']."clang set name='$name' where id='$id'");

  // ----- EXTENSION POINT
  rex_register_extension_point('CLANG_UPDATED','',array ('id' => $id, 'name' => $name));
  
  return TRUE;
}

/**
 * Schreibt Addoneigenschaften in die Datei include/addons.inc.php
 * 
 * @param array Array mit den Namen der Addons aus dem Verzeichnis addons/
 * 
 * @return TRUE bei Erfolg, sonst FALSE
 */
function rex_generateAddons($ADDONS)
{
  global $REX;
  natsort($ADDONS);

  $content = "";
  foreach ($ADDONS as $addon)
  {
    if (!OOAddon :: isInstalled($addon))
      OOAddon::setProperty($addon, 'install', 0);

    if (!OOAddon :: isActivated($addon))
      OOAddon::setProperty($addon, 'status', 0);

    foreach(array('install', 'status') as $prop)
    {
      $content .= sprintf(
        "\$REX['ADDON']['%s']['%s'] = '%d';\n",
        $prop,
        $addon,
        OOAddon::getProperty($addon, $prop)
      );
    }
    $content .= "\n";      
  }

  // Da dieser Funktion �fter pro request aufgerufen werden kann,
  // hier die caches l�schen
  clearstatcache();

  $file = $REX['INCLUDE_PATH']."/addons.inc.php";
  if(rex_replace_dynamic_contents($file, $content) === FALSE)
  {
    return 'Datei "'.$file.'" hat keine Schreibrechte';
  }
  return TRUE;
}

/**
 * Schreibt Plugineigenschaften in die Datei include/plugins.inc.php
 * 
 * @param array Array mit den Namen der Plugins aus dem Verzeichnis addons/plugins
 * 
 * @return TRUE bei Erfolg, sonst eine Fehlermeldung
 */
function rex_generatePlugins($PLUGINS)
{
  global $REX;
  
  $content = "";
  foreach ($PLUGINS as $addon => $_plugins)
  {
    foreach($_plugins as $plugin)
    {
      if (!OOPlugin :: isInstalled($addon, $plugin))
        OOPlugin::setProperty($addon, $plugin, 'install', 0);
  
      if (!OOPlugin :: isActivated($addon, $plugin))
        OOPlugin::setProperty($addon, $plugin, 'status', 0);
  
      foreach(array('install', 'status') as $prop)
      {
        $content .= sprintf(
          "\$REX['ADDON']['plugins']['%s']['%s']['%s'] = '%d';\n",
          $addon,
          $prop,
          $plugin,
          OOPlugin::getProperty($addon, $plugin, $prop)
        );
      }
      $content .= "\n";
    }
  }

  // Da dieser Funktion �fter pro request aufgerufen werden kann,
  // hier die caches l�schen
  clearstatcache();

  $file = $REX['INCLUDE_PATH']."/plugins.inc.php";
  if(rex_replace_dynamic_contents($file, $content) === false)
  {
    return 'Datei "'.$file.'" hat keine Schreibrechte';
  }
  return TRUE;
}

/**
 * Schreibt Spracheigenschaften in die Datei include/clang.inc.php
 * 
 * @return TRUE bei Erfolg, sonst eine Fehlermeldung
 */
function rex_generateClang()
{
  global $REX;
  
  $lg = new rex_sql();
  $lg->setQuery("select * from ".$REX['TABLE_PREFIX']."clang order by id");
  
  $REX['CLANG'] = array();
  while($lg->hasNext())
  {
    $REX['CLANG'][$lg->getValue("id")] = $lg->getValue("name"); 
    $lg->next();
  }
  
  $file = $REX['INCLUDE_PATH']."/clang.inc.php";
  if(rex_replace_dynamic_contents($file, "\$REX['CLANG'] = ". var_export($REX['CLANG'], TRUE) .";\n") === FALSE)
  {
    return 'Datei "'.$file.'" hat keine Schreibrechte';
  }
  return TRUE;
}

/**
 * Generiert den TemplateCache im Filesystem
 * 
 * @param $template_id Id des zu generierenden Templates
 * 
 * @return TRUE bei Erfolg, sonst FALSE
 */
function rex_generateTemplate($template_id)
{
  global $REX;

  $sql = new rex_sql();
  $qry = 'SELECT * FROM '. $REX['TABLE_PREFIX']  .'template WHERE id = '.$template_id;
  $sql->setQuery($qry);

  if($sql->getRows() == 1)
  {
    $templatesDir = rex_template::getTemplatesDir();
    $templateFile = rex_template::getFilePath($template_id);

  	$content = $sql->getValue('content');
  	foreach($REX['VARIABLES'] as $var)
  	{
      if (is_string($var)) { // Es hat noch kein Autoloading f�r diese Klasse stattgefunden
        $tmp = new $var();
        $tmp = null;
        $var = $REX['VARIABLES'][$idx];
      }
      
  		$content = $var->getTemplate($content);
  	}
    if(rex_put_file_contents($templateFile, $content) !== FALSE)
    {
      return TRUE;
    }
    else
    {
      trigger_error('Unable to generate template '. $template_id .'!', E_USER_ERROR);

      if(!is_writable())
        trigger_error('directory "'. $templatesDir .'" is not writable!', E_USER_ERROR);
    }
  }
  else
  {
    trigger_error('Template with id "'. $template_id .'" does not exist!', E_USER_ERROR);
  }

  return FALSE;
}

// ----------------------------------------- generate helpers

/**
 * Escaped einen String
 *
 * @param $string Zu escapender String
 */
function rex_addslashes($string, $flag = '\\\'\"')
{
  if ($flag == '\\\'\"')
  {
    $string = str_replace('\\', '\\\\', $string);
    $string = str_replace('\'', '\\\'', $string);
    $string = str_replace('"', '\"', $string);
  }elseif ($flag == '\\\'')
  {
    $string = str_replace('\\', '\\\\', $string);
    $string = str_replace('\'', '\\\'', $string);
  }
  return $string;
}