<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 *
 * The OOArticleSlice class is an object wrapper over the database table rex_article_slice.
 * Together with OOArticle and OOCategory it provides an object oriented
 * Framework for accessing vital parts of your website.
 * This framework can be used in Modules, Templates and PHP-Slices!
 *
 * @ingroup redaxo2
 */
class OOArticleSlice {
	private $_id;
	private $_article_id;
	private $_clang;
	private $_slot;
	private $_module;
	private $_slice_id;
	private $_prior;

	private $_createdate;
	private $_updatedate;
	private $_createuser;
	private $_updateuser;
	private $_revision;

	const CACHE_NS = 'sly.slice';

	/**
	 * Constructor
	 */
	public function __construct($id, $article_id, $clang, $slot, $module, $prior,
		$createdate, $updatedate, $createuser, $updateuser, $revision, $slice_id = 0) {
		$this->_id         = (int) $id;
		$this->_article_id = (int) $article_id;
		$this->_clang      = (int) $clang;
		$this->_slot       = $slot;
		$this->_module     = $module;
		$this->_slice_id   = (int) $slice_id;
		$this->_prior   = (int) $prior;

		$this->_createdate = (int) $createdate;
		$this->_updatedate = (int) $updatedate;
		$this->_createuser = $createuser;
		$this->_updateuser = $updateuser;
		$this->_revision   = (int) $revision;
	}

	/**
	 * @return OOArticleSlice
	 */
	public static function getArticleSliceById($id, $clang = false, $revision = 0) {
		if ($clang === false) $clang = sly_Core::getCurrentClang();

		$namespace = 'sly.slice';
		$clang     = (int) $clang;
		$id        = (int) $id;
		$revision  = (int) $revision;
		$key       = sly_Cache::generateKey('by_id', $id, $clang, $revision);
		$obj       = sly_Core::cache()->get($namespace, $key, null);

		if ($obj === null) {
			$obj = self::_getSliceWhere('id = '.$id.' AND clang = '.$clang.' AND revision = '.$revision);
			sly_Core::cache()->set(self::CACHE_NS, $key, $obj);
		}

		return $obj;
	}

	public static function getSliceIdsForSlot($article_id, $clang, $slot = null) {
		$cache    = sly_Cache::factory();
		$cachekey = sly_Cache::generateKey('slice_ids_for_slot', $article_id, $clang, $slot);
		$ids      = $cache->get(self::CACHE_NS, $cachekey);
		if(is_null($ids)) {
			$ids = array();
			$sql = sly_DB_Persistence::getInstance();
			$where = array('article_id' => $article_id, 'clang' => $clang);
			if(!is_null($slot)) {
				$where['slot'] = $slot;
			}
			$sql->select('article_slice', 'id', $where, null, 'slot, prior ASC');
			foreach($sql as $row){
				$ids[] = $row['id'];
			}
			$cache->set(self::CACHE_NS, $cachekey, $ids);

			rex_register_extension_point('CLANG_ARTICLE_GENERATED', '', array(
				'id'      => $article_id,
				'clang'   => $clang
			));
		}
		return $ids;
	}

	/**
	 * Return the first slice for an article.
	 *
	 * This can then be used to iterate over all the
	 * slices in the order as they appear using the
	 * getNextSlice() function.
	 * Returns an OOArticleSlice object
	 *
	 * @param  int     $articleID  The article id
	 * @param  string  $slot       The slot (if null, the first defined slot will be used)
	 * @param  int     $clang      The desired content language (if null, the current language will be used)
	 * @return OOArticleSlice
	 */
	public static function getFirstSliceForArticle($articleID, $slot = null, $clang = null, $revision = 0) {
		if ($clang === null) $clang = sly_Core::getCurrentClang();
		if ($slot  === null) {
			$template = self::getArticle()->getTemplateName();
			$slot = sly_Service_Factory::getService('Template')->getFirstSlot($template);

			if ($slot === null) return null;
		}

		$prefix    = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
		$articleID = (int) $articleID;
		$clang     = (int) $clang;
		$revision  = (int) $revision;

		return self::_getSliceWhere(
			'a.article_id = '.$articleID.' AND a.clang = "'.$clang.'" AND a.slot = "'.mysql_real_escape_string($slot).'" AND '.
			'((a.prior = 0  AND a.id = b.id) '.
			'OR (b.slot != a.slot AND b.id = a.prior)) '.
			'AND a.revision = '.$revision.' AND b.revision = '.$revision,
			$prefix.'article_slice a, '.$prefix.'article_slice b',
			'a.*'
		);
	}

	/*
	 * CLASS Function:
	 * Return all slices for an article that have a certain
	 * module type.
	 * Returns an array of OOArticleSlice objects
	 */
	public static function getSlicesForArticleOfType($articleID, $module, $clang = false, $revision = 0) {
		if ($clang === false) $clang = sly_Core::getCurrentClang();

		$prefix    = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
		$articleID = (int) $articleID;
		$clang     = (int) $clang;
		$revision  = (int) $revision;
		$module    = mysql_real_escape_string($module);

		return self::_getSliceWhere(
			'article_id = '.$articleID.' AND clang = '.$clang.' AND module = "'.$module.'" AND revision = '.$revision,
			array()
		);
	}

	/**
	 * Return the next slice for this article
	 * Returns an OOArticleSlice object.
	 *
	 * @return OOArticleSlice
	 */
	public function getNextSlice() {
		return self::_getSliceWhere('prior > '.$this->_prior.' AND slot = "'.mysql_real_escape_string($this->_slot).'" AND clang = '.$this->_clang.' AND article_id = '.$this->_article_id.' AND clang = '.$this->_clang.' LIMIT 1 ORDER BY prior ASC');
	}

	/**
	 * @return OOArticleSlice
	 */
	public function getPreviousSlice() {
		return self::_getSliceWhere('prior < '.$this->_prior.' AND slot = "'.mysql_real_escape_string($this->_slot).'" AND clang = '.$this->_clang.' AND article_id = '.$this->_article_id.' AND clang = '.$this->_clang.' LIMIT 1 ORDER BY prior DESC');
	}

	/**
	 * @return string the content of the slice
	 */
	public function getOutput() {
		$slice = $this->getSlice();
		$content = $slice->getOutput();
		$content = self::replaceLinks($content);
		$content = $this->replaceCommonVars($content);
		$content = $this->replaceGlobals($content);

		return $content;
	}

	public function printContent() {
		global $REX, $I18N;

		$cachedir = SLY_DYNFOLDER.'/internal/sally/article_slice/';
		sly_Util_Directory::create($cachedir);
		$modulefile = sly_Service_Factory::getModuleService()->getOutputFilename($this->getModuleName());

		$slice_content_file = $cachedir.$this->getSliceId().'-'.md5($modulefile).'.slice.php';

		if (!file_exists($slice_content_file)) {
			$slice_content = $this->getOutput();

			if (rex_put_file_contents($slice_content_file, $slice_content) === false) {
				return $I18N->msg('slice_could_not_be_generated').' '.$I18N->msg('check_rights_in_directory').SLY_DYNFOLDER.'/internal/sally/articles/';
			}
		}

		if (file_exists($slice_content_file)) {
			include $slice_content_file;
		}
	}

	/**
	 * @param  string $where
	 * @param  string $table
	 * @param  string $fields
	 * @param  mixed  $default
	 * @return array  OOArticleSlice
	 */
	public static function _getSliceWhere($where, $table = null, $fields = null, $default = null) {
		$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');

		if (!$table)  $table  = $prefix.'article_slice';
		if (!$fields) $fields = '*';

		$sql  = new rex_sql();
		$data = $sql->getArray('SELECT '.$fields.' FROM '.$table.' WHERE '.$where);

		foreach ($data as $row) {
			$slices[] = new OOArticleSlice(
				$row['id'], $row['article_id'], $row['clang'], $row['slot'], $row['module'],
				$row['prior'], $row['createdate'],
				$row['updatedate'], $row['createuser'], $row['updateuser'], $row['revision'],
				$row['slice_id']
			);
		}

		if (!empty($slices)) return count($slices) == 1 ? $slices[0] : $slices;
		return $default;
	}

	public function getArticle() {
		return OOArticle::getArticleById($this->getArticleId());
	}

	public function getArticleId()  { return $this->_article_id;          }
	public function getClang()      { return $this->_clang;               }
	public function getSlot()       { return $this->_slot;                }
	public function getRevision()   { return $this->_revision;            }
	public function getModuleName() { return $this->_module;              }
	public function getId()         { return $this->_id;                  }
	public function getPrior()      { return $this->_prior;               }
	public function getSliceId()    { return $this->_slice_id;            }
	/**
	 *
	 * @return Sly_Model_Slice
	 */
	public function getSlice()      { return sly_Service_Factory::getService('Slice')->findById($this->getSliceId()); }

	public function getValue($index)     { return $this->getRexVarValue('REX_VALUE', $index);     }
	public function getLink($index)      { return $this->getRexVarValue('REX_LINK', $index);      }
	public function getLinkList($index)  { return $this->getRexVarValue('REX_LINKLIST', $index);  }
	public function getMedia($index)     { return $this->getRexVarValue('REX_MEDIA', $index);     }
	public function getMediaList($index) { return $this->getRexVarValue('REX_MEDIALIST', $index); }

	public function getLinkUrl($index) {
		return rex_getUrl($this->getLink());
	}

	public function getMediaUrl($index) {
		return SLY_MEDIAFOLDER.'/'.$this->getMedia($index);
	}

	private function replaceGlobals($content) {
		// Slice-abhängige globale Variablen ersetzen

		$slice   = sly_Service_Factory::getService('Slice')->findById($this->getSliceId());
		$content = str_replace('REX_MODULE',   $slice->getModule(), $content);
		$content = str_replace('REX_SLICE_ID', $this->getId(),      $content);
		$content = str_replace('REX_CTYPE_ID', $this->getSlot(),    $content);
		$content = str_replace('REX_SLOT',     $this->getSlot(),    $content);

		return $content;
	}

	/**
	 * Artikelweite globale variablen werden ersetzt
	 */
	private function replaceCommonVars($content) {
		global $REX;

		static $user_id    = null;
		static $user_login = null;

		// UserId gibt's nur im Backend

		if ($user_id === null) {
			if (isset($REX['USER'])) {
				$user_id    = $REX['USER']->getId();
				$user_login = $REX['USER']->getLogin();
			}
			else {
				$user_id    = '';
				$user_login = '';
			}
		}

		$article = $this->getArticle();

		static $search = array(
			'REX_ARTICLE_ID',
			'REX_CATEGORY_ID',
			'REX_CLANG_ID',
			'REX_TEMPLATE_NAME',
			'REX_USER_ID',
			'REX_USER_LOGIN'
		);

		$replace = array(
			$article->getId(),
			$article->getCategoryId(),
			$article->getClang(),
			$article->getTemplateName(),
			$user_id,
			$user_login
		);

		return str_replace($search, $replace,$content);
	}

	private static function replaceLinks($content) {
		// Hier beachten, dass man auch ein Zeichen nach dem jeweiligen Link mitmatched,
		// damit beim ersetzen von z.b. redaxo://11 nicht auch innerhalb von redaxo://112
		// ersetzt wird
		// siehe dazu: http://forum.redaxo.de/ftopic7563.html

		// -- preg match redaxo://[ARTICLEID]-[CLANG] --
		preg_match_all('@(?:redaxo|sally)://([0-9]*)\-([0-9]*)(.){1}/?@im', $content, $matches, PREG_SET_ORDER);

		foreach ($matches as $match) {
			if (empty($match)) continue;
			$replace = self::getReplacementLink($match[1], $match[2]);
			$content = str_replace($match[0], $replace, $content);
		}

		// -- preg match redaxo://[ARTICLEID] --

		preg_match_all('@(?:redaxo|sally)://([0-9]*)(.){1}/?@im', $content, $matches, PREG_SET_ORDER);

		foreach ($matches as $match) {
			if (empty($match)) continue;
			$replace = self::getReplacementLink($match[1], false);
			$content = str_replace($match[0], $replace, $content);
		}

		return $content;
	}

	private static function getReplacementLink($articleID, $clang)
	{
		$art = OOArticle::getArticleById($articleID, $clang);
		if ($art === null) return '';
		return rex_getUrl($articleID, $clang, '', '', '', true);
	}

	protected function getRexVarValue($type, $key) {
		$value = sly_Service_Factory::getService('SliceValue')->findBySliceTypeFinder($this->getSliceId(), $type, $key);
		if ($value) return $value->getValue();
		return null;
	}
}
