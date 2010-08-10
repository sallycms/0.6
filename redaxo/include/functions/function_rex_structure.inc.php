<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Funktionensammlung für die Strukturverwaltung
 *
 * @package redaxo4
 */

/**
 * Erstellt eine neue Kategorie
 *
 * @param  int   $parentID KategorieId in der die neue Kategorie erstellt werden soll
 * @param  array $data     Array mit den Daten der Kategorie (muss enthalten: catname, status)
 * @return array           Ein Array welches den status sowie eine Fehlermeldung beinhaltet
 */
function rex_addCategory($parentID, $data)
{
	global $REX, $I18N;

	$message  = $I18N->msg('category_added_and_startarticle_created');
	$sql      = new rex_sql();
	$parentID = (int) $parentID;

	if (!is_array($data)) {
		trigger_error('Expecting $data to be an array!', E_USER_ERROR);
	}

	$startpageTemplates = array();

	if (!empty($parentID)) {
		// TemplateId vom Startartikel der jeweiligen Sprache vererben
		$startpageTemplates = rex_sql::getArrayEx(
			'SELECT clang, template FROM #_article '.
			'WHERE id = '.$parentID.' AND startpage = 1', '#_'
		);
	}

	if (isset($data['catprior']) && $data['catprior'] <= 0) {
		$data['catprior'] = 1;
	}
	else {
		$maxPrior         = rex_sql::fetch('MAX(catprior)', 'article', 're_id = '.$parentID.' AND catprior <> 0 AND clang = 0') + 1;
		$data['catprior'] = $data['catprior'] > $maxPrior ? $maxPrior : $data['catprior'];
	}

	if (!isset($data['name'])) {
		$data['name'] = $data['catname'];
	}

	if (!isset($data['status'])) {
		$data['status'] = false;
	}

	if (!isset($data['path'])) {
		if ($parentID != 0) {
			$data['path']  = rex_sql::fetch('path', 'article', 'id = '.$parentID.' AND startpage = 1 AND clang = 0');
			$data['path'] .= $parent.'|';
		}
		else {
			$data['path'] = '|';
		}
	}

	// Wir müssen uns die Kategoriedaten nur merken, wenn es eine Erweiterung
	// gibt, die sie nutzen möchte.
	// AddOns, die stattdessen CAT_ADDED_NEW verwenden, erhalten den gleichen EP,
	// nur ohne das rex_sql-Objekt. Das spart Speicher, wenn er gar nicht
	// benötigt wird.

	$hasOldExtensions = rex_extension_is_registered('CAT_ADDED');
	$hasNewExtensions = rex_extension_is_registered('CAT_ADDED_NEW');

	// Die ID ist für alle Sprachen gleich und entspricht einfach der aktuell
	// höchsten plus 1.

	$newID = rex_sql::fetch('MAX(id)', 'article', 'clang = 0') + 1;

	// Bevor wir die neuen Datensätze einfügen, machen wir in den sortierten
	// Listen (je eine pro Sprache) Platz, indem wir alle Kategorien, deren
	// Priorität größergleich der Priorität der neuen Kategorie ist, um eine
	// Position nach unten schieben.

	$sql->setQuery(
		'UPDATE #_article SET catprior = catprior + 1 '.
		'WHERE re_id = '.$parentID.' AND catprior <> 0 AND catprior >= '.$data['catprior'].' '.
		'ORDER BY catprior ASC', '#_'
	);

	// Kategorie in allen Sprachen anlegen

	$records     = array();
	$sqlTemplate = '(%d,%d,"%s","%s",%d,"%s",%d,%d,"%s",%d,%d,%d,%d,%d,"%s","%s",%d)';
	$createTime  = time();

	foreach (array_keys($REX['CLANG']) as $clangID) {
		$template = $REX['DEFAULT_TEMPLATE'];

		if (!empty($startpageTemplates[$clangID])) {
			$template = $startpageTemplates[$clangID];
		}

		$records[] = sprintf($sqlTemplate,
			/*          id */ (int) $newID,
			/*       re_id */ (int) $parentID,
			/*        name */ $data['name'], // Magic Quotes von REDAXO!
			/*     catname */ $data['catname'], // Magic Quotes von REDAXO!
			/*    catprior */ (int) $data['catprior'],
			/*  attributes */ '',
			/*   startpage */ 1,
			/*       prior */ 1,
			/*        path */ $data['path'], // Magic Quotes von REDAXO!
			/*      status */ $data['status'] ? 1 : 0,
			/*  createdate */ $createTime,
			/*  updatedate */ $createTime,
			/*    template */ $sql->escape($template),
			/*       clang */ (int) $clangID,
			/*  createuser */ $sql->escape($REX['USER']->getValue('login')),
			/*  updateuser */ $sql->escape($REX['USER']->getValue('login')),
			/*    revision */ 0
		);

		sly_Core::cache()->delete('clist', $parentID.'_'.$clangID);
	}

	$sql->setQuery('INSERT INTO '.$REX['DATABASE']['TABLE_PREFIX'].'article (id,re_id,name,'.
		'catname,catprior,attributes,startpage,prior,path,status,createdate,'.
		'updatedate,template,clang,createuser,updateuser,revision) VALUES '.
		implode(',', $records)
	);

	// Falls Extensions vorhanden sind, führen wir sie einmal pro Sprache aus.

	if ($hasOldExtensions || $hasNewExtensions) {
		foreach (array_keys($REX['CLANG']) as $clangID) {
			$category = null;

			// rex_sql-Objekt erzeugen, um die Kompatibilität beizubehalten.

			if ($hasOldExtensions) {
				$category = new rex_sql();
				$category->setQuery('SELECT * FROM #_article WHERE id = '.$newID.' AND clang = '.$clangID.' AND startpage = 1', '#_');
			}

			// EP auslösen

			$message = rex_register_extension_point('CAT_ADDED', $message, array(
				'category' => clone $category,
				'id'       => $newID,
				're_id'    => $parentID,
				'clang'    => $clangID,
				'name'     => $data['catname'],
				'prior'    => $data['catprior'],
				'path'     => $data['path'],
				'status'   => $data['status'],
				'data'     => $data
			));

			$message = rex_register_extension_point('CAT_ADDED_NEW', $message, array(
				'id'       => $newID,
				're_id'    => $parentID,
				'clang'    => $clangID,
				'name'     => $data['catname'],
				'prior'    => $data['catprior'],
				'path'     => $data['path'],
				'status'   => $data['status'],
				'data'     => $data
			));
		}

		$category = null;
		unset($category);
	}

	return array(true, $message, $newID);
}




/**
 * Bearbeitet einer Kategorie
 *
 * @param  int   $categoryID Id der Kategorie die verändert werden soll
 * @param  int   $clang      Id der Sprache
 * @param  array $data       Array mit den Daten der Kategorie
 * @return array             ein Array welches den status sowie eine Fehlermeldung beinhaltet
 */
function rex_editCategory($categoryID, $clang, $data)
{
	global $REX, $I18N;

	$categoryID = (int) $categoryID;
	$clang      = (int) $clang;

	if (!isset($data['catname'])) {
		trigger_error('Expecting $data to contain the key "catname"!', E_USER_ERROR);
	}

	// Kategorie mit alten Daten selektieren

	$oldData = rex_sql::fetch('*', 'article', 'startpage = 1 and id = '.$categoryID.' and clang = '.$clang);

	// Kategorie selbst updaten

	$sql = new rex_sql();
	$sql->setQuery(
		'UPDATE '.$REX['DATABASE']['TABLE_PREFIX'].'article '.
		'SET catname = "'.$data['catname'].'", '. // Magic Quotes von REDAXO!
		'updatedate = UNIX_TIMESTAMP(), updateuser = "'.$sql->escape($REX['USER']->getValue('login')).'" '.
		'WHERE id = '.$categoryID.' AND clang = '.$clang
	);

	// Name der Kategorie in den Kindern ändern

	$sql->setQuery(
		'UPDATE #_article '.
		'SET catname = "'.$data['catname'].'" '. // Magic Quotes von REDAXO!
		'WHERE re_id = '.$categoryID.' AND startpage = 0 AND clang = '.$clang, '#_'
	);

	// Kinder abrufen, um für jedes Kind den Cache zu leeren.

	$children = rex_sql::getArrayEx(
		'SELECT id FROM #_article '.
		'WHERE re_id = '.$categoryID.' AND startpage = 0 AND clang = '.$clang, '#_'
	);

	foreach ($children as $child) {
		rex_deleteCacheArticle($child, $clang);
	}

	// Priorität verarbeiten

	if (isset($data['catprior'])) {
		$parentID = $oldData['re_id'];
		$oldPrio  = $oldData['catprior'];
		$newPrio  = (int) $data['catprior'];

		if ($newPrio <= 0) {
			$newPrio = 1;
		}
		else {
			$maxPrio = rex_sql::fetch('MAX(catprior)', 'article', 're_id = '.$parentID.' AND catprior <> 0 AND clang = 0');

			if ($newPrio > $maxPrio) {
				$newPrio = $maxPrio;
			}
		}

		// Nur aktiv werden, wenn sich auch etwas geändert hat.

		if ($newPrio != $oldPrio) {
			$relation    = $newPrio < $oldPrio ? '+' : '-';
			list($a, $b) = $newPrio < $oldPrio ? array($newPrio, $oldPrio) : array($oldPrio, $newPrio);

			// Alle anderen entsprechend verschieben

			$sql->setQuery(
				'UPDATE '.$REX['DATABASE']['TABLE_PREFIX'].'article '.
				'SET catprior = catprior '.$relation.' 1 '.
				'WHERE catprior BETWEEN '.$a.' AND '.$b.' '.
				'AND re_id = '.$parentID.' AND catprior <> 0 AND clang = '.$clang
			);

			// Eigene neue Position speichern

			$sql->setQuery(
				'UPDATE '.$REX['DATABASE']['TABLE_PREFIX'].'article '.
				'SET catprior = '.$newPrio.' '.
				'WHERE id = '.$categoryID.' AND clang = '.$clang
			);

			$sql->setQuery('SELECT id FROM '.$REX['DATABASE']['TABLE_PREFIX'].'article WHERE re_id = "'.$parentID.'" AND clang ="'.$clang.'" AND catprior != 0');
			for ($i=0; $i < $sql->getRows(); $i++)
			{
				sly_Core::cache()->delete('category', $sql->getValue('id').'_'.$clang);
				$sql->next();
			}
           	sly_Core::cache()->delete('clist', $parentID.'_'.$clang);
		}
	}

	// Cache der Kategorie löschen

	$message = $I18N->msg('category_updated');
	$message = rex_register_extension_point('CAT_UPDATED', $message, array(
		'id'     => $categoryID,
		're_id'  => $oldData['re_id'],
		'clang'  => $clang,
		'name'   => $oldData['catname'],
		'prior'  => $oldData['catprior'],
		'path'   => $oldData['path'],
		'status' => $oldData['status'],
		'data'   => $data,
	));

	sly_Core::cache()->delete('category', $categoryID.'_'.$clang);
	return array(true, $message);
}




/**
 * Löscht eine Kategorie und reorganisiert die Prioritäten verbleibender
 * Geschwister-Kategorien
 *
 * @param  int $categoryID  Id der Kategorie die gelöscht werden soll
 * @return array            ein Array welches den Status sowie eine Fehlermeldung beinhaltet
 */
function rex_deleteCategoryReorganized($categoryID)
{
	global $I18N, $REX;

	$message    = '';
	$clang      = 0;
	$categoryID = (int) $categoryID;

	// Prüfen ob die Kategorie existiert

	$parentID = rex_sql::fetch('re_id', 'article', 'id = '.$categoryID.' AND clang = '. $clang);

	if ($parentID === false) {
		$message = $I18N->msg('category_could_not_be_deleted');
		return array(false, $message);
	}

	// Prüfen ob die Kategorie noch Unterkategorien besitzt

	$numSubCats = rex_sql::fetch('COUNT(*)', 'article', 're_id = '.$categoryID.' AND clang = '.$clang.' AND startpage = 1');

	if ($numSubCats > 0) {
		$message  = $I18N->msg('category_could_not_be_deleted').' ';
		$message .= $I18N->msg('category_still_contains_subcategories');
		return array(false, $message);
	}

	// Prüfen ob die Kategorie noch Artikel besitzt (ausser dem Startartikel)

	$numChildren = rex_sql::fetch('COUNT(*)', 'article', 're_id = '.$categoryID.' AND clang = '.$clang.' AND startpage = 0');

	if ($numChildren > 0) {
		$message  = $I18N->msg('category_could_not_be_deleted').' ';
		$message .= $I18N->msg('category_still_contains_articles');
		return array(false, $message);
	}

	$sql       = new rex_sql();
	$instances = rex_sql::getArrayEx(
		'SELECT clang, re_id, catname, catprior, path, status '.
		'FROM #_article WHERE id = '.$categoryID, '#_'
	);

	// Kategorie löschen

	$return = rex_deleteArticle($categoryID);

	if ($return['state'] != false) {
		// Kinder neu positionieren

		$cache = sly_Core::cache();

		foreach ($instances as $clang => $data) {
			$sql->setQuery(
				'UPDATE #_article SET catprior = catprior - 1 '.
				'WHERE re_id = '.$data['re_id'].' AND catprior > '.$data['catprior'].' '.
				'AND catprior <> 0 AND clang = '.$clang, '#_'
			);

			$return = rex_register_extension_point('CAT_DELETED', $return, array(
				'id'     => $categoryID,
				'clang'  => $clang,
				're_id'  => $data['re_id'],
				'name'   => $data['catname'],
				'prior'  => $data['catprior'],
				'path'   => $data['path'],
				'status' => $data['status']
			));

			$cache->delete('category', $categoryID.'_'.$clang);
			$cache->delete('clist', $data['re_id'].'_'.$clang);
		}

		// remove all caches

		$sql->setQuery('SELECT id FROM #_article WHERE re_id = "'.$parentID.'" AND catprior >= "' .$data['catprior']. '"', '#_');
		$clangs = array_keys($REX['CLANG']);

		for ($i = 0; $i < $sql->getRows(); ++$i) {
			$id = $sql->getValue('id');

			foreach ($clangs as $clang) {
				$cache->delete('category', $id.'_'.$clang);
			}

			$sql->next();
		}
	}

	return array($return['state'], $return['message']);
}




/**
 * Ändert den Status der Kategorie
 *
 * @param  int      $categoryID  Id der Kategorie die gelöscht werden soll
 * @param  int      $clang       Id der Sprache
 * @param  int|null $newStatus   Status auf den die Kategorie gesetzt werden soll, oder null wenn zum nächsten Status weitergeschaltet werden soll
 * @return array                 Ein Array welches den status sowie eine Fehlermeldung beinhaltet
 */
function rex_categoryStatus($categoryID, $clang, $newStatus = null)
{
	global $REX, $I18N;

	$success        = false;
	$message        = '';
	$catStatusTypes = rex_categoryStatusTypes();
	$categoryID     = (int) $categoryID;
	$clang          = (int) $clang;

	$sql       = new rex_sql();
	$oldStatus = rex_sql::fetch('status,re_id', 'article', 'id = '.$categoryID.' AND clang = '.$clang);

	if ($oldStatus !== false) {
		$re_id     = $oldStatus['re_id'];
		$oldStatus = $oldStatus['status'];

		// Status wurde nicht von außen vorgegeben,
		// => zyklisch auf den nächsten weiterschalten

		if ($newStatus === null) {
			$newStatus = ($oldStatus + 1) % count($catStatusTypes);
		}

		$sql->setTable('article', true);
		$sql->setWhere('id = '.$categoryID.' AND clang = '.$clang);
		$sql->setValue('status', $newStatus);
		$sql->addGlobalCreateFields();

		if ($sql->update()) {
			rex_deleteCacheArticle($categoryID, $clang);

			$success = true;
			$message = rex_register_extension_point('CAT_STATUS', $I18N->msg('category_status_updated'), array(
				'id'     => $categoryID,
				'clang'  => $clang,
				'status' => $newStatus
			));

			$cache = sly_Core::cache();
			$cache->delete('category', $categoryID.'_'.$clang);
			$cache->delete('clist', $re_id.'_'.$clang);
		}
		else {
			$message = $sql->getError();
		}
	}
	else {
		$message = $I18N->msg('no_such_category');
	}

	return array($success, $message);
}

/**
 * Gibt alle Stati zurück, die für eine Kategorie gültig sind
 *
 * @return array  Array von Stati (jeweils array(Titel, css-Klasse))
 */
function rex_categoryStatusTypes()
{
	global $I18N;
	static $catStatusTypes;

	if (!$catStatusTypes) {
		$catStatusTypes = array(
			// Name, CSS-Class
			array($I18N->msg('status_offline'), 'rex-offline'),
			array($I18N->msg('status_online'),  'rex-online')
		);

		$catStatusTypes = rex_register_extension_point('CAT_STATUS_TYPES', $catStatusTypes);
	}

	return $catStatusTypes;
}

/**
 * Erstellt einen neuen Artikel
 *
 * @param  array $data  Array mit den Daten des Artikels
 * @return array        ein Array welches den Status sowie eine Fehlermeldung beinhaltet
 */
function rex_addArticle($data)
{
	global $REX, $I18N;

	$success = true;
	$message = '';

	if (!isset($data['name']) || !isset($data['category_id']) || !isset($data['prior']) || !isset($data['template'])) {
		trigger_error('Expecting $data to be an array!', E_USER_ERROR);
	}

	$articleName  = $data['name'];
	$categoryID   = (int) $data['category_id'];
	$prior        = (int) $data['prior'];
	$templateName = $data['template'];

	// Template überprüfen

	$service = sly_Service_Factory::getService('Template');
	if (!$service->exists($templateName)) $templateName = '';

	if ($categoryID == 0) {
		$categoryData = array('catname' => '', 'path' => '|');
	}
	else {
		$categoryData = rex_sql::fetch('catname,path', 'article', 'id = '.$categoryID.' AND clang = 0 AND startpage = 1');
	}

	// Existiert die Kategorie überhaupt?

	if ($categoryData === false) {
		trigger_error('The parent category does not exist!', E_USER_ERROR);
	}

	// Priorität vorverarbeiten

	if (isset($data['prior']) && $data['prior'] <= 0) {
		$data['prior'] = 1;
	}
	else {
		$maxPrior      = rex_sql::fetch('MAX(prior)', 'article', '(re_id = '.$categoryID.' AND catprior = 0) OR id = '.$categoryID.'  AND clang = 0') + 1;
		$data['prior'] = $data['prior'] > $maxPrior ? $maxPrior : $data['prior'];
	}

	// Status beachten

	if (!isset($data['status'])) {
		$data['status'] = false;
	}

	// Pfad vorverarbeiten

	if (!isset($data['path'])) {
		$data['path'] = $categoryData['path'];
	}

	// Die ID ist für alle Sprachen gleich und entspricht einfach der aktuell
	// höchsten plus 1.

	$newID = rex_sql::fetch('MAX(id)', 'article', 'clang = 0') + 1;

	// Bevor wir die neuen Datensätze einfügen, machen wir in den sortierten
	// Listen (je eine pro Sprache) Platz, indem wir alle Artikel, deren
	// Priorität größergleich der Priorität des neuen Artikel ist, um eine
	// Position nach unten schieben.

	$sql = new rex_sql();
	$sql->setQuery(
		'UPDATE #_article SET prior = prior + 1 '.
		'WHERE ((re_id = '.$categoryID.' AND catprior = 0) OR id = '.$categoryID.' ) AND prior >= '.$data['prior'].' '.
		'ORDER BY prior ASC', '#_'
	);

	// Kategorienamen abrufen.
	// Wenn wir im Root einen Artikel hinzufügen, gibt es keinen catname.

	if ($categoryID != 0) {
		$categoryNames = rex_sql::getArrayEx(
			'SELECT clang, catname FROM #_article '.
			'WHERE id = '.$categoryID.' AND catprior <> 0 AND startpage = 1', '#_'
		);
	}

	// Artikel in allen Sprachen anlegen

	$records     = array();
	$sqlTemplate = '(%d,%d,"%s","%s",%d,"%s",%d,%d,"%s",%d,%d,%d,"%s",%d,"%s","%s",%d)';
	$createTime  = time();
	$cache       = sly_Core::cache();

	foreach (array_keys($REX['CLANG']) as $clangID) {
		$records[] = sprintf($sqlTemplate,
			/*          id */ (int) $newID,
			/*       re_id */ (int) $categoryID,
			/*        name */ $data['name'], // Magic Quotes von REDAXO!
			/*     catname */ isset($categoryNames[$clangID]) ? $categoryNames[$clangID] : '', // Magic Quotes von REDAXO!
			/*    catprior */ 0,
			/*  attributes */ '',
			/*   startpage */ 0,
			/*       prior */ (int) $data['prior'],
			/*        path */ $data['path'], // Magic Quotes von REDAXO!
			/*      status */ $data['status'] ? 1 : 0,
			/*  createdate */ $createTime,
			/*  updatedate */ $createTime,
			/*    template */ $sql->escape($templateName),
			/*       clang */ $clangID,
			/*  createuser */ $sql->escape($REX['USER']->getValue('login')),
			/*  updateuser */ $sql->escape($REX['USER']->getValue('login')),
			/*    revision */ 0
		);

		$cache->delete('alist', $categoryID.'_'.$clangID);
	}

	$sql->setQuery('INSERT INTO '.$REX['DATABASE']['TABLE_PREFIX'].'article (id,re_id,name,'.
		'catname,catprior,attributes,startpage,prior,path,status,createdate,'.
		'updatedate,template,clang,createuser,updateuser,revision) VALUES '.
		implode(',', $records)
	);

	// (Fast) fertig!

	$message = $I18N->msg('article_added');

	if (rex_extension_is_registered('ART_ADDED')) {
		foreach (array_keys($REX['CLANG']) as $clangID) {
			$message = rex_register_extension_point('ART_ADDED', $message, array(
				'id'       => $newID,
				'clang'    => $clangID,
				'status'   => $data['status'] ? 1 : 0,
				'name'     => $data['name'],
				'path'     => $data['path'],
				're_id'    => (int) $data['category_id'],
				'prior'    => (int) $data['prior'],
				'template' => $data['template'],
				'data'     => $data
			));
		}
	}

	return array($success, $message);
}





/**
 * Bearbeitet einen Artikel
 *
 * @param  int   $articleID  Id des Artikels der verändert werden soll
 * @param  int   $clang      Id der Sprache
 * @param  array $data       Array mit den Daten des Artikels
 * @return array             ein Array welches den Status sowie eine Fehlermeldung beinhaltet
 */
function rex_editArticle($articleID, $clang, $data)
{
	global $REX, $I18N;

	$articleID = (int) $articleID;
	$clang     = (int) $clang;

	if (!is_array($data)) {
		trigger_error('Expecting $data to be an array!', E_USER_ERROR);
	}

	$hasOldExtensions = rex_extension_is_registered('ART_UPDATED');
	$hasNewExtensions = rex_extension_is_registered('ART_UPDATED_NEW');

	// Template überprüfen

	$service = sly_Service_Factory::getService('Template');
	if (!$service->exists($data['template'])) $data['template'] = '';

	// Artikel mit alten Daten selektieren. Wir brauchen sie, unabhängig von
	// ART_UPDATED. Und da wir da die gleichen Daten benötigen, nutzen wir hier
	// gleich ein rex_sql-Objekt.

	$oldData = new rex_sql();
	$oldData->setQuery('SELECT * FROM #_article WHERE id = '.$articleID.' AND clang = '.$clang, '#_');

	// Kategorie selbst updaten

	$sql = new rex_sql();
	$sql->setQuery(
		'UPDATE '.$REX['DATABASE']['TABLE_PREFIX'].'article '.
		'SET name = "'.$data['name'].'", template = "'.$sql->escape($data['template']).'", '. // Magic Quotes von REDAXO!
		'updatedate = UNIX_TIMESTAMP(), updateuser = "'.$sql->escape($REX['USER']->getValue('login')).'" '.
		'WHERE id = '.$articleID.' AND clang = '.$clang
	);

	// Priorität verarbeiten

	if (isset($data['prior'])) {
		$parentID = $oldData->getValue('startpage') ? $oldData->getValue('id') : $oldData->getValue('re_id');
		$oldPrio  = $oldData->getValue('prior');
		$newPrio  = (int) $data['prior'];

		if ($newPrio <= 0) {
			$newPrio = 1;
		}
		else {
			$maxPrio = rex_sql::fetch('MAX(prior)', 'article', '((re_id = '.$parentID.' AND catprior = 0) OR id = '.$parentID.') AND clang = '.$clang);
			if ($newPrio > $maxPrio) {
				$newPrio = $maxPrio;
			}
		}

		// Nur aktiv werden, wenn sich auch etwas geändert hat.
		if ($newPrio != $oldPrio) {
			$relation    = $newPrio < $oldPrio ? '+' : '-';
			list($a, $b) = $newPrio < $oldPrio ? array($newPrio, $oldPrio) : array($oldPrio, $newPrio);

			// Alle anderen entsprechend verschieben

			$sql->setQuery(
				'UPDATE #_article SET prior = prior '.$relation.' 1 '.
				'WHERE prior BETWEEN '.$a.' AND '.$b.' '.
				'AND ((re_id = '.$parentID.' AND catprior = 0) OR id = '.$parentID.') AND clang = '.$clang, '#_'
			);

			// Eigene neue Position speichern

			$sql->setQuery(
				'UPDATE #_article SET prior = '.$newPrio.' '.
				'WHERE id = '.$articleID.' AND clang = '.$clang, '#_'
			);

			$sql->setQuery('SELECT id FROM '.$REX['DATABASE']['TABLE_PREFIX'].'article WHERE re_id = "'.$parentID.'" AND clang ="'.$clang.'" AND catprior = 0');
			for ($i=0; $i < $sql->getRows(); $i++)
			{
				sly_Core::cache()->delete('article', $sql->getValue('id').'_'.$clang);
				$sql->next();
			}
			sly_Core::cache()->delete('alist', $parentID.'_'.$clang);
		}
	}

	$message = $I18N->msg('article_updated');

	if ($hasOldExtensions) {
		$article = new rex_sql();
		$article->setQuery('SELECT * FROM #_article WHERE id = '.$articleID.' AND clang = '.$clang, '#_');

		$message = rex_register_extension_point('ART_UPDATED', $I18N->msg('article_updated'), array(
			'id'          => $articleID,
			'article'     => clone $article,
			'article_old' => clone $oldData,
			'status'      => (int) $oldData->getValue('status'),
			'name'        => $data['name'],
			'clang'       => $clang,
			're_id'       => (int) $data['category_id'],
			'prior'       => (int) $data['prior'],
			'path'        => $data['path'],
			'template'    => $data['template']
		));
	}

	if ($hasNewExtensions) {
		$message = rex_register_extension_point('ART_UPDATED', $I18N->msg('article_updated'), array(
			'id'       => $articleID,
			'data'     => $data,
			'status'   => (int) $oldData->getValue('status'),
			'name'     => $data['name'],
			'clang'    => $clang,
			're_id'    => (int) $data['category_id'],
			'prior'    => (int) $data['prior'],
			'path'     => $data['path'],
			'template' => $data['template']
		));
	}

	$cache = sly_Core::cache();
	$cache->delete('article', $articleID.'_'.$clang);
	$cache->delete('alist', $data['category_id'].'_'.$clang);

	return array(true, $message);
}







/**
 * Löscht einen Artikel und reorganisiert die Prioritäten verbleibender Geschwister-Artikel
 *
 * @param  int $articleID  Id des Artikels die gelöscht werden soll
 * @return array           ein Array welches den Status sowie eine Fehlermeldung beinhaltet
 */
function rex_deleteArticleReorganized($articleID)
{
	global $REX;

	$message   = '';
	$clang     = 0;
	$articleID = (int) $articleID;

	// Prüfen ob der Artikel existiert

	$data = rex_sql::getArrayEx(
		'SELECT clang, re_id, name, status, prior, path, template '.
		'FROM #_article WHERE id = '.$articleID.' AND startpage = 0', '#_'
	);

	if ($data === false) {
		$message = $I18N->msg('article_could_not_be_deleted');
		return array(false, $message);
	}

	$return = rex_deleteArticle($articleID);
	$cache  = sly_Core::cache();
	$sql    = new rex_sql();

	foreach ($data as $clang => $article) {
		$sql->setQuery(
			'UPDATE #_article SET prior = prior - 1 '.
			'WHERE prior > '.$article['prior'].' '.
			'AND ((re_id = '.$article['re_id'].' AND catprior = 0) OR id = '.$article['re_id'].') '.
			'AND clang = '.$clang, '#_'
		);

		$return = rex_register_extension_point('ART_DELETED', $return, array(
			'id'       => $articleID,
			'clang'    => $clang,
			'name'     => $article['name'],
			'path'     => $article['path'],
			're_id'    => (int) $article['re_id'],
			'status'   => (int) $article['status'],
			'prior'    => (int) $article['prior'],
			'template' => $article['template']
		));

		$cache->delete('article', $articleID.'_'.$clang);
		$cache->delete('alist', $article['re_id'].'_'.$clang);
	}

	return array($return['state'], $return['message']);
}

/**
 * Ändert den Status des Artikels
 *
 * @param  int      $articleID   Id des Artikels die gelöscht werden soll
 * @param  int      $clang       Id der Sprache
 * @param  int|null $newStatus   Status auf den der Artikel gesetzt werden soll, oder null wenn zum nächsten Status weitergeschaltet werden soll
 * @return array                 ein Array welches den Status sowie eine Fehlermeldung beinhaltet
 */
function rex_articleStatus($articleID, $clang, $newStatus = null)
{
	global $REX, $I18N;

	$success        = false;
	$message        = '';
	$artStatusTypes = rex_articleStatusTypes();
	$articleID      = (int) $articleID;
	$clang          = (int) $clang;

	$sql       = new rex_sql();
	$oldStatus = rex_sql::fetch('status', 'article', 'id = '.$articleID.' AND clang = '.$clang);

	if ($oldStatus !== false) {
		// Status wurde nicht von außen vorgegeben,
		// => zyklisch auf den nächsten weiterschalten

		if ($newStatus === null) {
			$newStatus = ($oldStatus + 1) % count($artStatusTypes);
		}

		$sql->setTable('article', true);
		$sql->setWhere('id = '.$articleID.' AND clang = '.$clang);
		$sql->setValue('status', $newStatus);
		$sql->addGlobalUpdateFields();

		if ($sql->update()) {
			sly_Core::cache()->delete('article', $articleID.'_'.$clang);

			$success = true;
			$message = rex_register_extension_point('ART_STATUS', $I18N->msg('article_status_updated'), array(
				'id'     => $articleID,
				'clang'  => $clang,
				'status' => $newStatus
			));
		}
		else {
			$message = $sql->getError();
		}
	}
	else {
		$message = $I18N->msg('no_such_category');
	}

	return array($success, $message);
}

/**
 * Gibt alle Stati zurück, die für einen Artikel gültig sind
 *
 * @return array  Array von Stati (jeweils array(Titel, css-Klasse))
 */
function rex_articleStatusTypes()
{
	global $I18N;
	static $artStatusTypes;

	if (!$artStatusTypes) {
		$artStatusTypes = array(
			// Name, CSS-Class
			array($I18N->msg('status_offline'), 'rex-offline'),
			array($I18N->msg('status_online'), 'rex-online')
		);

		$artStatusTypes = rex_register_extension_point('ART_STATUS_TYPES', $artStatusTypes);
	}

	return $artStatusTypes;
}