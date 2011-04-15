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
 * @deprecated
 */
abstract class OOCategory {

	/**
	 * Return an OORedaxo object based on an id
	 *
	 * @return sly_Model_Catgory
	 * @deprecated
	 */
	public static function getCategoryById($category_id, $clang = false) {
		return sly_Util_Category::findById($category_id, $clang, true);
	}

	/**
	 * Return all Children by id
	 * @deprecated
	 */
	public static function getChildrenById($cat_parent_id, $ignore_offlines = false, $clang = null) {
		return sly_Util_Category::findByParentId($cat_parent_id, $ignore_offlines, $clang);
	}

	/**
	 * Return a list of top level categories, ie.
	 * categories that have no parent.
	 * Returns an array of OOCategory objects sorted by $prior.
	 *
	 * If $ignore_offlines is set to TRUE,
	 * all categories with status 0 will be
	 * excempt from this list!
	 * @deprecated
	 */
	public static function getRootCategories($ignore_offlines = false, $clang = false) {
		return sly_Util_Category::getRootCategories($ignore_offlines, $clang);
	}
	
	/**
	 *
	 * @param sly_Model_Category $category
	 * @return boolean 
	 * @deprecated
	 */
	public static function isValid($category) {
		return sly_Util_Category::isValid($category);
	}
}
