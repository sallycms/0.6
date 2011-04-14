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
class sly_Util_Article {

	/**
	 * checks wheter a article exists or not
	 *
	 * @param  int $articleId
	 * @return boolean
	 */
	public static function exists($articleId) {
		return self::isValid(self::findById($articleId));
	}

	/**
	 * @return boolean
	 */
	public static function isValid($article) {
		return is_object($article) && ($article instanceof sly_Model_Article);
	}

	/**
	 * @return sly_Model_Article
	 */
	public static function findById($articleId, $clang = null) {
		return sly_Service_Factory::getArticleService()->findById($articleId, $clang);
	}
}
