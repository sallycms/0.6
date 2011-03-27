<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Object Oriented Framework: Bildet eine Kategorie der Struktur ab
 *
 * @ingroup redaxo2
 */
class OOCategory extends OORedaxo {
	public function __construct($params = false, $clang = false) {
		parent::__construct($params, $clang);
	}

	/**
	 * Return an OORedaxo object based on an id
	 *
	 * @return OOCategory
	 */
	public static function getCategoryById($category_id, $clang = false) {
		return OOArticle::getArticleById($category_id, $clang, true);
	}

	/**
	 * Return all Children by id
	 */
	public static function getChildrenById($cat_parent_id, $ignore_offlines = false, $clang = false) {
		$cat_parent_id = (int) $cat_parent_id;

		if ($clang === false) {
			$clang = sly_Core::getCurrentClang();
		}

		$clang     = (int) $clang;
		$namespace = 'sly.category.list';
		$key       = $cat_parent_id.'_'.$clang;
		$clist     = sly_Core::cache()->get($namespace, $key, null);

		if ($clist === null) {
			$clist = rex_sql::getArrayEx('SELECT id FROM ~article WHERE startpage = 1 AND re_id = '.$cat_parent_id.' AND clang = '.$clang.' ORDER BY catprior,name', '~');
			sly_Core::cache()->set($namespace, $key, $clist);
		}

		$catlist = array();

		foreach ($clist as $var) {
			$category = self::getCategoryById($var, $clang);

			if ($category && (!$ignore_offlines || ($ignore_offlines && $category->isOnline()))) {
				$catlist[] = $category;
			}
		}

		return $catlist;
	}

	/**
	 * @return int the article priority
	 */
	public function getPriority() { return $this->_catprior; }

	/**
	 * Return a list of top level categories, ie.
	 * categories that have no parent.
	 * Returns an array of OOCategory objects sorted by $prior.
	 *
	 * If $ignore_offlines is set to TRUE,
	 * all categories with status 0 will be
	 * excempt from this list!
	 */
	public static function getRootCategories($ignore_offlines = false, $clang = false) {
		return self::getChildrenById(0, $ignore_offlines, $clang);
	}

	/**
	 * Return a list of all subcategories.
	 * Returns an array of OORedaxo objects sorted by $prior.
	 *
	 * If $ignore_offlines is set to TRUE,
	 * all categories with status 0 will be
	 * excempt from this list!
	 */
	public function getChildren($ignore_offlines = false, $clang = false) {
		if ($clang === false) $clang = $this->_clang;
		return self::getChildrenById($this->_id, $ignore_offlines, $clang);
	}

	/**
	 * Returns the parent category
	 */
	public function getParentId() {
		return $this->_re_id;
	}

	/**
	 * Returns the parent category
	 */
	public function getParent($clang = false) {
		if ($clang === false) $clang = $this->_clang;
		return self::getCategoryById($this->getParentId(), $clang);
	}

	/**
	 * Returns TRUE if this category is the direct
	 * parent of the other category.
	 */
	public function isParent($other_cat) {
		return $this->getId() == $other_cat->getParentId() && $this->getClang() == $other_cat->getClang();
	}

	/**
	 * Returns TRUE if this category is an ancestor
	 * (parent, grandparent, greatgrandparent, etc)
	 * of the other category.
	 */
	public function isAncestor($other_cat) {
		$category = self::_getCategoryObject($other_cat);
		return in_array($this->_id, explode('|', $category->getPath()));
	}

	/**
	 * Return a list of articles in this category
	 * Returns an array of OOArticle objects sorted by $prior.
	 *
	 * If $ignore_offlines is set to TRUE,
	 * all articles with status 0 will be
	 * excempt from this list!
	 */
	public function getArticles($ignore_offlines = false) {
		return OOArticle::getArticlesOfCategory($this->_id, $ignore_offlines, $this->_clang);
	}

	/**
	 * Return the start article for this category
	 */
	public function getStartArticle() {
		return OOArticle::getCategoryStartArticle($this->_id, $this->_clang);
	}

	/**
	 * returns the name of the article
	 */
	public function getName() {
		return $this->_catname;
	}

	public function _getCategoryObject($category, $clang = false) {
		if (is_object($category)) {
			return $category;
		}
		elseif (is_int($category)) {
			return self::getCategoryById($category, $clang);
		}
		elseif (is_array($category)) {
			$catlist = array();

			foreach ($category as $cat) {
				$catobj = self::_getCategoryObject($cat, $clang);

				if (is_object($catobj)) {
					$catlist[] = $catobj;
				}
				else {
					return null;
				}
			}

			return $catlist;
		}

		return null;
	}

	public function hasValue($value, $prefixes = array()) {
		return parent::hasValue($value, array('cat_'));
	}

	/**
	 * Returns boolean if is category
	 */
	public static function isValid($category) {
		return $category instanceof self;
	}
}
