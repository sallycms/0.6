<?php


/**
 *
 * The OOArticleSlice class is an object wrapper over the database table rex_article_slice.
 * Together with OOArticle and OOCategory it provides an object oriented
 * Framework for accessing vital parts of your website.
 * This framework can be used in Modules, Templates and PHP-Slices!
 *
 * @package redaxo4
 * @version svn:$Id$
 */

class OOArticleSlice
{
	private $_id;
	private $_article_id;
	private $_clang;
	private $_ctype;
	private $_modultyp_id;

	private $_re_article_slice_id;
	private $_next_article_slice_id;

	private $_createdate;
	private $_updatedate;
	private $_createuser;
	private $_updateuser;
	private $_revision;

	private $_values;
	private $_files;
	private $_filelists;
	private $_links;
	private $_linklists;
	private $_php;
	private $_html;

	/*
	 * Constructor
	 */
	public function __construct(
	$id, $article_id, $clang, $ctype, $modultyp_id,
	$re_article_slice_id, $next_article_slice_id,
	$createdate,$updatedate,$createuser,$updateuser,$revision,
	$values, $files, $filelists, $links, $linklists, $php, $html)
	{
		$this->_id = $id;
		$this->_article_id = $article_id;
		$this->_clang = $clang;
		$this->_ctype = $ctype;
		$this->_modultyp_id = $modultyp_id;

		$this->_re_article_slice_id = $re_article_slice_id;
		$this->_next_article_slice_id = $next_article_slice_id;

		$this->_createdate = $createdate;
		$this->_updatedate = $updatedate;
		$this->_createuser = $createuser;
		$this->_updateuser = $updateuser;
		$this->_revision = $revision;

		$this->_values = $values;
		$this->_files = $files;
		$this->_filelists = $filelists;
		$this->_links = $links;
		$this->_linklists = $linklists;
		$this->_php = $php;
		$this->_html = $html;
	}

	/*
	 * CLASS Function:
	 * Return an ArticleSlice by its id
	 * Returns an OOArticleSlice object
	 */
	public static function getArticleSliceById($an_id, $clang = false, $revision = 0)
	{
		if ($clang === false) $clang = Core::getCurrentClang();
		$namespace = 'slice';
		$key = $an_id;

		$obj = Core::cache()->get($namespace, $key, null);
		if ($obj === null) {
			$obj = self::_getSliceWhere('id='. $an_id .' AND clang='. $clang.' and revision='.$revision);
			Core::cache()->set($namespace, $key, $obj);
		}
		return $obj;
	}

	/*
	 * CLASS Function:
	 * Return the first slice for an article.
	 * This can then be used to iterate over all the
	 * slices in the order as they appear using the
	 * getNextSlice() function.
	 * Returns an OOArticleSlice object
	 */
	public static function getFirstSliceForArticle($an_article_id, $clang = false, $revision = 0)
	{
		global $REX;

		if ($clang === false)
		$clang = Core::getCurrentClang();

		return self::_getSliceWhere('a.article_id='. $an_article_id .' AND
                                          a.clang='. $clang .' AND
                                          (
                                           (a.re_article_slice_id=0 AND a.ctype=1 AND a.id = b.id)
                                            OR
                                           (b.ctype=2 AND a.ctype=1 AND b.id = a.re_article_slice_id)
                                          )
                                          AND a.revision='.$revision.' 
                                          AND b.revision='.$revision,
		$REX['TABLE_PREFIX'].'article_slice a, '. $REX['TABLE_PREFIX'].'article_slice b',
                                          'a.*' 
                                          );
	}

	/*
	 * CLASS Function:
	 * Returns the first slice of the given ctype of an article
	 */
	public static function getFirstSliceForCtype($ctype, $an_article_id, $clang = false, $revision = 0)
	{
		global $REX;

		if ($clang === false)
		$clang = Core::getCurrentClang();

		return self::_getSliceWhere('a.article_id='. $an_article_id .' AND
                                          a.clang='. $clang .' AND
                                          a.ctype='. $ctype .' AND
                                          (
                                           (a.re_article_slice_id=0  AND a.id = b.id)
                                            OR
                                           (b.ctype != a.ctype AND b.id = a.re_article_slice_id)
                                          )
                                          AND a.revision='.$revision.' 
                                          AND b.revision='.$revision,
		$REX['TABLE_PREFIX'].'article_slice a, '. $REX['TABLE_PREFIX'].'article_slice b',
                                          'a.*'
                                          );
	}

	/*
	 * CLASS Function:
	 * Return all slices for an article that have a certain
	 * module type.
	 * Returns an array of OOArticleSlice objects
	 */
	public static function getSlicesForArticleOfType($an_article_id, $a_moduletype_id, $clang = false, $revision = 0)
	{
		global $REX;

		if ($clang === false)
		$clang = Core::getCurrentClang();

		return self::_getSliceWhere('article_id='. $an_article_id .' AND clang='. $clang .' AND modultyp_id='. $a_moduletype_id .' AND revision='.$revision, array());
	}

	/*
	 * Object Function:
	 * Return the next slice for this article
	 * Returns an OOArticleSlice object.
	 */
	public function getNextSlice()
	{
		return self::_getSliceWhere('re_article_slice_id = '. $this->_id .' AND clang = '. $this->_clang.' AND revision='.$this->_revision);
	}

	/*
	 * Object Function:
	 */
	public function getPreviousSlice()
	{
		return self::_getSliceWhere('id = '. $this->_re_article_slice_id .' AND clang = '. $this->_clang.' AND revision='.$this->_revision);
	}

	/**
	 * Gibt den Slice formatiert zurück
	 * @since 4.1 - 29.05.2008
	 */
	public function getSlice()
	{
		$art = new rex_article();
		$art->setArticleId($this->getArticleId());
		$art->setClang($this->getClang());
		$art->getSlice = $this->getId();
		$art->setSliceRevision($this->_revision);
		$content = $art->getArticle();
		$content = self::replaceLinks($content);
		$content = $this->replaceCommonVars($content);
		return $content;
	}

	public function getContent(){
		global $REX, $I18N;
		$slice_content_file = $REX['INCLUDE_PATH'].'/generated/articles/'.$this->getId().'.slice';
		if (!file_exists($slice_content_file)) {
			$slice_content = $this->getSlice();
			$slice_content = self::replaceLinks($slice_content);
			if (rex_put_file_contents($slice_content_file, $slice_content) === FALSE)
			{
				return $I18N->msg('slice_could_not_be_generated')." ".$I18N->msg('check_rights_in_directory').$REX['INCLUDE_PATH']."/generated/articles/";
			}
		}
		if(file_exists($slice_content_file))
		{
			include $slice_content_file;
		}
	}

	public static function _getSliceWhere($where, $table = null, $fields = null, $default = null)
	{
		global $REX;

		if(!$table)
		$table = $REX['TABLE_PREFIX'].'article_slice';

		if(!$fields)
		$fields = '*';

		$sql = new rex_sql;
		// $sql->debugsql = true;
		$query = '
      SELECT '. $fields .'
      FROM '. $table .'
      WHERE '. $where;

		$sql->setQuery($query);
		$rows = $sql->getRows();

		$slices = array ();
		for ($i = 0; $i < $rows; $i++) {
			$slices[] = new OOArticleSlice(
			$sql->getValue('id'), $sql->getValue('article_id'), $sql->getValue('clang'), $sql->getValue('ctype'), $sql->getValue('modultyp_id'),
			$sql->getValue('re_article_slice_id'), $sql->getValue('next_article_slice_id'),
			$sql->getValue('createdate'), $sql->getValue('updatedate'), $sql->getValue('createuser'), $sql->getValue('updateuser'), $sql->getValue('revision'),
			array($sql->getValue('value1'), $sql->getValue('value2'), $sql->getValue('value3'), $sql->getValue('value4'), $sql->getValue('value5'), $sql->getValue('value6'), $sql->getValue('value7'), $sql->getValue('value8'), $sql->getValue('value9'), $sql->getValue('value10'), $sql->getValue('value11'), $sql->getValue('value12'), $sql->getValue('value13'), $sql->getValue('value14'), $sql->getValue('value15'), $sql->getValue('value16'), $sql->getValue('value17'), $sql->getValue('value18'), $sql->getValue('value19'), $sql->getValue('value20')),
			array($sql->getValue('file1'), $sql->getValue('file2'), $sql->getValue('file3'), $sql->getValue('file4'), $sql->getValue('file5'), $sql->getValue('file6'), $sql->getValue('file7'), $sql->getValue('file8'), $sql->getValue('file9'), $sql->getValue('file10')),
			array($sql->getValue('filelist1'), $sql->getValue('filelist2'), $sql->getValue('filelist3'), $sql->getValue('filelist4'), $sql->getValue('filelist5'), $sql->getValue('filelist6'), $sql->getValue('filelist7'), $sql->getValue('filelist8'), $sql->getValue('filelist9'), $sql->getValue('filelist10')),
			array($sql->getValue('link1'), $sql->getValue('link2'), $sql->getValue('link3'), $sql->getValue('link4'), $sql->getValue('link5'), $sql->getValue('link6'), $sql->getValue('link7'), $sql->getValue('link8'), $sql->getValue('link9'), $sql->getValue('link10')),
			array($sql->getValue('linklist1'), $sql->getValue('linklist2'), $sql->getValue('linklist3'), $sql->getValue('linklist4'), $sql->getValue('linklist5'), $sql->getValue('linklist6'), $sql->getValue('linklist7'), $sql->getValue('linklist8'), $sql->getValue('linklist9'), $sql->getValue('linklist10')),
			$sql->getValue('php'), $sql->getValue('html'));

			$sql->next();
		}
	 if (!empty($slices)) return count($slices) == 1 ? $slices[0] : $slices;

	 return $default;
	}

	public function getArticle()
	{
		return OOArticle::getArticleById($this->getArticleId());
	}

	public function getArticleId()
	{
		return $this->_article_id;
	}

	public function getClang()
	{
		return $this->_clang;
	}

	public function getCtype()
	{
		return $this->_ctype;
	}

	public function getRevision()
	{
		return $this->_revision;
	}

	public function getModuleId()
	{
		return $this->_modultyp_id;
	}

	public function getId()
	{
		return $this->_id;
	}

	public function getValue($index)
	{
		if(is_int($index))
		return $this->_values[$index-1];

		$attrName = '_'. $index;
		if(isset($this->$attrName))
		return $this->$attrName;

		return null;
	}

	public function getLink($index)
	{
		return $this->_links[$index-1];
	}

	public function getLinkUrl($index)
	{
		return rex_getUrl($this->getLink($index));
	}

	public function getLinkList($index)
	{
		return $this->_linklists[$index-1];
	}

	public function getMedia($index)
	{
		return $this->_files[$index-1];
	}

	public function getMediaUrl($index)
	{
		global $REX;
		return $REX['MEDIAFOLDER'].'/'.$this->getMedia($index);
	}

	public function getMediaList($index)
	{
		return $this->_filelists[$index-1];
	}

	public function getHtml()
	{
		return $this->_html;
	}

	public function getPhp()
	{
		return $this->_php;
	}

	// ---- Artikelweite globale variablen werden ersetzt
	private function replaceCommonVars($content)
	{
		global $REX;

		static $user_id = null;
		static $user_login = null;

		// UserId gibts nur im Backend
		if($user_id === null)
		{
			if(isset($REX['USER']))
			{
				$user_id = $REX['USER']->getValue('user_id');
				$user_login = $REX['USER']->getValue('login');
			}else
			{
				$user_id = '';
				$user_login = '';
			}
		}

		static $search = array(
       'REX_ARTICLE_ID',
       'REX_CATEGORY_ID',
       'REX_CLANG_ID',
       'REX_TEMPLATE_ID',
       'REX_USER_ID',
       'REX_USER_LOGIN'
       );
		
       $article = $this->getArticle();
       
       $replace = array(
       $article->getId(),
       $article->getCategoryId(),
       $article->getClang(),
       $article->getTemplateId(),
       $user_id,
       $user_login
       );

       return str_replace($search, $replace,$content);
	}


	private static function replaceLinks($content)
	{
		// Hier beachten, dass man auch ein Zeichen nach dem jeweiligen Link mitmatched,
		// damit beim ersetzen von z.b. redaxo://11 nicht auch innerhalb von redaxo://112
		// ersetzt wird
		// siehe dazu: http://forum.redaxo.de/ftopic7563.html

		// -- preg match redaxo://[ARTICLEID]-[CLANG] --
		preg_match_all('@redaxo://([0-9]*)\-([0-9]*)(.){1}/?@im',$content,$matches,PREG_SET_ORDER);
		foreach($matches as $match)
		{
			if(empty($match)) continue;

			$url = OOArticle::getArticleById($match[1], $match[2])->getUrl();
			$content = str_replace($match[0],$url.$match[3],$content);
		}

		// -- preg match redaxo://[ARTICLEID] --
		preg_match_all('@redaxo://([0-9]*)(.){1}/?@im',$content,$matches,PREG_SET_ORDER);
		foreach($matches as $match)
		{
			if(empty($match)) continue;

			$url = OOArticle::getArticleById($match[1])->getUrl();
			$content = str_replace($match[0],$url.$match[2],$content);
		}

		return $content;
	}
}