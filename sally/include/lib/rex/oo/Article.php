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
 * @deprecated
 * @ingroup redaxo2
 */
abstract class OOArticle
{
	/**
	 * @return sly_Model_Article
	 * @deprecated
	 */
	public static function getArticleById($article_id, $clang = false, $OOCategory = false)
	{
		return sly_Util_Article::findById($articleId, $clang);
	}

	/**
	 * @return sly_Model_Article
	 * @deprecated
	 */
	public static function getSiteStartArticle($clang = null)
	{
		return sly_Util_Article::findSiteStartArticle($clang);
	}

	/**
	 * @return array
	 * @deprecated
	 */
	public static function getArticlesOfCategory($categoryId, $ignore_offlines = false, $clangId = false)
	{
		return sly_Util_Article::findByCategory($categoryId, $ignore_offlines, $clangId);
	}

	/**
	 * Return a list of top-level articles
	 * @return array
	 * @deprecated
	 */
	public static function getRootArticles($ignore_offlines = false, $clang = false)
	{
		return sly_Util_Article::getRootArticles($ignore_offlines, $clang);
	}

}