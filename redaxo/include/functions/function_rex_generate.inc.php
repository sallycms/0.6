<?php

/**
 * Funktionensammlung für die generierung der Artikel/Templates/Kategorien/Metainfos.. etc.
 * @package redaxo4
 * @version svn:$Id$
 */

// ----------------------------------------- Alles generieren
// (heißt in REDAXO-Sprech: Alles löschen....)

/**
 * Löscht den vollständigen Artikel-Cache.
 */
function rex_generateAll()
{
	global $REX, $I18N;

	rex_deleteDir($REX['INCLUDE_PATH'].'/generated', false);

	if (($MSG = rex_generateClang()) !== true) {
		return $MSG;
	}

	$MSG = $I18N->msg('delete_cache_message');
	$MSG = rex_register_extension_point('ALL_GENERATED', $MSG);

	return $MSG;
}

// ----------------------------------------- ARTICLE

/**
 * Löscht die gecachten Dateien eines Artikels. Wenn keine clang angegeben, wird
 * der Artikel in allen Sprachen gelöscht.
 *
 * @param $id ArtikelId des Artikels
 * @param [$clang ClangId des Artikels]
 * 
 * @return void
 */
function rex_deleteCacheArticle($id, $clang = null)
{
	global $REX;
	
	$cache = Core::cache();

	foreach (array_keys($REX['CLANG']) as $_clang) {
		if ($clang !== null && $clang != $_clang) {
			continue;
		}

		rex_deleteCacheArticleContent($id, $clang);

		$cache->delete('article', $id.'_'.$clang);
		$cache->delete('alist', $id.'_'.$clang);
		$cache->delete('clist', $id.'_'.$clang);
	}
}

/**
 * Löscht die gecachten Content-Dateien eines Artikels. Wenn keine clang angegeben, wird
 * der Artikel in allen Sprachen gelöscht.
 *
 * @param $id ArtikelId des Artikels
 * @param [$clang ClangId des Artikels]
 * 
 * @return void
 */
function rex_deleteCacheArticleContent($id, $clang = null)
{
	global $REX;

	$cachePath = $REX['INCLUDE_PATH'].'/generated/articles/';
	$level     = error_reporting(0);

	foreach (array_keys($REX['CLANG']) as $_clang) {
		if ($clang !== null && $clang != $_clang) {
			continue;
		}
		
		unlink($cachePath.$id.'.'.$_clang.'.content');
	}
	
	error_reporting($level);
}

function rex_deleteCacheSliceContent($slice_id)
{
	global $REX;

	$cachePath = $REX['INCLUDE_PATH'].'/generated/articles/';
	@unlink($cachePath.$slice_id.'.slice');
}

/**
 * Generiert den Artikel-Cache des Artikelinhalts.
 * 
 * @param $article_id Id des zu generierenden Artikels
 * @param [$clang ClangId des Artikels]
 * 
 * @return true bei Erfolg, false wenn eine ungütlige article_id übergeben wird, sonst eine Fehlermeldung
 */
function rex_generateArticleContent($article_id, $clang = null)
{
	global $REX, $I18N;

	foreach (array_keys($REX['CLANG']) as $_clang) {
		if ($clang !== null && $clang != $_clang) {
			continue;
		}

		$query =
			'SELECT slice.id, slice.ctype, slice.re_article_slice_id '.
			'FROM #_article_slice slice LEFT JOIN #_article a ON slice.article_id = a.id '.
			'WHERE slice.article_id = '.$article_id.' AND a.clang = '.$clang.' '.
			'ORDER BY slice.re_article_slice_id ASC';
		
		$sql             = new rex_sql();
		$slices          = $sql->getArray(str_replace('#_', $REX['TABLE_PREFIX'], $query));
		$article_content = '';
		
		if (!empty($slices)) {
			$sliceArray = array();
			
			foreach ($slices as $slice) {
				$re_id = $slice['re_article_slice_id'];

				$sliceArray[$re_id]['id'] = $slice['id'];
				$sliceArray[$re_id]['ctype'] = $slice['ctype'];
			}

			$oldctype      = null;
			$ctype_content = array();
			$idx           = 0;
			$sliceCount    = count($sliceArray);
			
			for ($i = 0; $i < $sliceCount; ++$i) {
				$ctype = $sliceArray[$idx]['ctype'];
				$id    = $sliceArray[$idx]['id'];
				
				if (!$oldctype) {
					$article_content .= '<?php if ($this->ctype == '.$ctype.' || $this->ctype == -1) { ?>';
				}
				elseif ($oldctype !== $ctype) {
					$article_content .= '<?php print '.implode('.', $ctype_content).'; ?>';
					$ctype_content    = array();
					$article_content .= '<?php } elseif ($this->ctype == '.$ctype.' || $this->ctype == -1) { ?>';
				}
				
				$oldctype = $ctype;
				$idx      = $id;
				
				$ctype_content[] = 'OOArticleSlice::getArticleSliceById('.$id.','.$clang.')->getContent()';
			}
			
			$article_content .= '<?php print '.implode('.', $ctype_content).'; ?>';
			$article_content .= '<?php } ?>';
		}
		
		$article_content_file = $REX['INCLUDE_PATH']."/generated/articles/$article_id.$_clang.content";

		if (rex_put_file_contents($article_content_file, $article_content) === false) {
			return $I18N->msg('article_could_not_be_generated').' '.$I18N->msg('check_rights_in_directory').$REX['INCLUDE_PATH'].'/generated/articles/';
		}
	}

	return true;
}

/**
 * Generiert alle *.article u. *.content Dateien eines Artikels/einer Kategorie
 *
 * @param $id ArtikelId des Artikels, der generiert werden soll
 * @param $refreshall Boolean Bei True wird der Inhalte auch komplett neu generiert, bei False nur die Metainfos
 * 
 * @return true bei Erfolg, false wenn eine ungütlige article_id übergeben wird
 */
function rex_generateArticle($id, $refreshall = true)
{
	global $REX, $I18N;

	// Artikel generieren
	// Vorraussetzung: Artikel steht schon in der Datenbank
	//
	// -> Infos schreiben -> abhängig von clang
	// --> Artikel infos / Einzelartikel metadaten
	// --> Artikel content / Einzelartikel content
	// --> Listen generieren // wenn startpage = 1
	// ---> Artikelliste
	// ---> Kategorieliste

	foreach (array_keys($REX['CLANG']) as $clang) {
		$CONT = new rex_article();
		$CONT->setCLang($clang);
		$CONT->getContentAsQuery(); // Content aus Datenbank holen, no cache
		$CONT->setEval(false);      // Content nicht ausführen, damit in Cachedatei gespeichert werden kann
		
		if (!$CONT->setArticleId($id)) {
			return false;
		}

		// generiere generated/articles/xx.article
		
//		$MSG = rex_generateArticleMeta($id, $clang);
//		if($MSG === false) return false;

		if ($refreshall) {
			// generiere generated/articles/xx.content
			$MSG = rex_generateArticleContent($id, $clang);
			
			if ($MSG === false) {
				return false;
			}
		}

		$MSG = rex_register_extension_point('CLANG_ARTICLE_GENERATED', '', array(
			'id'      => $id,
			'clang'   => $clang,
			'article' => $CONT
		));

		if (!empty($MSG)) {
			print rex_warning($MSG);
		}

		// Listen generieren
		
//		if ($CONT->getValue("startpage") == 1)
//		{
//		  rex_generateLists($id);
//		  rex_generateLists($CONT->getValue("re_id"));
//		}
//		else
//		{
//		  rex_generateLists($CONT->getValue("re_id"));
//		}
	}

	rex_register_extension_point('ARTICLE_GENERATED', '', array('id' => $id));
	return true;
}

/**
 * Löscht einen Artikel
 *
 * @param $id ArtikelId des Artikels, der gelöscht werden soll
 * 
 * @return Erfolgsmeldung bzw. Fehlermeldung bei Fehlern.
 */
function rex_deleteArticle($id)
{
	return _rex_deleteArticle($id);
}

/**
 * Löscht einen Artikel
 * 
 * @param $id ArtikelId des Artikels, der gelöscht werden soll
 * 
 * @return true wenn der Artikel gelöscht wurde, sonst eine Fehlermeldung
 */
function _rex_deleteArticle($id)
{
	global $REX, $I18N;

	// Artikel löschen
	//
	// Kontrolle ob Erlaubnis nicht hier.. muss vorher geschehen
	//
	// -> startpage = 0
	// --> Artikelfiles löschen
	// ---> article
	// ---> content
	// ---> clist
	// ---> alist
	// -> startpage = 1
	// --> rekursiv aufrufen

	$return = array();
	$return['state'] = false;

	if ($id == $REX['START_ARTICLE_ID']) {
		$return['message'] = $I18N->msg('cant_delete_sitestartarticle');
		return $return;
	}
	
	if ($id == $REX['NOTFOUND_ARTICLE_ID']) {
		$return['message'] = $I18N->msg('cant_delete_notfoundarticle');
		return $return;
	}

	$articleData = rex_sql::fetch('re_id, startpage', 'article', 'id = '.$id.' AND clang = 0');

	if ($articleData !== false) {
		$re_id = (int) $articleData['re_id'];
		$return['state'] = true;
		
		if ($articleData['startpage'] == 1) {
			$return['message'] = $I18N->msg('category_deleted');
			$children = rex_sql::getArrayEx('SELECT id FROM #_article WHERE re_id = '.$id.' AND clang = 0', '#_');
			
			foreach ($children as $child) {
				$retval = _rex_deleteArticle($child);;
				$return['state'] &= $retval['state'];
				
				if (!$retval['status']) {
					$return['message'] .= "<br />\n$retval[message]";
				}
			}
		}
		else {
			$return['message'] = $I18N->msg('article_deleted');
		}

		// Rekursion über alle Kindkategorien ergab keine Fehler
		// => löschen erlaubt
		
		if ($return['state'] === true) {
			//rex_deleteCacheArticle($id);
			
			$sql = new rex_sql();
			$sql->setQuery('DELETE FROM #_article WHERE id = '.$id, '#_');
			$sql->setQuery('DELETE FROM #_article_slice WHERE article_id = '.$id, '#_');
			$sql = null;
			
			// Listen generieren (auskommtiert, weil: werden layze erzeugt)
			//rex_generateLists($re_id);
		}

		return $return;
	}
	
	$return['message'] = $I18N->msg('category_doesnt_exist');
	return $return;
}

/**
 * Löscht einen Ordner/Datei mit Unterordnern
 *
 * @param  string $file            Zu löschender Ordner/Datei
 * @param  bool   $delete_folders  Ordner auch löschen? false => nein, true => ja
 * @param  bool   $isRecursion     wird beim rekursiven Aufruf auf true gesetzt, um zu vermeiden, immer wieder das Error-Reporting auf 0 zu setzen
 * @return bool                    true bei Erfolg, sonst false
 */
function rex_deleteDir($file, $delete_folders = false, $isRecursion = false)
{
	$state = true;
	$level = $isRecursion ? -1 : error_reporting(0);

	$file = rtrim($file, DIRECTORY_SEPARATOR);

	if (file_exists($file)) {
		if (is_dir($file)) {
			$handle = opendir($file);
			
			if (!$handle) {
				if (!$isRecursion) error_reporting($level);
				return false;
			}

			while ($filename = readdir($handle)) {
				if ($filename == '.' || $filename == '..' || $filename == '.svn') {
					continue;
				}

				if (!rex_deleteDir($file.DIRECTORY_SEPARATOR.$filename, $delete_folders, true)) {
					$state = false;
				}
			}
			
			closedir($handle);

			if ($state !== true) {
				if (!$isRecursion) error_reporting($level);
				return false;
			}

			// Ordner auch löschen?
			
			if ($delete_folders) {
				if (!rmdir($file)) {
					if (!$isRecursion) error_reporting($level);
					return false;
				}
			}
		}
		else {
			// Datei löschen
			
			if (!unlink($file)) {
				if (!$isRecursion) error_reporting($level);
				return false;
			}
		}
	}
	else {
		// Datei/Ordner existiert nicht
		
		if (!$isRecursion) error_reporting($level);
		return false;
	}

	if (!$isRecursion) error_reporting($level);
	return true;
}

/**
 * Lösch allen Datei in einem Ordner
 *
 * @param  string $file  Pfad zum Ordner
 * @return bool          true bei Erfolg, sonst false
 */
function rex_deleteFiles($file)
{
	$file  = rtrim($file, DIRECTORY_SEPARATOR);
	$level = error_reporting(0);

	if (file_exists($file)) {
		if (is_dir($file)) {
			$handle = opendir($file);
			
			if (!$handle) {
				error_reporting($level);
				return false;
			}

			while ($filename = readdir($handle)) {
				if ($filename == '.' || $filename == '..') {
					continue;
				}

				if (!unlink($file)) {
					error_reporting($level);
					return false;
				}
			}
			
			closedir($handle);     
		}
	}
	else {
		// Datei/Ordner existiert nicht
		
		error_reporting($level);
		return false;
	}

	error_reporting($level);
	return true;
}

/**
 * Kopiert eine Ordner von $srcdir nach $dstdir
 * 
 * @param  string $srcdir    Zu kopierendes Verzeichnis
 * @param  string $dstdir    Zielpfad
 * @param  string $startdir  Pfad ab welchem erst neue Ordner generiert werden
 * @return bool              true bei Erfolg, false bei Fehler
 */
function rex_copyDir($srcdir, $dstdir, $startdir = '')
{
	global $REX;

	$state = true;

	if (!is_dir($dstdir)) {
		$dir = '';
		
		foreach (explode(DIRECTORY_SEPARATOR, $dstdir) as $dirPart) {
			$dir .= $dirPart.DIRECTORY_SEPARATOR;
			
			if (strpos($startdir, $dir) !== 0 && !is_dir($dir)) {
				mkdir($dir);
				chmod($dir, $REX['DIRPERM']);
			}
		}
	}

	if ($curdir = opendir($srcdir)) {
		while ($file = readdir($curdir)) {
			if ($file[0] != '.') {
				$srcfile = $srcdir.DIRECTORY_SEPARATOR.$file;
				$dstfile = $dstdir.DIRECTORY_SEPARATOR.$file;
				
				if (is_file($srcfile)) {
					$isNewer = true;
					
					if (is_file($dstfile)) {
						$isNewer = (filemtime($srcfile) - filemtime($dstfile)) > 0;
					}

					if ($isNewer) {
						if (copy($srcfile, $dstfile)) {
							touch($dstfile, filemtime($srcfile));
							chmod($dstfile, $REX['FILEPERM']);
						}
						else {
							return false;
						}
					}
				}
				elseif (is_dir($srcfile)) {
					$state &= rex_copyDir($srcfile, $dstfile, $startdir);
				}
			}
		}
		
		closedir($curdir);
	}
	
	return $state;
}

// ----------------------------------------- CLANG

/**
 * Löscht eine Clang
 *
 * @param $id Zu löschende ClangId
 * 
 * @return true bei Erfolg, sonst false
 */
function rex_deleteCLang($clang)
{
	global $REX;
	
	$clang = (int) $clang;

	if ($clang == 0 || !isset($REX['CLANG'][$clang])) {
		return false;
	}

	$clangName = $REX['CLANG'][$clang];
	unset($REX['CLANG'][$clang]);

	$del = new rex_sql();
	$del->setQuery('DELETE FROM #_article WHERE clang = '.$clang, '#_');
	$del->setQuery('DELETE FROM #_article_slice WHERE clang = '.$clang, '#_');
	$del->setQuery('DELETE FROM #_clang WHERE id = '.$clang, '#_');
	unset($del);

	rex_register_extension_point('CLANG_DELETED','', array(
		'id'   => $clang,
		'name' => $clangName,
	));

	rex_generateAll();
	return true;
}

/**
 * Erstellt eine Clang
 *
 * @param  int    $id    Id der Clang
 * @param  string $name  Name der Clang
 * @return bool          true bei Erfolg, sonst false
 */
function rex_addCLang($id, $name)
{
	global $REX;
	
	$id = (int) $id;

	if (isset($REX['CLANG'][$id])) {
		return false;
	}

	$REX['CLANG'][$id] = $name;
	
	$file = $REX['INCLUDE_PATH'].'/clang.inc.php';
	$sql  = new rex_sql();
	
	rex_replace_dynamic_contents($file, '$REX[\'CLANG\'] = '.var_export($REX['CLANG'], true).";\n");

	$sql->setQuery(
		'INSERT INTO #_article (id,re_id,name,catname,catprior,attributes,'.
			'startpage,prior,path,status,createdate,updatedate,template_id,clang,createuser,'.
			'updateuser,revision) '.
			'SELECT id,re_id,name,catname,catprior,attributes,startpage,prior,path,0,createdate,'.
				'updatedate,template_id,'.$id.',createuser,updateuser,revision '.
				'FROM #_article WHERE clang = 0', '#_'
	);

	$sql->setQuery('INSERT INTO '.$REX['TABLE_PREFIX'].'clang (id,name,revision) VALUES ('.$id.', "'.$sql->escape($name).'", 0)');
	unset($sql);
	
	rex_register_extension_point('CLANG_ADDED', '', array('id' => $id, 'name' => $name));
	return true;
}

/**
 * Ändert eine Clang
 *
 * @param  int    $id    Id der Clang
 * @param  string $name  Name der Clang
 * @return bool          true bei Erfolg, sonst false
 */
function rex_editCLang($id, $name)
{
	global $REX;
	
	$id = (int) $id;

	if (!isset($REX['CLANG'][$id])) {
		return false;
	}

	$REX['CLANG'][$id] = $name;
	$file = $REX['INCLUDE_PATH'].'/clang.inc.php';
	rex_replace_dynamic_contents($file, '$REX[\'CLANG\'] = '.var_export($REX['CLANG'], true).";\n");

	$edit = new rex_sql();
	$edit->setQuery('UPDATE '.$REX['TABLE_PREFIX'].'clang SET name = "'.$edit->escape($name).'" WHERE id = '.$id);
	unset($edit);

	rex_register_extension_point('CLANG_UPDATED', '', array('id' => $id, 'name' => $name));
	return true;
}

/**
 * Schreibt Addoneigenschaften in die Datei include/addons.inc.php
 * 
 * @param  array $addons  Array mit den Namen der Addons aus dem Verzeichnis addons/
 * @return bool           true bei Erfolg, sonst false
 */
function rex_generateAddons($addons)
{
	global $REX;
	natsort($addons);

	$content = array();
	
	foreach ($addons as $addon) {
		if (!OOAddon::isInstalled($addon)) {
			OOAddon::setProperty($addon, 'install', 0);
		}

		if (!OOAddon::isActivated($addon)) {
			OOAddon::setProperty($addon, 'status', 0);
		}

		foreach (array('install', 'status') as $prop) {
			$content[] = sprintf("\$REX['ADDON']['%s']['%s'] = %d;",
				$prop, $addon, OOAddon::getProperty($addon, $prop)
			);
		}    
	}

	// Da dieser Funktion mehrfach pro Request aufgerufen werden kann,
	// hier die Caches löschen.
	
	clearstatcache();

	$content = implode("\n", $content)."\n";
	$file    = $REX['INCLUDE_PATH'].'/addons.inc.php';
	
	if (rex_replace_dynamic_contents($file, $content) === false) {
		return 'Datei "'.$file.'" hat keine Schreibrechte.';
	}
	
	return true;
}

/**
 * Schreibt Plugineigenschaften in die Datei include/plugins.inc.php
 * 
 * @param  array $plugins  Array mit den Namen der Plugins aus dem Verzeichnis addons/plugins
 * @return mixed           true bei Erfolg, sonst eine Fehlermeldung
 */
function rex_generatePlugins($plugins)
{
	global $REX;

	$content = array();
	
	foreach ($plugins as $addon => $_plugins) {
		foreach ($_plugins as $plugin) {
			if (!OOPlugin::isInstalled($addon, $plugin)) {
				OOPlugin::setProperty($addon, $plugin, 'install', 0);
			}

			if (!OOPlugin::isActivated($addon, $plugin)) {
				OOPlugin::setProperty($addon, $plugin, 'status', 0);
			}

			foreach (array('install', 'status') as $prop) {
				$content[] = sprintf("\$REX['ADDON']['plugins']['%s']['%s']['%s'] = %d;",
					$addon, $prop, $plugin, OOPlugin::getProperty($addon, $plugin, $prop)
				);
			}
		}
	}

	// Da dieser Funktion öfter pro request aufgerufen werden kann,
	// hier die caches löschen
	clearstatcache();

	$content = implode(PHP_EOL, $content).PHP_EOL;
	$file    = $REX['INCLUDE_PATH'].'/plugins.inc.php';
	
	if (rex_replace_dynamic_contents($file, $content) === false) {
		return 'Datei "'.$file.'" hat keine Schreibrechte.';
	}
	
	return true;
}

/**
 * Schreibt Spracheigenschaften in die Datei include/clang.inc.php
 * 
 * @return mixed  true bei Erfolg, sonst eine Fehlermeldung
 */
function rex_generateClang()
{
	global $REX;

	$data = rex_sql::getArrayEx('SELECT id,name FROM #_clang ORDER BY id', '#_');
	$REX['CLANG'] = $data === false ? array() : $data;

	$content = '$REX[\'CLANG\'] = '.var_export($REX['CLANG'], true).";\n";
	$file    = $REX['INCLUDE_PATH'].'/clang.inc.php';
	
	if (rex_replace_dynamic_contents($file, $content) === false) {
		return 'Datei "'.$file.'" hat keine Schreibrechte';
	}
	
	return true;
}


/**
 * Escaped einen String
 *
 * @param $string Zu escapender String
 */
function rex_addslashes($string, $flag = '\\\'\"')
{
	if ($flag == '\\\'\"') {
		$string = str_replace('\\', '\\\\', $string);
		$string = str_replace('\'', '\\\'', $string);
		$string = str_replace('"', '\"', $string);
	}
	elseif ($flag == '\\\'') {
		$string = str_replace('\\', '\\\\', $string);
		$string = str_replace('\'', '\\\'', $string);
	}
	
	return $string;
}
