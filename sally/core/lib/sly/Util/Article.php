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
	 * checks wheter an article exists or not
	 *
	 * @param  int $articleId
	 * @return boolean
	 */
	public static function exists($articleId) {
		return self::isValid(self::findById($articleId));
	}

	/**
	 * @param  mixed $article
	 * @return boolean
	 */
	public static function isValid($article) {
		return is_object($article) && ($article instanceof sly_Model_Article);
	}

	/**
	 * @param  int $articleId
	 * @param  int $clang
	 * @return sly_Model_Article
	 */
	public static function findById($articleId, $clang = null) {
		return sly_Service_Factory::getArticleService()->findById($articleId, $clang);
	}

	/**
	 * @param  int $clang
	 * @return sly_Model_Article
	 */
	public static function findSiteStartArticle($clang = null) {
		return self::findById(sly_core::getSiteStartArticleId(), $clang);
	}

	/**
	 * @param  int     $categoryId
	 * @param  boolean $ignore_offlines
	 * @param  int     $clangId
	 * @return array
	 */
	public static function findByCategory($categoryId, $ignore_offlines = false, $clangId = false) {
		return sly_Service_Factory::getArticleService()->findArticlesByCategory($categoryId, $ignore_offlines, $clangId);
	}

	/**
	 * @param  boolean $ignore_offlines
	 * @param  int     $clang
	 * @return array
	 */
	public static function getRootArticles($ignore_offlines = false, $clang = false) {
		return self::findByCategory(0, $ignore_offlines, $clang);
	}

	/**
	 * @param  sly_Model_Article $article
	 * @return boolean
	 */
	public static function isSiteStartArticle(sly_Model_Article $article) {
		return $article->getId() == sly_Core::getSiteStartArticleId();
	}

	/**
	 * @param  sly_Model_Article $article
	 * @return boolean
	 */
	public static function isNotFoundArticle(sly_Model_Article $article) {
		return $article->getId() == sly_Core::getNotFoundArticleId();
	}

	public static function canReadArticle(sly_Model_User $user, $articleId) {
		if ($user->isAdmin() || $user->hasRight('csr['.$articleId.']')) return true;
		if(!sly_Authorisation::hasProvider()) {
			if($articleId === 0) {
				return true;
			}
		}
		/*if(!sly_Authorisation::hasProvider()) {
			$cat = sly_Util_Article::findById($articleId)->getCategory();
			while ($cat) {
				if ($user->hasRight('csw['.$cat->getId().']')) return true;
				$cat = $cat->getParent();
			}
		}*/

		return false;
	}

	public static function canEditArticle(sly_Model_User $user, $articleId) {
		if ($user->hasRight('editContentOnly[]')) return false;
		return self::canEditContent($user, $articleId);
	}

	public static function canEditContent(sly_Model_User $user, $articleId) {
		if ($user->isAdmin() || $user->hasRight('csw[0]')) return true;

		if(sly_Authorisation::hasProvider()) {
			return sly_Authorisation::hasPermission($user->getId(), 'csw', $articleId);
		} else {
			if(sly_Util_Article::exists($articleId)) {
				$cat = sly_Util_Article::findById($articleId)->getCategory();
				while ($cat) {
					if ($user->hasRight('csw['.$cat->getId().']')) return true;
					$cat = $cat->getParent();
				}
			}
		}

		return false;
	}
}
