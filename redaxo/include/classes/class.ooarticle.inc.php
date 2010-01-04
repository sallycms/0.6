<?php

/**
* Object Oriented Framework: Bildet einen Artikel der Struktur ab
* @package redaxo4
* @version svn:$Id$
*/

class OOArticle extends OORedaxo {
	
	public function __construct($params = false, $clang = false) {
		parent::__construct($params, $clang);
	}
	
	/**
	* CLASS Function:
	* Return an OORedaxo object based on an id
	*/
	public static function getArticleById($article_id, $clang = false, $OOCategory = false) {
		global $REX;
		
		$article_id = (int) $article_id;

		if($clang === false) { $clang = $REX['CUR_CLANG']; }

		$namespace = $OOCategory ? 'category' : 'article';
		$key = $article_id.'_'.$clang;
		
		$obj = Core::cache()->get($namespace, $key, null);
		
		if($obj === null) {
			$article = rex_sql::getInstance();
			$article->setQuery("SELECT * FROM ".$REX['TABLE_PREFIX']."article WHERE id = '$article_id' AND clang = '$clang' LIMIT 1");
			
			if($article->rows > 0) {
				if ($OOCategory) {
					$obj = new OOCategory(mysql_fetch_array($article->result, MYSQL_ASSOC), $clang);
				} else {
					$obj = new OOArticle(mysql_fetch_array($article->result, MYSQL_ASSOC), $clang);
				}
				
				Core::cache()->set($namespace, $key, $obj);
			}
			
			$article->freeResult();
		}
		
		return $obj;
	}

	/**
	* CLASS Function:
	* Return the site wide start article
	*/
	public static function getSiteStartArticle($clang = false) {
		global $REX;
		
		return OOArticle :: getArticleById($REX['START_ARTICLE_ID'], $clang);
	}

	/**
	* CLASS Function:
	* Return start article for a certain category
	*/
	public static function getCategoryStartArticle($a_category_id, $clang = false) {
		global $REX;
		
		return OOArticle::getArticleById($a_category_id, $clang);
	}

	/**
	* CLASS Function:
	* Return a list of articles for a certain category
	*/
	public static function getArticlesOfCategory($a_category_id, $ignore_offlines = false, $clang = false) {
		global $REX;

		if ($clang === false) { $clang = $REX['CUR_CLANG']; }
		$a_category_id = (int) $a_category_id;

		$namespace = 'alist';
		$key   = $a_category_id.'_'.$clang;
		$alist = Core::cache()->get($namespace, $key, null);
	
		if($alist === null) {
			$alist = array();
			if($a_category_id !== 0) $alist[] = $a_category_id;
			
			$sql = rex_sql::getInstance();
			$sql->setQuery("SELECT id FROM " . $REX['TABLE_PREFIX'] . "article WHERE re_id='$a_category_id' AND clang='$clang' ORDER BY prior,name");
			
			while ($row = mysql_fetch_array($sql->result, MYSQL_NUM)) {
				$alist[] = $row[0];  
			}
			
			$sql->freeResult();
			
			Core::cache()->set($namespace, $key, $alist);
		
		}

		$artlist = array ();
			
		foreach($alist as $var) {
			
			$article = OOArticle::getArticleById($var, $clang);
			
			if (!$ignore_offlines || ($ignore_offlines && $article->isOnline())) {
				$artlist[] = $article;
			}
		}
		
		return $artlist;
	}

	/**
	* CLASS Function:
	* Return a list of top-level articles
	*/
	public static function getRootArticles($ignore_offlines = FALSE, $clang = FALSE) {
		return OOArticle::getArticlesOfCategory(0, $ignore_offlines, $clang);

	}

	/**
	* Accessor Method:
	* returns the category id
	*/
	public function getCategoryId() {
		return $this->isStartPage() ? $this->getId() : $this->getParentId();
	}

	/*
	* Object Function:
	* Returns the parent category
	*/
	public function getCategory() {
		return OOCategory::getCategoryById($this->getCategoryId(), $this->getClang());
	}

	/*
	* Static Method: Returns boolean if article exists with requested id
	*/
	public static function exists($articleId) {
		global $REX;
		
	
		if(Core::cache()->get('article', $articleId.'_'.$REX['CUR_CLANG'], null) != null) {
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
	
	/*
	* Static Method: Returns boolean if is article
	*/
	public static function isValid($article) {
		return is_object($article) && ($article instanceof OOArticle);
	}
	
	public function getValue($value) {
		// alias für re_id -> category_id
		if(in_array($value, array('re_id', '_re_id', 'category_id', '_category_id'))) {
			// für die CatId hier den Getter verwenden,
			// da dort je nach ArtikelTyp unterscheidungen getroffen werden müssen
			return $this->getCategoryId();
		}
		
		return parent::getValue($value);
	}

	public function hasValue($value) {
		return parent::hasValue($value, array('art_'));
	}
}