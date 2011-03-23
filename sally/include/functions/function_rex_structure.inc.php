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
 * Sämtliche Funktionen sind deprecated; die entsprechenden Services sollten
 * ihnen unbedingt vorgezogen werden.
 *
 * @package redaxo4
 */

/**
 * Erstellt eine neue Kategorie
 *
 * @param  int   $parentID KategorieId in der die neue Kategorie erstellt werden soll
 * @param  array $data     Array mit den Daten der Kategorie (muss enthalten: catname, status)
 * @return array           Ein Array welches den status sowie eine Fehlermeldung beinhaltet
 * @deprecated
 */
function rex_addCategory($parentID, $data) {
	$status = isset($data['status']) ? $data['status'] : false;
	$prior  = -1;

	if (isset($data['catprior']) && $data['catprior'] <= 0) {
		$prior = 1;
	}

	try {
		$service = sly_Service_Factory::getService('Category');
		$service->add($parentID, $data['catname'], $status, $prior);

		return array(true, t('category_added_and_startarticle_created'));
	}
	catch (Exception $e) {
		return array(false, $e->getMessage());
	}
}

/**
 * Bearbeitet einer Kategorie
 *
 * @param  int   $categoryID Id der Kategorie die verändert werden soll
 * @param  int   $clang      Id der Sprache
 * @param  array $data       Array mit den Daten der Kategorie
 * @return array             ein Array welches den status sowie eine Fehlermeldung beinhaltet
 * @deprecated
 */
function rex_editCategory($categoryID, $clang, $data) {
	try {
		$service = sly_Service_Factory::getService('Category');
		$service->edit($categoryID, $clang, $data['catname'], isset($data['catprior']) ? $data['catprior'] : false);

		return array(true, t('category_updated'));
	}
	catch (Exception $e) {
		return array(false, $e->getMessage());
	}
}

/**
 * Löscht eine Kategorie
 *
 * @param  int $categoryID  Id der Kategorie die gelöscht werden soll
 * @return array            ein Array welches den Status sowie eine Fehlermeldung beinhaltet
 * @deprecated
 */
function rex_deleteCategoryReorganized($categoryID) {
	try {
		sly_Service_Factory::getService('Category')->delete($categoryID);
		return array(true, t('category_deleted'));
	}
	catch (Exception $e) {
		return array(false, $e->getMessage());
	}
}

/**
 * Ändert den Status der Kategorie
 *
 * @param  int      $categoryID  Id der Kategorie die gelöscht werden soll
 * @param  int      $clang       Id der Sprache
 * @param  int|null $newStatus   Status auf den die Kategorie gesetzt werden soll, oder null wenn zum nächsten Status weitergeschaltet werden soll
 * @return array                 Ein Array welches den status sowie eine Fehlermeldung beinhaltet
 * @deprecated
 */
function rex_categoryStatus($categoryID, $clang, $newStatus = null) {
	try {
		sly_Service_Factory::getService('Category')->changeStatus($categoryID, $clang, $newStatus);
		return array(true, t('category_status_updated'));
	}
	catch (Exception $e) {
		return array(false, $e->getMessage());
	}
}

/**
 * Gibt alle Stati zurück, die für eine Kategorie gültig sind
 *
 * @return array  Array von Stati (jeweils array(Titel, css-Klasse))
 * @deprecated
 */
function rex_categoryStatusTypes() {
	return sly_Service_Factory::getService('Category')->getStati();
}

/**
 * Erstellt einen neuen Artikel
 *
 * @param  array $data  Array mit den Daten des Artikels
 * @return array        ein Array welches den Status sowie eine Fehlermeldung beinhaltet
 * @deprecated
 */
function rex_addArticle($data) {
	$status     = isset($data['status']) ? $data['status'] : false;
	$prior      = -1;
	$categoryID = (int) $data['category_id'];

	if (isset($data['prior']) && $data['prior'] <= 0) {
		$prior = 1;
	}

	try {
		$service = sly_Service_Factory::getService('Article');
		$service->add($categoryID, $data['name'], $status, $prior);

		return array(true, t('article_added'));
	}
	catch (Exception $e) {
		return array(false, $e->getMessage());
	}
}

/**
 * Bearbeitet einen Artikel
 *
 * @param  int   $articleID  Id des Artikels der verändert werden soll
 * @param  int   $clang      Id der Sprache
 * @param  array $data       Array mit den Daten des Artikels
 * @return array             ein Array welches den Status sowie eine Fehlermeldung beinhaltet
 * @deprecated
 */
function rex_editArticle($articleID, $clang, $data) {
	try {
		$service = sly_Service_Factory::getService('Category');
		$service->edit($articleID, $clang, $data['name'], isset($data['catprior']) ? $data['catprior'] : false);

		return array(true, t('article_updated'));
	}
	catch (Exception $e) {
		return array(false, $e->getMessage());
	}
}

/**
 * Löscht einen Artikel
 *
 * @param  int $articleID  Id des Artikels die gelöscht werden soll
 * @return array           ein Array welches den Status sowie eine Fehlermeldung beinhaltet
 * @deprecated
 */
function rex_deleteArticleReorganized($articleID) {
	try {
		sly_Service_Factory::getService('Article')->delete($articleID);
		return array(true, t('article_deleted'));
	}
	catch (Exception $e) {
		return array(false, $e->getMessage());
	}
}

/**
 * Ändert den Status des Artikels
 *
 * @param  int      $articleID   Id des Artikels die gelöscht werden soll
 * @param  int      $clangID     Id der Sprache
 * @param  int|null $newStatus   Status auf den der Artikel gesetzt werden soll, oder null wenn zum nächsten Status weitergeschaltet werden soll
 * @return array                 ein Array welches den Status sowie eine Fehlermeldung beinhaltet
 * @deprecated
 */
function rex_articleStatus($articleID, $clangID, $newStatus = null) {
	try {
		$service   = sly_Service_Factory::getService('Article');
		$articleID = (int) $articleID;
		$clangID   = (int) $clangID;
		$article   = $service->findById($articleID, $clangID);

		// Prüfen ob die Artikel existiert
		if ($article === null) {
			return array(false, t('no_such_article'));
		}

		$service->changeStatus($article, $newStatus);
		return array(true, t('article_status_updated'));
	}
	catch (Exception $e) {
		return array(false, $e->getMessage());
	}
}

/**
 * Gibt alle Stati zurück, die für einen Artikel gültig sind
 * @deprecated
 *
 * @return array  Array von Stati (jeweils array(Titel, css-Klasse))
 */
function rex_articleStatusTypes() {
	return sly_Service_Factory::getService('Article')->getStati();
}
