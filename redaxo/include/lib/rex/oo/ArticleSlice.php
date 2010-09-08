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
	private $_slice_id;

	private $_re_article_slice_id;
	private $_next_article_slice_id;

	private $_createdate;
	private $_updatedate;
	private $_createuser;
	private $_updateuser;
	private $_revision;

	const CACHE_NS = 'slice';

	/*
	 * Constructor
	 */
	public function __construct(
	$id, $article_id, $clang, $ctype, $modultyp_id,
	$re_article_slice_id, $next_article_slice_id,
	$createdate,$updatedate,$createuser,$updateuser,$revision,
	$slice_id = 0)
	{
		$this->_id = $id;
		$this->_article_id = $article_id;
		$this->_clang = $clang;
		$this->_ctype = $ctype;
		$this->_modultyp_id = $modultyp_id;
		$this->_slice_id = $slice_id;

		$this->_re_article_slice_id = $re_article_slice_id;
		$this->_next_article_slice_id = $next_article_slice_id;

		$this->_createdate = $createdate;
		$this->_updatedate = $updatedate;
		$this->_createuser = $createuser;
		$this->_updateuser = $updateuser;
		$this->_revision = $revision;

	}

	/*
	 * CLASS Function:
	 * Return an ArticleSlice by its id
	 * Returns an OOArticleSlice object
	 */
	public static function getArticleSliceById($an_id, $clang = false, $revision = 0)
	{
		if ($clang === false) $clang = sly_Core::getCurrentClang();
		$key = $an_id;

		// Cache is flushed ob article change (see content.inc.php).
		$obj = sly_Core::cache()->get(self::CACHE_NS, $key, null);
		if ($obj === null) {
			$obj = self::_getSliceWhere('id='. $an_id .' AND clang='. $clang.' and revision='.$revision);
			sly_Core::cache()->set(self::CACHE_NS, $key, $obj);
		}
		return $obj;
	}

	/**
	 * CLASS Function:
	 * Return the first slice for an article.
	 * This can then be used to iterate over all the
	 * slices in the order as they appear using the
	 * getNextSlice() function.
	 * Returns an OOArticleSlice object
	 *
	 * @return OOArticleSlice
	 */
	public static function getFirstSliceForArticle($an_article_id, $clang = false, $revision = 0)
	{
		global $REX;

		if ($clang === false)
		$clang = sly_Core::getCurrentClang();

		return self::_getSliceWhere('a.article_id='. $an_article_id .' AND
                                          a.clang='. $clang .' AND
                                          (
                                           (a.re_article_slice_id=0 AND a.ctype=1 AND a.id = b.id)
                                            OR
                                           (b.ctype=2 AND a.ctype=1 AND b.id = a.re_article_slice_id)
                                          )
                                          AND a.revision='.$revision.'
                                          AND b.revision='.$revision,
		$REX['DATABASE']['TABLE_PREFIX'].'article_slice a, '. $REX['DATABASE']['TABLE_PREFIX'].'article_slice b',
                                          'a.*'
                                          );
	}

	/**
	 * CLASS Function:
	 * Returns the first slice of the given ctype of an article
	 *
	 * @return OOArticleSlice
	 */
	public static function getFirstSliceForCtype($ctype, $an_article_id, $clang = false, $revision = 0)
	{
		global $REX;

		if ($clang === false)
		$clang = sly_Core::getCurrentClang();

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
		$REX['DATABASE']['TABLE_PREFIX'].'article_slice a, '. $REX['DATABASE']['TABLE_PREFIX'].'article_slice b',
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
		$clang = sly_Core::getCurrentClang();

		return self::_getSliceWhere('article_id='. $an_article_id .' AND clang='. $clang .' AND modultyp_id='. $a_moduletype_id .' AND revision='.$revision, array());
	}

	/**
	 * Object Function:
	 * Return the next slice for this article
	 * Returns an OOArticleSlice object.
	 *
	 * @return OOArticleSlice
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
		$slice = sly_Service_Factory::getService('Slice')->findById($this->getSliceId());
		$content = $slice->getOutput();

		$content = self::replaceLinks($content);
		$content = $this->replaceCommonVars($content);
		$content = $this->replaceGlobals($content);
		return $content;
	}

	public function getContent(){
		global $REX, $I18N;
		$slice_content_file = $REX['DYNFOLDER'].'/internal/sally/articles/'.$this->getId().'.slice';
		if (!file_exists($slice_content_file)) {
			$slice_content = $this->getSlice();
			if (rex_put_file_contents($slice_content_file, $slice_content) === FALSE)
			{
				return $I18N->msg('slice_could_not_be_generated')." ".$I18N->msg('check_rights_in_directory').$REX['DYNFOLDER']."/internal/sally/articles/";
			}
		}
		if(file_exists($slice_content_file))
		{
			include $slice_content_file;
		}
	}

	/**
	 *
	 *
	 * @param string $where
	 * @param string $table
	 * @param string $fields
	 * @param mixed $default
	 *
	 * @return array of OOArticleSlice
	 */
	public static function _getSliceWhere($where, $table = null, $fields = null, $default = null)
	{
		global $REX;

		if(!$table)
		$table = $REX['DATABASE']['TABLE_PREFIX'].'article_slice';

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
			$sql->getValue('slice_id'));

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

	public function getReId()
	{
		return $this->_re_article_slice_id;
	}

	public function getSliceId(){
		return $this->_slice_id;
	}


	public function getValue($index)
	{
		$value = sly_Service_Factory::getService('SliceValue')->findBySliceTypeFinder($this->getSliceId(), 'REX_VALUE', $index);
		if($value){
			return $value->getValue();
		}

		return null;
	}

	public function getLink($index)
	{
		$value = sly_Service_Factory::getService('SliceValue')->findBySliceTypeFinder($this->getSliceId(), 'REX_LINK', $index);
		if($value){
			return $value->getValue();
		}

		return null;
	}

	public function getLinkUrl($index)
	{

		return rex_getUrl($this->getLink());

		return null;
	}

	public function getLinkList($index)
	{
		$value = sly_Service_Factory::getService('SliceValue')->findBySliceTypeFinder($this->getSliceId(), 'REX_LINKLIST', $index);
		if($value){
			return $value->getValue();
		}

		return null;
	}

	public function getMedia($index)
	{
		$value = sly_Service_Factory::getService('SliceValue')->findBySliceTypeFinder($this->getSliceId(), 'REX_MEDIA', $index);
		if($value){
			return $value->getValue();
		}

		return null;
	}

	public function getMediaUrl($index)
	{
		global $REX;
		return $REX['MEDIAFOLDER'].'/'.$this->getMedia($index);
	}

	public function getMediaList($index)
	{
		$value = sly_Service_Factory::getService('SliceValue')->findBySliceTypeFinder($this->getSliceId(), 'REX_MEDIALIST', $index);
		if($value){
			return $value->getValue();
		}

		return null;
	}

	public function getHtml()
	{
		$value = sly_Service_Factory::getService('SliceValue')->findBySliceTypeFinder($this->getSliceId(), 'REX_HTML', $index);
		if($value){
			return $value->getValue();
		}

		return null;
	}

	public function getPhp()
	{
		$value = sly_Service_Factory::getService('SliceValue')->findBySliceTypeFinder($this->getSliceId(), 'REX_PHP', $index);
		if($value){
			return $value->getValue();
		}

		return null;
	}

	private function replaceGlobals($content){
	    // Articleslice abhängige Globale Variablen ersetzen
	    $slice = sly_Service_Factory::getService('Slice')->findById($this->getSliceId());

    	$content = str_replace('REX_MODULE_ID', $slice->getModuleId(), $content);
	    $content = str_replace('REX_SLICE_ID', $this->getId(), $content);
    	$content = str_replace('REX_CTYPE_ID', $this->getCtype(), $content);

    	return $content;
	}

	// ---- Artikelweite globale variablen werden ersetzt
	private function replaceCommonVars($content) {
		global $REX;

		static $user_id = null;
		static $user_login = null;

		// UserId gibts nur im Backend
		if($user_id === null) {
			if(isset($REX['USER'])) {
				$user_id = $REX['USER']->getValue('id');
				$user_login = $REX['USER']->getValue('login');
			}else {
				$user_id = '';
				$user_login = '';
			}
		}

		$article = $this->getArticle();

		static $search = array(
		'REX_ARTICLE_ID',
		'REX_CATEGORY_ID',
		'REX_CLANG_ID',
		'REX_TEMPLATE_ID',
		'REX_USER_ID',
		'REX_USER_LOGIN'
		);

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

		foreach($matches as $match) {
			if (empty($match)) continue;
			$replace = self::getReplacementLink($match[1], $match[2], $match[3]);
			$content = str_replace($match[0], $replace, $content);
		}

		// -- preg match redaxo://[ARTICLEID] --
		preg_match_all('@redaxo://([0-9]*)(.){1}/?@im',$content,$matches,PREG_SET_ORDER);

		foreach($matches as $match) {
			if (empty($match)) continue;
			$replace = self::getReplacementLink($match[1], false, $match[2]);
			$content = str_replace($match[0], $replace, $content);
		}

		return $content;
	}

	private static function getReplacementLink($articleID, $clang = false, $params = '')
	{
		$art = OOArticle::getArticleById($articleID, $clang);
		if ($art === null) return '';
		return $art->getUrl().$params;
	}
}
