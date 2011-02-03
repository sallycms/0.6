<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Object Oriented Framework: Bildet einen Artikel der Struktur ab
 *
 * @ingroup redaxo2
 */
class OOArticle extends OORedaxo
{
	public function __construct($params = false, $clang = false)
	{
		parent::__construct($params, $clang);
	}

	/**
	 * @return OOArticle
	 */
	public static function getArticleById($article_id, $clang = false, $OOCategory = false)
	{
		$article_id = (int) $article_id;

		if ($clang === false) {
			$clang = sly_Core::getCurrentClang();
		}

		$clang     = (int) $clang;
		$namespace = $OOCategory ? 'sly.category' : 'sly.article';
		$key       = $article_id.'_'.$clang;
		$obj       = sly_Core::cache()->get($namespace, $key, null);

		if ($obj === null) {
			$article = rex_sql::fetch('*', 'article', 'id = '.$article_id.' AND clang = '.$clang);

			if ($article) {
				$class = $OOCategory ? 'OOCategory' : 'OOArticle';
				$obj   = new $class($article, $clang);

				sly_Core::cache()->set($namespace, $key, $obj);
			}
		}

		return $obj;
	}

	/**
	 * @return OOArticle
	 */
	public static function getSiteStartArticle($clang = false)
	{
		global $REX;
		return self::getArticleById($REX['START_ARTICLE_ID'], $clang);
	}

	/**
	 * @return OOArticle
	 */
	public static function getCategoryStartArticle($a_category_id, $clang = false)
	{
		return self::getArticleById($a_category_id, $clang);
	}

	/**
	 * @return array
	 */
	public static function getArticlesOfCategory($category_id, $ignore_offlines = false, $clang = false)
	{
		global $REX;

		if ($clang === false) {
			$clang = sly_Core::getCurrentClang();
		}

		$category_id = (int) $category_id;
		$clang       = (int) $clang;

		$namespace = 'sly.article.list';
		$key       = sly_Cache::generateKey($category_id, $clang, $ignore_offlines);
		$alist     = sly_Core::cache()->get($namespace, $key, null);

		if ($alist === null) {
			$where = 're_id = '.$category_id.' AND clang = '.$clang.($ignore_offlines ? ' AND status = 1' : '');
			$query = 'SELECT id FROM '.$REX['DATABASE']['TABLE_PREFIX'].'article WHERE '.$where.' ORDER BY prior,name';
			$alist = array_map('intval', rex_sql::getArrayEx($query));

			if ($category_id != 0) {
				$category = OOCategory::getCategoryById($category_id, $clang);

				if (($ignore_offlines && $category->isOnline()) || !$ignore_offlines) {
					array_unshift($alist, $category_id);
				}
			}

			sly_Core::cache()->set($namespace, $key, $alist);
		}

		$artlist = array();

		foreach ($alist as $articleID) {
			$artlist[] = OOArticle::getArticleById($articleID, $clang);
		}

		return $artlist;
	}

	/**
	 * CLASS Function:
	 * Return a list of top-level articles
	 * @return array
	 */
	public static function getRootArticles($ignore_offlines = false, $clang = false)
	{
		return self::getArticlesOfCategory(0, $ignore_offlines, $clang);

	}

	/**
	 * Accessor Method:
	 * returns the category id
	 * @return int
	 */
	public function getCategoryId()
	{
		return $this->isStartPage() ? $this->getId() : $this->getParentId();
	}

	/**
	 * @return OOCategory
	 */
	public function getCategory()
	{
		return OOCategory::getCategoryById($this->getCategoryId(), $this->getClang());
	}

	/**
	 * Static Method: Returns boolean if article exists with requested id
	 * @return boolean
	 */
	public static function exists($articleId)
	{

		if (sly_Core::cache()->get('sly.article', sly_Cache::generateKey($articleId, sly_Core::getCurrentClang()), null) !== null) {
			return true;
		}

		// prüfen, ob ID in DB vorhanden
		return self::isValid(self::getArticleById($articleId));
	}

	/**
	 * Static Method: Returns boolean if is article
	 */
	public static function isValid($article)
	{
		return is_object($article) && ($article instanceof OOArticle);
	}

	public function getValue($value)
	{
		// alias für re_id -> category_id
		if (in_array($value, array('_re_id', 'category_id', '_category_id'))) {
			// für die CatId hier den Getter verwenden,
			// da dort je nach ArtikelTyp Unterscheidungen getroffen werden müssen
			return $this->getCategoryId();
		}

		return parent::getValue($value);
	}

	public function hasValue($value, $prefixes = array())
	{
		return parent::hasValue($value, array_merge(array('art_'), $prefixes));
	}

	/**
	 * prints the articlecontent for a given slot, or if empty for all slots
	 *
	 * @param string $slot
	 */
	public function printContent($slot = null) {
		$ids = OOArticleSlice::getSliceIdsForSlot($this->getId(), $this->getClang(), $slot);
		foreach ($ids as $id) {
			print OOArticleSlice::getArticleSliceById($id)->printContent();
		}
	}

	/**
	 * returns the articlecontent for a given slot, or if empty for all slots
	 *
	 * @deprecated use getContent() instead
	 * @param string $slot
	 * @return string
	 */
	public function getArticle($slot = null) {
		return $this->getContent($slot);
	}

	/**
	 * returns the articlecontent for a given slot, or if empty for all slots
	 *
	 * @param <type> $slot
	 * @return <type>
	 */
	public function getContent($slot = null) {
		ob_start();
		$this->printContent($slot);
		return ob_get_clean();
	}

	/**
	 * returns the rendered template with the articlecontent
	 *
	 * @global array $REX
	 * @return string
	 */
	public function getArticleTemplate() {
		// global $REX hier wichtig, damit in den Artikeln die Variable vorhanden ist!
		global $REX;

		$tplserv = sly_Service_Factory::getTemplateService();

		if ($this->hasType() && $tplserv->exists($this->getTemplateName())) {
			$params['article'] = $this;
			ob_start();
			ob_implicit_flush(0);
			$tplserv->includeFile($this->getTemplateName(), $params);
			$content = ob_get_clean();
		}
		else {
			$content = 'No Template';
		}

		return $content;
	}

	/**
	 * returns the template name of the template associated with the articletype of this article
	 *
	 * @return string the template name
	 */
	public function getTemplateName() {
		return sly_Service_Factory::getArticleTypeService()->getTemplate($this->_type);
	}

	/**
	 * returns true if the articletype is set
	 *
	 * @return boolean
	 */
	public function hasType() { return !empty($this->_type); }

	/**
	 * returns the articletype
	 *
	 * @return string the articletype
	 */
	public function getType() { return $this->_type; }
}