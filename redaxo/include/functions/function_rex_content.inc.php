<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * @package redaxo4
 */

/**
 * Verschiebt einen Slice nach oben
 *
 * @param  int $slice_id  ID des Slices
 * @param  int $clang     ID der Sprache
 * @return array          ein Array welches den Status sowie eine Fehlermeldung beinhaltet
 */
function rex_moveSliceUp($slice_id, $clang)
{
	return rex_moveSlice($slice_id, $clang, 'moveup');
}

/**
 * Verschiebt einen Slice nach unten
 *
 * @param  int $slice_id  ID des Slices
 * @param  int $clang     ID der Sprache
 * @return array          ein Array welches den Status sowie eine Fehlermeldung beinhaltet
 */
function rex_moveSliceDown($slice_id, $clang)
{
	return rex_moveSlice($slice_id, $clang, 'movedown');
}

/**
 * Verschiebt einen Slice
 *
 * @param  int    $slice_id   ID des Slices
 * @param  int    $clang      ID der Sprache
 * @param  string $direction  Richtung in die verschoben werden soll
 * @return array              ein Array welches den Status sowie eine Fehlermeldung beinhaltet
 */
function rex_moveSlice($slice_id, $clang, $direction)
{
	global $REX, $I18N;

	$slice_id = (int) $slice_id;
	$clang    = (int) $clang;

	if (!in_array($direction, array('moveup', 'movedown'))) {
		trigger_error('rex_moveSlice: Unsupported direction "'.$direction.'"!', E_USER_ERROR);
	}

	// ctype beachten
	// verschieben / vertauschen
	// article regenerieren.

	$success = false;
	$message = $I18N->msg('slice_moved_error');

	// Slice finden

	$sliceData = rex_sql::fetch(
		'id, article_id, re_article_slice_id, ctype',
		'article_slice',
		'id = '.$slice_id.' AND clang = '.$clang
	);

	if ($sliceData !== false) {
		$slice_id         = (int) $sliceData['id'];
		$slice_article_id = (int) $sliceData['article_id'];
		$re_slice_id      = (int) $sliceData['re_article_slice_id'];
		$slice_ctype      = (int) $sliceData['ctype'];

		// Finde alle Slices dieses Artikels

		$allSlices = rex_sql::getArrayEx(
			'SELECT id, re_article_slice_id, ctype FROM #_article_slice WHERE article_id = '.$slice_article_id,
			'#_'
		);

		$SID    = array();
		$SREID  = array();
		$SCTYPE = array();

		foreach ($allSlices as $id => $data) {
			$re          = (int) $data['re_article_slice_id'];
			$id          = (int) $id;
			$SID[$re]    = $id;
			$SREID[$id]  = $re;
			$SCTYPE[$id] = (int) $data['ctype'];
		}

		$update  = new rex_sql();
		$message = $I18N->msg('slice_moved');
		$success = true;

		if ($direction == 'moveup') {
			if (isset($SREID[$slice_id]) && $SREID[$slice_id] > 0 && $SCTYPE[$SREID[$slice_id]] == $slice_ctype) {
				$update->setQuery('UPDATE #_article_slice SET re_article_slice_id = '.$SREID[$SREID[$slice_id]].' WHERE id = '.$slice_id, '#_');
				$update->setQuery('UPDATE #_article_slice SET re_article_slice_id = '.$slice_id.' WHERE id = '.$SREID[$slice_id], '#_');

				if (isset($SID[$slice_id]) && $SID[$slice_id] > 0) {
					$update->setQuery('UPDATE #_article_slice SET re_article_slice_id = '.$SREID[$slice_id].' WHERE id = '.$SID[$slice_id], '#_');
				}
			}
		}
		else {
			if (isset($SID[$slice_id]) && $SID[$slice_id] > 0 && $SCTYPE[$SID[$slice_id]] == $slice_ctype) {
				$update->setQuery('UPDATE #_article_slice SET re_article_slice_id = '.$SREID[$slice_id].' WHERE id = '.$SID[$slice_id], '#_');
				$update->setQuery('UPDATE #_article_slice SET re_article_slice_id = '.$SID[$slice_id].' WHERE id = '.$slice_id, '#_');

				if (isset($SID[$SID[$slice_id]]) && $SID[$SID[$slice_id]] > 0) {
					$update->setQuery('UPDATE #_article_slice SET re_article_slice_id = '.$slice_id.' WHERE id = '.$SID[$SID[$slice_id]], '#_');
				}
			}
		}

		rex_deleteCacheArticleContent($slice_article_id, $clang);
	}

	return array($success, $message);
}

/**
 * Löscht einen Slice
 *
 * @param  int $slice_id  ID des Slices
 * @return boolean        true bei Erfolg, sonst false
 */
function rex_deleteSlice($slice_id)
{
	$article_slice = OOArticleSlice::getArticleSliceById($slice_id);

	if ($article_slice !== null) {
		$sql       = new rex_sql();
		$nextslice = $article_slice->getNextSlice();

		if ($nextslice !== null){
			$sql->setQuery('UPDATE #_article_slice SET re_article_slice_id = '.$article_slice->getReId().' WHERE id = '.$nextslice->getId(), '#_');
		}

		sly_Service_Factory::getService('SliceValue')->delete(array('slice_id' => $article_slice->getSliceId()));
		sly_Service_Factory::getService('Slice')->delete(array('id' => $article_slice->getSliceId()));

		$sql->setQuery('DELETE FROM #_article_slice WHERE id = '.$slice_id, '#_');
		return $sql->getRows() == 1;
	}

	return false;
}

/**
 * Prüft, ob ein Modul für ein bestimmtes Slice im System bekannt ist.
 *
 * @return int  -1, falls kein Modul gefunden wurde, sonst die ID des Moduls
 */
function rex_slice_module_exists($sliceID, $clang)
{
	global $REX;

	$sliceID = (int) $sliceID;
	$clang   = (int) $clang;
	$from    = 'article_slice s LEFT JOIN '.$REX['DATABASE']['TABLE_PREFIX'].'module m ON s.modultyp_id = m.id';
	$id      = rex_sql::fetch('m.id', $from, 's.id = '.$sliceID.' AND s.clang = '.$clang);

	return $id === false ? -1 : $id;
}

/**
 * PrÃ¼ft, ob ein Modul im System bekannt ist.
 *
 * @return boolean  true oder ... false
 */
function rex_module_exists($moduleID)
{
	$moduleID = (int) $moduleID;

	if ($moduleID < 0) {
		return false;
	}

	return rex_sql::fetch('id', 'module', 'id = '.$moduleID) > 0;
}

/**
 * FÃ¼hrt alle pre-save Aktionen eines Moduls aus
 *
 * @param  int    $module_id   ID des Moduls
 * @param  string $function    Funktion/Modus der Aktion
 * @param  array  $REX_ACTION  Array zum Speichern des Status'
 * @return array               ein Array welches den Status sowie eine Fehlermeldung beinhaltet
 */
function rex_execPreSaveAction($module_id, $function, $REX_ACTION)
{
	global $REX;

	$module_id = (int) $module_id;
	$modebit   = rex_getActionModeBit($function);
	$message   = '';

	$ga = new rex_sql();
	$ga->setQuery(
		'SELECT presave '.
		'FROM #_module_action ma, #_action a '.
		'WHERE presave != "" AND ma.action_id = a.id AND module_id = '.$module_id.' AND '.
		'((a.presavemode & '.$modebit.') = '.$modebit.')',
		'#_'
	);

	for ($i = 0; $i < $ga->getRows(); ++$i) {
		$REX_ACTION['MSG'] = '';
		$iaction = $ga->getValue('presave');

		// *********************** WERTE ERSETZEN
		foreach (sly_Core::getVarTypes() as $obj) {
			$iaction = $obj->getACOutput($REX_ACTION, $iaction);
		}

		eval('?>'.$iaction);

		if ($REX_ACTION['MSG'] != '') {
			$message .= $REX_ACTION['MSG'].' | ';
		}

		$ga->next();
	}

	return array($message, $REX_ACTION);
}

/**
 * FÃ¼hrt alle post-save Aktionen eines Moduls aus
 *
 * @param  int    $module_id   ID des Moduls
 * @param  string $function    Funktion/Modus der Aktion
 * @param  array  $REX_ACTION  Array zum Speichern des Status'
 * @return string              eine Meldung
 */
function rex_execPostSaveAction($module_id, $function, $REX_ACTION)
{
	global $REX;

	$module_id = (int) $module_id;
	$modebit   = rex_getActionModeBit($function);
	$message   = '';

	$ga = new rex_sql();
	$ga->setQuery(
		'SELECT postsave '.
		'FROM #_module_action ma, #_action a '.
		'WHERE postsave != "" AND ma.action_id = a.id AND module_id = '.$module_id.' AND '.
		'((a.postsavemode & '.$modebit.') = '.$modebit.')',
		'#_'
	);

	for ($i = 0; $i < $ga->getRows(); ++$i) {
		$REX_ACTION['MSG'] = '';
		$iaction = $ga->getValue('postsave');

		// ***************** WERTE ERSETZEN UND POSTACTION AUSFÃœHREN
		foreach (sly_Core::getVarTypes() as $obj) {
			$iaction = $obj->getACOutput($REX_ACTION, $iaction);
		}

		eval('?>'.$iaction);

		if ($REX_ACTION['MSG'] != '') {
			$message .= ' | '.$REX_ACTION['MSG'];
		}

		$ga->next();
	}

	return $message;
}

/**
 * Ã¼bersetzt den Modus in das dazugehÃ¶rige Bitwort
 *
 * @param  string $function  Funktion/Modus der Aktion
 * @return int               ein Bitwort
 */
function rex_getActionModeBit($function)
{
	if ($function == 'edit') {
		$modebit = '2'; // pre-action and edit
	}
	elseif ($function == 'delete') {
		$modebit = '4'; // pre-action and delete
	}
	else {
		$modebit = '1'; // pre-action and add
	}

	return $modebit;
}

/**
 * Konvertiert einen Artikel zum Startartikel der eigenen Kategorie
 *
 * @param  int $neu_id  Artikel ID des Artikels, der Startartikel werden soll
 * @return boolean      true bei Erfolg, sonst false
 */
function rex_article2startpage($neu_id)
{
	global $REX;

	$neu_id = (int) $neu_id;
	$GAID   = array();

	// neuer Startartikel

	$neu = rex_sql::fetch('path, re_id', 'article', 'id = '.$neu_id.' AND startpage = 0 AND clang = 0 AND re_id <> 0');

	if ($neu === false) {
		return false;
	}

	$neu_path   = $neu['path'];
	$neu_cat_id = (int) $neu['re_id'];

	// in oberster Kategorie? -> return
	// (Ist bereits oben im SQL-Query mit dem "re_id <> 0" enthalten.)

//	if ($neu_cat_id == 0) {
//		return false;
//	}

	// alter Startartikel

	$alt_id = (int) $neu_cat_id;
	$alt    = rex_sql::fetch('path', 'article', 'id = '.$alt_id.' AND startpage = 1 AND clang = 0');

	if ($alt === false) {
		return false;
	}

	// Diese Felder werden von den beiden Artikeln ausgetauscht.

	$params = array('id', 'path', 'catname', 'startpage', 'catprior', 'status', 're_id');

	// cat felder sammeln.
	// Ist speziell für das Metainfo-AddOn enthalten, um dessen Daten gleich mit zu kopieren.

	$service = sly_Service_Factory::getService('AddOn');

	if ($service->isAvailable('metainfo')) {
		$db_fields = OORedaxo::getClassVars();

		foreach ($db_fields as $field) {
			if (substr($field, 0, 4) == 'cat_') {
				$params[] = $field;
			}
		}
	}

	$paramsToSelect = implode(',', $params);

	$alt = new rex_sql();
	$neu = new rex_sql();

	foreach (array_keys($REX['CLANG']) as $clang) {
		$data = rex_sql::getArrayEx(
			'SELECT '.$paramsToSelect.' FROM #_article '.
			'WHERE id IN ('.$neu_cat_id.','.$neu_id.') AND clang = '.$clang,
			'#_'
		);
		// alten Startartikel updaten

		$alt->setTable('article', true);
		$alt->setWhere('id = '.$alt_id.' AND clang = '.$clang);
		$alt->setValue('re_id', $neu_id);

		// neuen Startartikel updaten

		$neu->setTable('article', true);
		$neu->setWhere('id = '.$neu_id.' AND clang = '.$clang);
		$neu->setValue('re_id', $data[$neu_cat_id]['re_id']);

		// Austauschen der definierten Paramater

		foreach ($params as $param) {
			if ($param == 'id' || $param == 're_id') {
				continue;
			}

			$alt->setValue($param, $alt->escape($data[$neu_id][$param]));
			$neu->setValue($param, $neu->escape($data[$neu_cat_id][$param]));
		}

		$alt->update();
		$neu->update();

		$alt->flush();
		$neu->flush();

		$cache = sly_Core::cache();
       	$cache->delete('article', $neu_id.'_'.$clang);
		$cache->delete('category', $alt_id.'_'.$clang);
		$cache->delete('alist', $alt_id.'_'.$clang);
		$cache->delete('clist', $data[$neu_cat_id]['re_id'].'_'.$clang);
	}

	$alt = null;
	$neu = null;
	unset($alt, $neu);

	// alle Artikel suchen nach |art_id| und Pfade ersetzen
	// alle Artikel mit re_id alt_id suchen und ersetzen

	$update   = new rex_sql();
	$articles = rex_sql::getArrayEx('SELECT id FROM #_article WHERE path LIKE "%|'.$alt_id.'|%"', '#_');

	$update->setQuery('UPDATE #_article SET re_id = '.$neu_id.' WHERE re_id = '.$alt_id, '#_'); // re_id = X enthält path LIKE "%|X|%".
	$update->setQuery('UPDATE #_article SET path = REPLACE(path, "|'.$alt_id.'|", "|'.$neu_id.'|") WHERE path LIKE "%|'.$alt_id.'|%"', '#_');

	$update = null;
	unset($update);

  	return true;
}

/**
 * Kopiert eine Kategorie in eine andere
 *
 * @param int $from_cat_id  ID der Kategorie, die kopiert werden soll (Quelle)
 * @param int $to_cat_id    ID der Kategorie, IN die kopiert werden soll (Ziel)
 */
function rex_copyCategory($from_cat, $to_cat)
{
	// TODO: rex_copyCategory implementieren
}

/**
 * Kopiert die Metadaten eines Artikels in einen anderen Artikel
 *
 * @param  int   $from_id     Artikel-ID des Artikels, aus dem kopiert werden (Quell Artikel-ID)
 * @param  int   $to_id       Artikel-ID des Artikel, in den kopiert werden sollen (Ziel Artikel-ID)
 * @param  int   $from_clang  Sprach-ID des Artikels, aus dem kopiert werden soll (Quell Sprach-ID)
 * @param  int   $to_clang    Sprach-ID des Artikels, in den kopiert werden soll (Ziel Sprach-ID)
 * @param  array $params      Array von Spaltennamen, welche kopiert werden sollen
 * @return boolean            true bei Erfolg, sonst false
 */
function rex_copyMeta($from_id, $to_id, $from_clang = 0, $to_clang = 0, $params = array())
{
	global $REX;

	$from_clang = (int) $from_clang;
	$to_clang   = (int) $to_clang;
	$from_id    = (int) $from_id;
	$to_id      = (int) $to_id;

	if (!is_array($params)) {
		$params = array();
	}

	if ($from_id == $to_id && $from_clang == $to_clang) {
		return false;
	}

	$paramsToSelect = array_merge($params, array('clang', 'id'));
	$paramsToSelect = implode(',', $paramsToSelect);
	$articleData    = rex_sql::fetch($paramsToSelect, 'article', 'clang = '.$from_clang.' AND id = '.$from_id);

	if ($articleData !== false) {
		$update = new rex_sql();
		$update->setTable('article', true);
		$update->setWhere('clang = '.$to_clang.' AND id = '.$to_id);
		$update->addGlobalUpdateFields();

		foreach ($params as $param) {
			$update->setValue($value, $gc->escape($articleData[$param]));
		}

		$update->update();

		sly_Core::cache()->delete('article', $to_id.'_'.$to_clang);
		return true;
	}

	return false;
}

/**
 * Kopiert die Inhalte eines Artikels in einen anderen Artikel
 *
 * @param  int $from_id          Artikel-ID des Artikels, aus dem kopiert werden (Quell Artikel-ID)
 * @param  int $to_id            Artikel-ID des Artikel, in den kopiert werden sollen (Ziel Artikel-ID)
 * @param  int $from_clang       Sprach-ID des Artikels, aus dem kopiert werden soll (Quell Sprach-ID)
 * @param  int $to_clang         Sprach-ID des Artikels, in den kopiert werden soll (Ziel Sprach-ID)
 * @param  int $from_re_sliceid  ID des Slices, bei dem begonnen werden soll
 * @return boolean               true bei Erfolg, sonst false
 */
function rex_copyContent($from_id, $to_id, $from_clang = 0, $to_clang = 0, $from_re_sliceid = 0, $revision = 0)
{
	global $REX;
	$from_clang      = (int) $from_clang;
	$to_clang        = (int) $to_clang;
	$from_id         = (int) $from_id;
	$to_id           = (int) $to_id;
	$from_re_sliceid = (int) $from_re_sliceid;
	$revision        = (int) $revision;

	if ($from_id == $to_id && $from_clang == $to_clang) {
		return false;
	}

	$article_slice = OOArticleSlice::_getSliceWhere('article_id = '.$from_id.' AND clang = '.$from_clang.' AND re_article_slice_id = 0');
	$re_slice_id = 0;
	while($article_slice){
		$sliceservice = sly_Service_Factory::getService('Slice');
		$slice = $sliceservice->findById($article_slice->getSliceId());
		$slice = $sliceservice->copy($slice);

		$insert = new rex_sql();
		$insert->setTable('article_slice', true);
		$insert->setValue('clang', $insert->escape($to_clang));
		$insert->setValue('ctype', $insert->escape($article_slice->getCtype()));
		$insert->setValue('re_article_slice_id', $insert->escape($re_slice_id));
		$insert->setValue('slice_id', $insert->escape($slice->getId()));
		$insert->setValue('article_id', $insert->escape($to_id));
		$insert->setValue('modultyp_id', $insert->escape($slice->getModuleId()));
		$insert->setValue('revision', 0);
		$insert->addGlobalCreateFields();
		$insert->insert();
		$re_slice_id = $insert->last_insert_id;

		$article_slice = $article_slice->getNextSlice();
	}

	rex_deleteCacheArticleContent($to_id, $to_clang);
	return true;
}

/**
 * Kopieren eines Artikels von einer Kategorie in eine andere
 *
 * @param int $id          Artikel-ID des zu kopierenden Artikels
 * @param int $to_cat_id   KategorieId in die der Artikel kopiert werden soll
 *
 * @return boolean false bei Fehler, sonst die Artikel Id des neue kopierten Artikels
 */
function rex_copyArticle($id, $to_cat_id)
{
	global $REX;

	$id        = (int) $id;
	$to_cat_id = (int) $to_cat_id;
	$new_id    = '';

	foreach (array_keys($REX['CLANG']) as $clang) {
		// Validierung der id & from_cat_id
		$from_data = rex_sql::fetch('*', 'article', 'clang = '.$clang.' AND id = '.$id);

		if ($from_data) {
			// Validierung der to_cat_id
			// Query kann eingespart werden, wenn in die Root-Kategorie kopiert
			// werden soll.
			$to_data = $to_cat_id == 0 ? false : rex_sql::fetch('path, id, name', 'article', 'clang = '.$clang.' AND startpage = 1 AND id = '.$to_cat_id);

			if ($to_data || $to_cat_id == 0) {
				if ($to_data) {
					$path    = $to_data['path'].$to_data['id'].'|';
					$catname = $to_data['name'];
				}
				else {
					// In RootEbene
					$path    = '|';
					$catname = $from_data['name'];
				}

				$art_sql = new rex_sql();
				$art_sql->setTable($REX['DATABASE']['TABLE_PREFIX'].'article');

				if (empty($new_id)) {
					$new_id = $art_sql->setNewId('id');
				}


				$art_sql->setValue('id',        $new_id); // neuen auto_incrment erzwingen
				$art_sql->setValue('re_id',     $to_cat_id);
				$art_sql->setValue('path',      $path);
				$art_sql->setValue('catname',   $catname);
				$art_sql->setValue('catprior',  0);
				$art_sql->setValue('prior',     9999999); // Artikel als letzten Artikel in die neue Kat einfügen
				$art_sql->setValue('status',    0);       // kopierten Artikel offline setzen
				$art_sql->setValue('startpage', 0);
				$art_sql->addGlobalCreateFields();

				// schon gesetzte Felder nicht wieder überschreiben
				$dont_copy = array('id', 'pid', 're_id', 'catname', 'catprior', 'path', 'prior', 'status', 'createdate', 'createuser', 'startpage');

				foreach (array_diff(array_keys($from_data), $dont_copy) as $fld_name) {
					$art_sql->setValue($fld_name, $from_data[$fld_name]);
				}

				$art_sql->setValue('clang', $clang);
				$art_sql->insert();

				// ArticleSlices kopieren
				rex_copyContent($id, $new_id, $clang, $clang);

				// Prios neu berechnen
				rex_newArtPrio($to_cat_id, $clang, 1, 0);

				// ----- EXTENSION POINT
    			rex_register_extension_point('ART_ADDED', '',
			      	array (
				        'id' => $new_id,
				        'clang'  => $clang,
				        'status' => 0,
				        'name'   => $from_data['name'],
				        're_id'  => $to_cat_id,
				        'prior'  => 9999999,
				        'path'   => $path,
				        'template_id' => $from_data['template_id'],
			      	)
    			);
    			sly_Core::cache()->delete('alist', $to_cat_id);


				$art_sql->flush();
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}

	// Caches des Artikels löschen, in allen Sprachen
	//rex_deleteCacheArticle($id);

	// Caches der Kategorien löschen, da sich darin befindliche Artikel geändert haben
	rex_deleteCacheArticle($to_cat_id);

	return $new_id;
}

/**
 * Verschieben eines Artikels von einer Kategorie in eine Andere
 *
 * @param int $id          Artikel-ID des zu verschiebenden Artikels
 * @param int $from_cat_id KategorieId des Artikels, der Verschoben wird
 * @param int $to_cat_id   KategorieId in die der Artikel verschoben werden soll
 *
 * @return boolean true bei Erfolg, sonst false
 */
function rex_moveArticle($id, $from_cat_id, $to_cat_id)
{
	global $REX;

	$id          = (int) $id;
	$to_cat_id   = (int) $to_cat_id;
	$from_cat_id = (int) $from_cat_id;

	if ($from_cat_id == $to_cat_id) {
		return false;
	}

	foreach (array_keys($REX['CLANG']) as $clang) {
		// Validierung der id & from_cat_id
		$from_name = rex_sql::fetch('name', 'article', 'clang = '.$clang.' AND startpage <> 1 AND id = '.$id.' AND re_id = '.$from_cat_id);

		if ($from_name !== false) {
			// validierung der to_cat_id
			$to_data = $to_cat_id == 0 ? false : rex_sql::fetch('id, path, name', 'article', 'clang = '.$clang.' AND startpage = 1 AND id = '.$to_cat_id);

			if ($to_data || $to_cat_id == 0) {
				if ($to_data) {
					$re_id   = $to_data['id'];
					$path    = $to_data['path'].$to_data['id'].'|';
					$catname = $to_data['name'];
				}
				else {
					// In RootEbene
					$re_id   = 0;
					$path    = '|';
					$catname = $from_name;
				}

				$art_sql = new rex_sql();

				$art_sql->setTable($REX['DATABASE']['TABLE_PREFIX'].'article');
				$art_sql->setValue('re_id',   $re_id);
				$art_sql->setValue('path',    $path);
				$art_sql->setValue('catname', $catname);
				$art_sql->setValue('prior',   9999999);   // Artikel als letzten Artikel in die neue Kat einfÃ¼gen
				$art_sql->setValue('status',  0);         // kopierten Artikel offline setzen
				$art_sql->addGlobalUpdateFields();
				$art_sql->setWhere('clang = '.$clang.' AND startpage <> 1 AND id = '.$id.' AND re_id = '.$from_cat_id);
				$art_sql->update();

				// Prios neu berechnen
				rex_newArtPrio($to_cat_id, $clang, 1, 0);
				rex_newArtPrio($from_cat_id, $clang, 1, 0);

				// Cache aufrÃ¤umen
				$cache = sly_Core::getInstance()->cache();

				$cache->delete('article', $id.'_'.$clang);
				$cache->delete('alist', $from_cat_id.'_'.$clang);
				$cache->delete('alist', $to_cat_id.'_'.$clang);
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}

	return true;
}

/**
 * Verschieben einer Kategorie in eine andere
 *
 * @param int $from_cat_id KategorieId der Kategorie, die verschoben werden soll (Quelle)
 * @param int $to_cat_id   KategorieId der Kategorie, IN die verschoben werden soll (Ziel)
 *
 * @return boolean true bei Erfolg, sonst false
 */
function rex_moveCategory($from_cat, $to_cat)
{
	global $REX;

	$from_cat = (int) $from_cat;
	$to_cat   = (int) $to_cat;

	if ($from_cat == $to_cat) {
		// kann nicht in gleiche Kategroie kopiert werden
		return false;
	}
	else {
		// Kategorien vorhanden?
		// Ist die Zielkategorie im Pfad der Quellkategorie?

		$from_data = rex_sql::fetch('path, re_id', 'article', 'startpage = 1 AND id = '.$from_cat.' AND clang = 0');
		$to_data   = $to_cat == 0 ? false : rex_sql::fetch('path, re_id', 'article', 'startpage = 1 and id = '.$to_cat.' AND clang = 0');

		if (!$from_data || (!$to_data && $to_cat != 0)) {
			// eine der Kategorien existiert nicht
			return false;
		}
		else {
			if ($to_cat > 0) {
				$tcats = explode('|', $to_data['path']);

				if (in_array($from_cat, $tcats)) {
					// Zielkategorie ist in Quellkategorie -> nicht verschiebbar
					return false;
				}
			}

			// folgende cats regenerate

			$RC[$from_data['re_id']] = 1;
			$RC[$from_cat]           = 1;
			$RC[$to_cat]             = 1;

			if ($to_cat > 0) {
				$to_path  = $to_data['path'].$to_cat.'|';
				$to_re_id = $to_data['re_id'];
			}
			else {
				$to_path  = '|';
				$to_re_id = 0;
			}

			$from_path = $from_data['path'].$from_cat.'|';

			$up   = new rex_sql();
			$cats = $up->getArrayEx('SELECT id, re_id, path FROM '.$REX['DATABASE']['TABLE_PREFIX'].'article WHERE path LIKE "'.$from_path.'%" AND clang = 0');

			foreach ($cats as $id => $data) {
				// make update
				$new_path = $to_path.$from_cat.'|'.str_replace($from_path, '', $data['path']);

				// path Ã¤ndern und speichern
				$up->setTable($REX['DATABASE']['TABLE_PREFIX'].'article');
				$up->setWhere('id = '.$id);
				$up->setValue('path', $new_path);
				$up->update();
				$up->flush();

				// cat in gen eintragen
				$RC[$id] = 1;
			}

			// clang holen, max catprio holen und entsprechend updaten

			foreach (array_keys($REX['CLANG']) as $clang) {
				$catprior = (int) rex_sql::fetch('MAX(catprior)', 'article', 're_id = '.$to_cat.' AND clang = '.$clang);

				$up->setTable($REX['DATABASE']['TABLE_PREFIX'].'article');
				$up->setWhere('id = '.$from_cat.' AND clang = '.$clang);
				$up->setValue('path', $to_path);
				$up->setValue('re_id', $to_cat);
				$up->setValue('catprior', $catprior + 1);
				$up->update();
				$up->flush();

				// Cache aufräumen
				$cache = sly_Core::cache();
				$cache->delete('category', $from_cat.'_'.$clang);
				$cache->delete('clist', $to_cat.'_'.$clang);
			}

			// generiere Artikel neu - ohne neue Inhaltsgenerierung
			foreach ($RC as $id => $key) {
				rex_deleteCacheArticle($id);
			}

			foreach (array_keys($REX['CLANG']) as $clang) {
				rex_newCatPrio($from_data['re_id'], $clang, 0, 1);
			}
		}
	}

	return true;
}

/**
 * Berechnet die Prios der Kategorien in einer Kategorie neu
 *
 * @param $re_id    KategorieId der Kategorie, die erneuert werden soll
 * @param $clang    Sprach-ID der Kategorie, die erneuert werden soll
 * @param $new_prio Neue PrioNr der Kategorie
 * @param $old_prio Alte PrioNr der Kategorie
 *
 * @deprecated 4.1 - 26.03.2008
 * Besser die rex_organize_priorities() Funktion verwenden!
 *
 * @return void
 */
function rex_newCatPrio($re_id, $clang, $new_prio, $old_prio)
{
	global $REX;

	$re_id    = (int) $re_id;
	$clang    = (int) $clang;
	$new_prio = (int) $new_prio;
	$old_prio = (int) $old_prio;

	if ($new_prio != $old_prio) {
		$addsql = $new_prio < $old_prio ? 'desc' : 'asc';

		rex_organize_priorities(
			$REX['DATABASE']['TABLE_PREFIX'].'article',
			'catprior',
			'clang = '.$clang.' AND re_id = '.$re_id.' AND startpage = 1',
			'catprior, updatedate '.$addsql,
			'pid'
		);

		sly_Core::getInstance()->cache()->delete('clist', $re_id.'_'.$clang);
	}
}

/**
 * Berechnet die Prios der Artikel in einer Kategorie neu
 *
 * @param $re_id    KategorieId der Kategorie, die erneuert werden soll
 * @param $clang    Sprach-ID der Kategorie, die erneuert werden soll
 * @param $new_prio Neue PrioNr der Kategorie
 * @param $old_prio Alte PrioNr der Kategorie
 *
 * @deprecated 4.1 - 26.03.2008
 * Besser die rex_organize_priorities() Funktion verwenden!
 *
 * @return void
 */
function rex_newArtPrio($re_id, $clang, $new_prio, $old_prio)
{
	global $REX;

	$re_id    = (int) $re_id;
	$clang    = (int) $clang;
	$new_prio = (int) $new_prio;
	$old_prio = (int) $old_prio;

	if ($new_prio != $old_prio) {
		$addsql = $new_prio < $old_prio ? 'desc' : 'asc';

		rex_organize_priorities(
			$REX['DATABASE']['TABLE_PREFIX'].'article',
			'prior',
			'clang = '.$clang.' AND ((startpage <> 1 AND re_id = '.$re_id.') OR (startpage = 1 AND id = '.$re_id.'))',
			'prior, updatedate '. $addsql,
			'pid'
		);

		sly_Core::getInstance()->cache()->delete('alist', $re_id.'_'.$clang);
	}
}