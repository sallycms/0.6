<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Object Oriented Framework: Bildet ein Medium des Medienpools ab
 *
 * @package redaxo4
 */
class OOMedia
{
	private $id = '';
	private $parent_id = ''; // (FOR FUTURE USE!)
	private $cat_id = '';
	private $cat_name = '';
	private $cat = '';
	private $name = '';
	private $orgname = '';
	private $type = '';
	private $size = '';
	private $width = '';
	private $height = '';
	private $title = '';
	private $updatedate = '';
	private $createdate = '';
	private $updateuser = '';
	private $createuser = '';

	private static $dummeFileSize = null;

	protected function __construct($id = null)
	{
		$this->getMediaById($id);
	}

	public static function _getTableName()
	{
		global $REX;
		return $REX['DATABASE']['TABLE_PREFIX'].'file';
	}

	protected static function _getTableJoin()
	{
		$mediatable = self::_getTableName();
		$cattable   = OOMediaCategory::_getTableName();
		return $mediatable.' LEFT JOIN '.$cattable.' ON '.$mediatable.'.category_id = '.$cattable.'.id';
	}

	/**
	 * @return OOMedia
	 */
	public static function getMediaById($id)
	{
		$id = (int) $id;
		if ($id <= 0) return null;

		$media = sly_Core::cache()->get('media', $id, null);

		if ($media === null) {
			$query  = 'SELECT '.self::_getTableName().'.*, '.OOMediaCategory :: _getTableName().'.name catname FROM '.self::_getTableJoin().' WHERE file_id = '.$id;
			$sql    = new rex_sql();
			$result = $sql->getArray($query);

			if (empty($result)) {
				return null;
			}

			$result = $result[0];

			static $aliasMap = array(
				'file_id'      => 'id',
				're_file_id'   => 'parent_id',
				'category_id'  => 'cat_id',
				'catname'      => 'cat_name',
				'filename'     => 'name',
				'originalname' => 'orgname',
				'filetype'     => 'type',
				'filesize'     => 'size'
	      );

	      $media = new OOMedia();

	      foreach ($sql->getFieldNames() as $fieldName) {
	      	if (in_array($fieldName, array_keys($aliasMap))) {
					$var_name = $aliasMap[$fieldName];
				}
	      	else {
					$var_name = $fieldName;
				}

	      	$media->$var_name = $result[$fieldName];
	      }

	      sly_Core::cache()->set('media', $id, $media);
		}

		return $media;
	}

	public static function getMediaByName($filename)
	{
		return self::getMediaByFileName($filename);
	}

	/**
	 * @example OOMedia::getMediaByExtension('css');
	 * @example OOMedia::getMediaByExtension('gif');
	 */
	public static function getMediaByExtension($extension)
	{
		$query  = 'SELECT file_id FROM '.self::_getTableName().' WHERE SUBSTRING(filename, LOCATE(".", filename) + 1) = "'.$extension.'"';
		$sql    = new rex_sql();
		$result = $sql->getArray($query);
		$media  = array();

		if (is_array($result)) {
			foreach ($result as $row) {
				$media[] = self::getMediaById($row['file_id']);
			}
		}

		return $media;
	}

	/**
	 * @return OOMedia
	 */
	public static function getMediaByFileName($name)
	{
		$query  = 'SELECT file_id FROM '.self::_getTableName().' WHERE filename = "'.$name.'" LIMIT 1';
		$sql    = new rex_sql();
		$result = $sql->getArray($query);

		if (is_array($result)) {
			foreach ($result as $line) {
				return self::getMediaById($line['file_id']);
			}
		}

		return null;
	}

	public function getCategory()
	{
		if ($this->cat === null) {
			$this->cat = OOMediaCategory::getCategoryById($this->getCategoryId());
		}

		return $this->cat;
	}

	public function getId()           { return $this->id;         }
	public function getCategoryName() { return $this->cat_name;   }
	public function getCategoryId()   { return $this->cat_id;     }
	public function getParentId()     { return $this->parent_id;  }
	public function getTitle()        { return $this->title;      }
	public function getFileName()     { return $this->name;       }
	public function getOrgFileName()  { return $this->orgname;    }
	public function getWidth()        { return $this->width;      }
	public function getHeight()       { return $this->height;     }
	public function getType()         { return $this->type;       }
	public function getSize()         { return $this->size;       }
	public function getUpdateUser()   { return $this->updateuser; }
	public function getCreateUser()   { return $this->createuser; }

	public function hasParent()
	{
		return $this->getParentId() != 0;
	}

	/**
	 * @deprecated 12.10.2007
	 */
	public function getDescription()
	{
		return $this->getValue('med_description');
	}

	/**
	 * @deprecated 12.10.2007
	 */
	public function getCopyright()
	{
		return $this->getValue('med_copyright');
	}

	public function getPath()
	{
		global $REX;
		return $REX['MEDIAFOLDER'];
	}

	public function getFullPath()
	{
		return $this->getPath().'/'.$this->getFileName();
	}

	public function getFormattedSize()
	{
		return self::_getFormattedSize($this->getSize());
	}

	public static function _getFormattedSize($size)
	{
		return sly_Util_String::formatFilesize($size);
	}

	/**
	 * Formats a datestamp with the given format.
	 *
	 * If format is <code>null</code> the datestamp is returned.
	 *
	 * If format is <code>''</code> the datestamp is formated
	 * with the default <code>dateformat</code> (lang-files).
	 */
	protected static function _getDate($date, $format = null)
	{
		if ($format !== null) {
			if ($format == '') {
				// TODO Im Frontend gibts kein I18N
				// global $I18N;
				//$format = $I18N->msg('dateformat');
				$format = '%a %d. %B %Y';
			}

			return strftime($format, $date);
		}

		return $date;
	}

	/**
	 * @see #_getDate
	 */
	public function getUpdateDate($format = null)
	{
		if ($format == null) return $this->updatedate;
		return self::_getDate($this->updatedate, $format);
	}

	/**
	 * @see #_getDate
	 */
	public function getCreateDate($format = null)
	{
		if ($format == null) return $this->createdate;
		return self::_getDate($this->createdate, $format);
	}

	public function toImage($params = array())
	{
		global $REX;

		if (!is_array($params)) {
			$params = array();
		}

		$path = $REX['HTDOCS_PATH'];

		if (isset($params['path'])) {
			$path = $params['path'];
			unset($params['path']);
		}

		// Ist das Medium ein Bild?
		// Falls nicht, verwenden wir von hier ab das Dummy-Icon.

		if (!$this->isImage()) {
			$path = 'media/';
			$file = 'file_dummy.gif';

			if (self::$dummeFileSize === null) {
				self::$dummeFileSize = getimagesize($path.$file);
			}

			if (self::$dummeFileSize) {
				$params['width']  = self::$dummeFileSize[0];
				$params['height'] = self::$dummeFileSize[1];
			}
		}
		else {
			$resize = false;

			// ResizeModus festlegen
			if (isset ($params['resize']) && $params['resize']) {
				unset ($params['resize']);

				$service = sly_Service_Factory::getService('AddOn');

				// Resize Addon installiert?
				if ($service->isAvailable('image_resize')) {
					$resize = true;

					if (isset($params['width'])) {
						$resizeMode  = 'w';
						$resizeParam = $params['width'];
						unset($params['width']);
					}
					elseif (isset($params['height'])) {
						$resizeMode  = 'h';
						$resizeParam = $params['height'];
						unset($params['height']);
					}
					elseif (isset($params['crop'])) {
						$resizeMode  = 'c';
						$resizeParam = $params['crop'];
						unset($params['crop']);
					}
					else {
						$resizeMode  = 'a';
						$resizeParam = 100;
					}

					// evtl. Größeneinheiten entfernen
					$resizeParam = str_replace(array('px', 'pt',  '%', 'em'), '', $resizeParam);
				}
			}

			// Bild resizen?
			if ($resize) {
				$file = $REX['FRONTEND_FILE'].'?rex_resize='.$resizeParam.$resizeMode.'__'.$this->getFileName();
			}
			else {
				// Bild 1:1 anzeigen
				$path .= 'files/';
				$file = $this->getFileName();
			}
		}

		$title = $this->getTitle();

		// Alternativtext hinzufügen

		if (!isset($params['alt']) && $title != '') {
			$params['alt'] = $title;
		}

		// Titel hinzufügen

		if (!isset($params['title']) && $title != '') {
			$params['title'] = $title;
		}

		$params['src'] = $path.$file;
		return sprintf('<img %s />', sly_Util_HTML::buildAttributeString($params));
	}

	public function toLink($attributes = '')
	{
		return sprintf('<a href="%s" title="%s"%s>%s</a>', $this->getFullPath(), $this->getDescription(), $attributes, $this->getFileName());
	}

	public function toIcon($attributes = array())
	{
		if (!isset($attributes['alt']))   $attributes['alt']   = '"'.$this->getExtension().'"-Symbol';
		if (!isset($attributes['title'])) $attributes['title'] = $attributes['alt'];
		if (!isset($attributes['style'])) $attributes['style'] = 'width:44px;height:38px';

		$attributes['src'] = $this->getIcon();
		return sprintf('<img %s />', sly_Util_HTML::buildAttributeString($attributes));
	}

	public static function isValid($media)
	{
		return $media instanceof self;
	}

	public function isImage()
	{
		return self::_isImage($this->getFileName());
	}

	public static function _isImage($filename)
	{
		static $imageExtensions = array('gif', 'jpeg', 'jpg', 'png', 'bmp');
		return in_array(self::_getExtension($filename), $imageExtensions);
	}

	public function isInUse()
	{
		global $REX;

		$sql      = new rex_sql();
		$filename = addslashes($this->getFileName());
		$prefix   = $REX['DATABASE']['TABLE_PREFIX'];
		$query    =
			'SELECT s.article_id, s.clang FROM '.$prefix.'slice_value sv, '.$prefix.'article_slice s, '.$prefix.'article a '.
			'WHERE sv.slice_id = s.slice_id AND a.id = s.article_id AND a.clang = s.clang AND ('.
			'(type = "'.rex_var_media::MEDIALIST.'" AND (value LIKE "'.$filename.',%" OR value LIKE "%,'.$filename.',%" OR value LIKE "%,'.$filename.'")) OR '.
			'(type <> "'.rex_var_media::MEDIALIST.'" AND value LIKE "%'.$filename.'%")'.
			') GROUP BY s.article_id, s.clang';

		$res    = $sql->getArray($query);
		$usages = array();

		foreach ($res as $row) {
			$article = OOArticle::getArticleById($row['article_id'], $row['clang']);

			$usages[] = array(
				'title' => $article->getName(),
				'type'  => 'rex-article',
				'id'    => (int) $row['article_id'],
				'clang' => (int) $row['clang'],
				'link'  => 'index.php?page=content&article_id='.$row['article_id'].'&mode=edit&clang='.$row['clang']
			);
		}

		$usages = rex_register_extension_point('SLY_OOMEDIA_IS_IN_USE', $usages, array(
			'filename' => $this->getFileName(),
			'media'    => $this
		));

		return empty($usages) ? false : $usages;
	}

	public function toHTML($attributes = '')
	{
		$file     = $this->getFullPath();
		$filetype = $this->getExtension();

		switch ($filetype) {
			case 'jpg':
			case 'jpeg':
			case 'png':
			case 'gif':
			case 'bmp':
				return $this->toImage($attributes);

			case 'js':
				return sprintf('<script type="text/javascript" src="%s"%s></script>', $file, $attributes);

			case 'css':
				return sprintf('<link href="%s" rel="stylesheet" type="text/css"%s>', $file, $attributes);

			default:
				return 'No html-equivalent available for type "'.$filetype.'"';
		}
	}

	public function toString()
	{
		return 'OOMedia, "'.$this->getId().'", "'.$this->getFileName().'"'."<br/>\n";
	}

	public function __toString()
	{
		return $this->toString();
	}

	// new functions by vscope

	public function getExtension()
	{
		return self::_getExtension($this->_name);
	}

	public static function _getExtension($filename)
	{
		return substr(strrchr($filename, '.'), 1);
	}

	public function getIcon($useDefaultIcon = true)
	{
		global $REX;

		$ext    = $this->getExtension();
		$folder = $REX['HTDOCS_PATH'].'redaxo/media/';
		$icon   = $folder.'mime-'.$ext.'.gif';

		// Dateityp für den kein Icon vorhanden ist

		if (!file_exists($icon)) {
			$icon = $folder.($useDefaultIcon ? 'mime-default.gif' : 'mime-error.gif');
		}

		return $icon;
	}

	public function save()
	{
		$sql = new rex_sql();
		$sql->setTable($this->_getTableName());
		$sql->setValue('re_file_id', $this->getParentId());
		$sql->setValue('category_id', $this->getCategoryId());
		$sql->setValue('filetype', $this->getType());
		$sql->setValue('filename', $this->getFileName());
		$sql->setValue('originalname', $this->getOrgFileName());
		$sql->setValue('filesize', $this->getSize());
		$sql->setValue('width', $this->getWidth());
		$sql->setValue('height', $this->getHeight());
		$sql->setValue('title', $this->getTitle());

		if ($this->getId() !== null) {
			$sql->addGlobalUpdateFields();
			$sql->setWhere('file_id = '.$this->getId().' LIMIT 1');
			return $sql->update();
		}
		else {
			$sql->addGlobalCreateFields();
			return $sql->insert();
		}
	}

	public function delete($filename = null)
	{
		global $REX;

		if ($filename != null) {
			$OOMed = OOMedia::getMediaByFileName($filename);
			if ($OOMed) return $OOMed->delete();
		}
		else {
			$qry = 'DELETE FROM '.$this->_getTableName().' WHERE file_id = '.$this->getId().' LIMIT 1';
			$sql = new rex_sql();
			$sql->setQuery($qry);

			if (self::fileExists($this->getFileName())) {
				unlink($REX['MEDIAFOLDER'].DIRECTORY_SEPARATOR.$this->getFileName());
			}

			return $sql->getError();
		}

		return false;
	}

	public static function fileExists($filename = null)
	{
		global $REX;
		return file_exists($REX['MEDIAFOLDER'].DIRECTORY_SEPARATOR.$filename);
	}

	// allowed filetypes

	public static function getDocTypes()
	{
		static $docTypes = array(
			'bmp', 'css', 'doc', 'docx', 'eps', 'gif', 'gz', 'jpg', 'mov', 'mp3',
			'ogg', 'pdf', 'png', 'ppt', 'pptx','pps', 'ppsx', 'rar', 'rtf', 'swf',
			'tar', 'tif', 'txt', 'wma', 'xls', 'xlsx', 'zip'
		);

		return $docTypes;
	}

	public static function isDocType($type)
	{
		return in_array($type, self::getDocTypes());
	}

	// allowed image upload types

	public static function getImageTypes()
	{
		static $imageTypes = array(
			'image/gif',
			'image/jpg',
			'image/jpeg',
			'image/png',
			'image/x-png',
			'image/pjpeg',
			'image/bmp'
		);

		return $imageTypes;
	}

	public static function isImageType($type)
	{
		return in_array($type, self::getImageTypes());
	}

	public static function compareImageTypes($type1, $type2)
	{
		static $jpg = array(
			'image/jpg',
			'image/jpeg',
			'image/pjpeg'
		);

		return in_array($type1, $jpg) && in_array($type2, $jpg);
	}

	public function hasValue($value)
	{
		if ($value[0] == '_') $value = substr($value, 1);
		return isset($this->$value);
	}

	public function getValue($value)
	{
		if ($value[0] == '_') $value = substr($value, 1);

		// damit alte rex_article felder wie copyright, description
		// noch funktionieren

		if ($this->hasValue($value)) {
			return $this->$value;
		}
		elseif ($this->hasValue('med_'.$value)) {
			return $this->getValue('med_'.$value);
		}

		return null;
	}
}
