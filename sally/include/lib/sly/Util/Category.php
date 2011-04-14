<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup util
 *
 * @author zozi@webvariants.de
 */
class sly_Util_Category {

	/**
	 * checks wheter a category exists or not
	 *
	 * @param  int $categoryId
	 * @return boolean
	 */
	public static function exists($categoryId) {
		return self::isValid(self::findById($categoryId));
	}

	/**
	 * @return boolean
	 */
	public static function isValid($category) {
		return is_object($category) && ($category instanceof sly_Model_Category);
	}

	/**
	 * @return sly_Model_Category
	 */
	public static function findById($categoryId, $clang = null) {
		return sly_Service_Factory::getCategoryService()->findById($categoryId, $clang);
	}
}
