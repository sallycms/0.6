<?php

/**
 * Object Oriented Framework: Bildet einen Artikel der Struktur ab
 * @package redaxo4
 * @version svn:$Id$
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
			$clang = Core::getCurrentClang();
		}

		$clang     = (int) $clang;
		$namespace = $OOCategory ? 'category' : 'article';

		$key       = $article_id.'_'.$clang;
		$obj       = Core::cache()->get($namespace, $key, null);

		if ($obj === null) {
			$article = rex_sql::fetch('*', 'article', 'id = '.$article_id.' AND clang = '.$clang);

			if ($article) {
				$class = $OOCategory ? 'OOCategory' : 'OOArticle';
				$obj   = new $class($article, $clang);

				
				Core::cache()->set($namespace, $key, $obj);
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
		global $REX;
		return self::getArticleById($a_category_id, $clang);
	}

	/**
	 * @return array
	 */
	public static function getArticlesOfCategory($category_id, $ignore_offlines = false, $clang = false)
	{
		global $REX;

		if ($clang === false) {
			$clang = Core::getCurrentClang();
		}
		
		$category_id = (int) $category_id;
		$clang       = (int) $clang;

		$namespace = 'alist';
		$key       = $category_id.'_'.$clang.'_'.($ignore_offlines ? 1 : 0);
		$alist     = Core::cache()->get($namespace, $key, null);
	
		if ($alist === null) {
			$where = 're_id = '.$category_id.' AND clang = '.$clang.($ignore_offlines ? ' AND status = 1' : '');
			$query = 'SELECT id FROM '.$REX['TABLE_PREFIX'].'article WHERE '.$where.' ORDER BY prior,name';
			$alist = array_map('intval', rex_sql::getArrayEx($query));
			
			if ($category_id != 0) {
				array_unshift($alist, $category_id);
			}

			Core::cache()->set($namespace, $key, $alist);
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
		global $REX;
		
		if (Core::cache()->get('article', $articleId.'_'.Core::getCurrentClang(), null) !== null) {
			return true;
		}
		
		// prüfen, ob ID in Content Cache Dateien vorhanden
		
		$cacheFiles = glob($REX['INCLUDE_PATH'].'/generated/articles/'.$articleId.'.*');
		
		if (!empty($cacheFiles)) {
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

	public function hasValue($value)
	{
		return parent::hasValue($value, array('art_'));
	}
}
