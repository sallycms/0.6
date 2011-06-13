<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Mediapool extends sly_Controller_Backend {
	protected $warning;
	protected $info;
	protected $category;
	protected $selectBox;

	public function init() {
		// load our i18n stuff
		sly_Core::getI18N()->appendFile(SLY_SALLYFOLDER.'/backend/lang/pages/mediapool/');

		$this->info    = sly_request('info', 'string');
		$this->warning = sly_request('warning', 'string');
		$this->args    = sly_requestArray('args', 'string');

		$this->getCurrentCategory();
		$this->initOpener();

		// Header

		$subline = array(
			array('',       $this->t('file_list')),
			array('upload', $this->t('file_insert'))
		);

		if ($this->isMediaAdmin()) {
			$subline[] = array('structure', $this->t('cat_list'));
			$subline[] = array('sync',      $this->t('sync_files'));
		}

		// ArgUrl an Menü anhängen

		$args = '&amp;'.$this->getArgumentString();

		foreach ($subline as &$item) {
			$item[2] = '';
			$item[3] = $args;
		}

		$subline = sly_Core::dispatcher()->filter('PAGE_MEDIAPOOL_MENU', $subline);
		$layout  = sly_Core::getLayout();

		$layout->showNavigation(false);
		$layout->pageHeader($this->t('media'), $subline);
	}

	protected function t($args) {
		$args    = func_get_args();
		$args[0] = 'pool_'.$args[0];
		return call_user_func_array('t', $args);
	}

	protected function getArgumentString($separator = '&amp;') {
		$args = array();

		foreach ($this->args as $name => $value) {
			$args['args['.$name.']'] = $value;
		}

		return http_build_query($args, '', $separator);
	}

	protected function getCurrentCategory() {
		if ($this->category === null) {
			$category = sly_request('rex_file_category', 'int', -1);
			$service  = sly_Service_Factory::getMediaCategoryService();

			if ($category == -1) {
				$category = sly_Util_Session::get('media[rex_file_category]', 'int');
			}

			$category = $service->findById($category);
			$category = $category ? $category->getId() : 0;

			sly_util_Session::set('media[rex_file_category]', $category);
			$this->category = $category;
		}

		return $this->category;
	}

	protected function initOpener() {
		$this->opener = sly_request('opener_input_field', 'string', sly_Util_Session::get('media[opener_input_field]', 'string'));
		sly_util_Session::set('media[opener_input_field]', $this->opener);
	}

	protected function getOpenerLink(/* OOMedia | sly_Model_Medium */ $file) {
		$field    = $this->opener;
		$link     = '';
		$title    = sly_html($file->getTitle());
		$filename = $file->getFilename();
		$uname    = urlencode($filename);

		if ($field == 'TINYIMG') {
			if (OOMedia::_isImage($filename)) {
				$link = '<a href="javascript:insertImage(\''.$uname.'\',\''.$title.'\')">'.$this->t('image_get').'</a> | ';
			}
		}
		elseif ($field == 'TINY') {
			$link = '<a href="javascript:insertLink(\''.$uname.'\')">'.$this->t('link_get').'</a>';
		}
		elseif ($field != '') {
			$link = '<a href="javascript:selectMedia(\''.$uname.'\')">'.$this->t('file_get').'</a>';

			if (substr($field, 0, 14) == 'REX_MEDIALIST_') {
				$link = '<a href="javascript:selectMedialist(\''.$uname.'\')">'.$this->t('file_get').'</a>';
			}
		}

		return $link;
	}

	protected function getFiles() {
		$cat   = $this->getCurrentCategory();
		$where = 'f.category_id = '.$cat;
		$where = sly_Core::dispatcher()->filter('SLY_MEDIA_LIST_QUERY', $where, array('category_id' => $cat));
		$where = '('.$where.')';

		if (isset($this->args['types'])) {
			$types = explode(',', preg_replace('#[^a-z0-9,]#i', '', $this->args['types']));

			foreach ($types as $i => $type) {
				$types[$i] = 'f.filename LIKE "%.'.$type.'"';
			}

			$where .= ' AND ('.implode(' OR ', $types).')';
		}

		$db     = sly_DB_Persistence::getInstance();
		$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
		$query  = 'SELECT f.id FROM '.$prefix.'file f LEFT JOIN '.$prefix.'file_category c ON f.category_id = c.id WHERE '.$where.' ORDER BY f.updatedate DESC';
		$files  = array();

		$db->query($query);

		foreach ($db as $row) {
			$files[$row['id']] = OOMedia::getMediaById($row['id']);
		}

		return $files;
	}

	public function index() {
		print $this->render('mediapool/toolbar.phtml');
		print $this->render('mediapool/index.phtml');
	}

	public function batch() {
		if (!empty($_POST['delete'])) {
			return $this->delete();
		}

		return $this->move();
	}

	public function move() {
		if (!$this->isMediaAdmin()) {
			return $this->index();
		}

		$files = sly_postArray('selectedmedia', 'int', array());

		if (empty($files)) {
			$this->warning = $this->t('selectedmedia_error');
			return $this->index();
		}

		$user = sly_Util_User::getCurrentUser();
		$db   = sly_DB_Persistence::getInstance();
		$what = array('category_id' => $this->category, 'updateuser' => $user->getLogin(), 'updatedate' => time());
		$db->update('file', $what, array('id' => $files));

		$this->info = $this->t('selectedmedia_moved');
		$this->index();
	}

	public function delete() {
		if (!$this->isMediaAdmin()) {
			return $this->index();
		}

		$files = sly_postArray('selectedmedia', 'int', array());

		if (empty($files)) {
			$this->warning = $this->t('selectedmedia_error');
			return $this->index();
		}

		foreach ($files as $fileID) {
			$media = OOMedia::getMediaById($fileID);

			if ($media) {
				$retval = $this->deleteMedia($media);
			}
			else {
				$this->warning[] = $this->t('file_not_found');
			}
		}

		$this->index();
	}

	protected function deleteMedia(OOMedia $media) {
		$filename = $media->getFileName();
		$user     = sly_Util_User::getCurrentUser();

		// TODO: Is $this->isMediaAdmin() redundant? The user rights are already checked in delete()...

		if ($this->isMediaAdmin() || $user->hasRight('media['.$media->getCategoryId().']')) {
			$usages = $media->isInUse();

			if ($usages === false) {
				if ($media->delete() !== false) {
					// re-validate asset cache
					$service = sly_Service_Factory::getAssetService();
					$service->validateCache();

					// notify system
					sly_Core::dispatcher()->notify('SLY_MEDIA_DELETED', $media);
					$this->info[] = $this->t('file_deleted');
				}
				else {
					$this->warning[] = $this->t('file_delete_error_1', $filename);
				}
			}
			else {
				$tmp   = array();
				$tmp[] = $this->t('file_delete_error_1', $filename).'. '.$this->t('file_delete_error_2').':<br />';
				$tmp[] = '<ul>';

				foreach ($usages as $usage) {
					if (!empty($usage['link'])) {
						$tmp[] = '<li><a href="javascript:openPage(\''.sly_html($usage['link']).'\')">'.sly_html($usage['title']).'</a></li>';
					}
					else {
						$tmp[] = '<li>'.sly_html($usage['title']).'</li>';
					}
				}

				$tmp[] = '</ul>';
				$this->warning[] = implode("\n", $tmp);
			}
		}
		else {
			$this->warning[] = t('no_permission');
		}
	}

	public function checkPermission() {
		$user = sly_Util_User::getCurrentUser();
		return !empty($user);
	}

	protected function isMediaAdmin() {
		$user = sly_Util_User::getCurrentUser();
		return $user->hasRight('admin[]') || $user->hasRight('media[0]');
	}

	protected function canAccessFile(OOMedia $file) {
		return $this->canAccessCategory($file->getCategoryId());
	}

	protected function canAccessCategory($cat) {
		$user = sly_Util_User::getCurrentUser();
		return $this->isMediaAdmin() || $user->hasRight('media['.intval($cat).']');
	}

	protected function getCategorySelect() {
		$user = sly_Util_User::getCurrentUser();

		if ($this->selectBox === null) {
			$this->selectBox = sly_Form_Helper::getMediaCategorySelect('rex_file_category', null, $user);
			$this->selectBox->setLabel($this->t('kats'));
			$this->selectBox->setMultiple(false);
			$this->selectBox->setAttribute('value', $this->getCurrentCategory());
		}

		return $this->selectBox;
	}

	protected function createFileObject($filename, $type, $title, $category, $origFilename = null) {
		$size = getimagesize($filename);

		// finfo:             PHP >= 5.3, PECL fileinfo
		// mime_content_type: PHP >= 4.3 (deprecated)

		if (empty($type)) {
			// if it's an image, we know the type
			if (isset($size['mime'])) {
				$type = $size['mime'];
			}

			// or else try the new, recommended way
			elseif (function_exists('finfo_file')) {
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$type  = finfo_file($finfo, $filename);
			}

			// argh, let's see if this old one exists
			elseif (function_exists('mime_content_type')) {
				$type = mime_content_type($filename);
			}

			// fallback to a generic type
			else {
				$type = 'application/octet-stream';
			}
		}

		$file = new sly_Model_Medium();
		$file->setFiletype($type);
		$file->setTitle($title);
		$file->setOriginalName(basename($origFilename === null ? $filename : $origFilename));
		$file->setFilename(basename($filename));
		$file->setFilesize(filesize($filename));
		$file->setCategoryId((int) $category);
		$file->setRevision(0); // totally useless...
		$file->setReFileId(0); // even more useless
		$file->setCreateColumns();

		if ($size) {
			$file->setWidth($size[0]);
			$file->setHeight($size[1]);
		}

		return $file;
	}

	protected function createFilename($filename, $doSubindexing = true) {
		$filename    = $this->correctEncoding($filename);
		$newFilename = strtolower($filename);
		$newFilename = str_replace(array('ä','ö', 'ü', 'ß'), array('ae', 'oe', 'ue', 'ss'), $newFilename);
		$newFilename = preg_replace('#[^a-z0-9.+-]#i', '_', $newFilename);
		$lastDotPos  = strrpos($newFilename, '.');
		$fileLength  = strlen($newFilename);

		// split up extension

		if ($lastDotPos !== false) {
			$newName = substr($newFilename, 0, $lastDotPos);
			$newExt  = substr($newFilename, $lastDotPos);
		}
		else {
			$newName = $newFilename;
			$newExt  = '';
		}

		// check for disallowed extensions (broken by design...)

		$blocked = sly_Core::config()->get('MEDIAPOOL/BLOCKED_EXTENSIONS');

		if (in_array($newExt, $blocked)) {
			$newName .= $newExt;
			$newExt   = '.txt';
		}

		$newFilename = $newName.$newExt;

		if ($doSubindexing) {
			// increment filename suffix until an unique one was found

			if (file_exists(SLY_MEDIAFOLDER.'/'.$newFilename)) {
				for ($cnt = 1; file_exists(SLY_MEDIAFOLDER.'/'.$newName.'_'.$cnt.$newExt); ++$cnt);
				$newFilename = $newName.'_'.$cnt.$newExt;
			}
		}

		return $newFilename;
	}

	protected function getDimensions($width, $height, $maxWidth, $maxHeight) {
		if ($width > $maxWidth) {
			$factor  = (float) $maxWidth / $width;
			$width   = $maxWidth;
			$height *= $factor;
		}

		if ($height > $maxHeight) {
			$factor  = (float) $maxHeight / $height;
			$height  = $maxHeight;
			$width  *= $factor;
		}

		return array(ceil($width), ceil($height));
	}

	protected function correctEncoding($filename) {
		$enc = mb_detect_encoding($filename, 'Windows-1252, ISO-8859-1, ISO-8859-2, UTF-8');
		if ($enc != 'UTF-8') $filename = mb_convert_encoding($filename, 'UTF-8', $enc);
		return $filename;
	}
}
