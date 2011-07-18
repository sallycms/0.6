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

	protected function init() {
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

	protected function getOpenerLink(sly_Model_Medium $file) {
		$field    = $this->opener;
		$link     = '';
		$filename = $file->getFilename();
		$uname    = urlencode($filename);

		if ($field === 'TINYIMG') {
			if ($this->isImage($file)) {
				$title = sly_html($file->getTitle());
				$link  = '<a href="javascript:insertImage(\''.$uname.'\',\''.$title.'\')">'.$this->t('image_get').'</a> | ';
			}
		}
		elseif ($field === 'TINY') {
			$link = '<a href="javascript:insertLink(\''.$uname.'\')">'.$this->t('link_get').'</a>';
		}
		elseif ($field !== '') {
			$link = '<a href="javascript:selectMedia(\''.$uname.'\')">'.$this->t('file_get').'</a>';

			if (substr($field, 0, 14) === 'REX_MEDIALIST_') {
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
			$files[$row['id']] = sly_Util_Medium::findById($row['id']);
		}

		return $files;
	}

	protected function index() {
		print $this->render('mediapool/toolbar.phtml');
		print $this->render('mediapool/index.phtml');
	}

	protected function batch() {
		if (!empty($_POST['delete'])) {
			return $this->delete();
		}

		return $this->move();
	}

	protected function move() {
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

		// clear system cache
		$cache = sly_Core::cache();

		foreach ($files as $fileID) {
			$cache->delete('sly.medium', $fileID);
		}

		// refresh asset cache in case permissions have changed
		sly_Service_Factory::getAssetService()->validateCache();

		$this->info = $this->t('selectedmedia_moved');
		$this->index();
	}

	protected function delete() {
		if (!$this->isMediaAdmin()) {
			return $this->index();
		}

		$files = sly_postArray('selectedmedia', 'int', array());

		if (empty($files)) {
			$this->warning = $this->t('selectedmedia_error');
			return $this->index();
		}

		foreach ($files as $fileID) {
			$media = sly_Util_Medium::findById($fileID);

			if ($media) {
				$retval = $this->deleteMedia($media);
			}
			else {
				$this->warning[] = $this->t('file_not_found');
			}
		}

		$this->index();
	}

	protected function deleteMedia(sly_Model_Medium $medium) {
		$filename = $medium->getFileName();
		$user     = sly_Util_User::getCurrentUser();

		// TODO: Is $this->isMediaAdmin() redundant? The user rights are already checked in delete()...

		if ($this->isMediaAdmin() || $user->hasRight('media['.$medium->getCategoryId().']')) {
			$usages = $this->isInUse($medium);

			if ($usages === false) {
				$service = sly_Service_Factory::getMediumService();

				try {
					$service->delete($medium);

					// re-validate asset cache
					$service = sly_Service_Factory::getAssetService();
					$service->validateCache();

					$this->info[] = $this->t('file_deleted');
				}
				catch (sly_Exception $e) {
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

	protected function checkPermission() {
		$user = sly_Util_User::getCurrentUser();
		return !empty($user);
	}

	protected function isMediaAdmin() {
		$user = sly_Util_User::getCurrentUser();
		return $user->isAdmin() || $user->hasRight('media[0]');
	}

	protected function canAccessFile(sly_Model_Medium $medium) {
		return $this->canAccessCategory($medium->getCategoryId());
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

	protected function isDocType(sly_Model_Medium $medium) {
		static $docTypes = array(
			'bmp', 'css', 'doc', 'docx', 'eps', 'gif', 'gz', 'jpg', 'mov', 'mp3',
			'ogg', 'pdf', 'png', 'ppt', 'pptx','pps', 'ppsx', 'rar', 'rtf', 'swf',
			'tar', 'tif', 'txt', 'wma', 'xls', 'xlsx', 'zip'
		);

		return in_array($medium->getExtension(), $docTypes);
	}

	protected function isImage(sly_Model_Medium $medium) {
		static $exts = array('gif', 'jpeg', 'jpg', 'png', 'bmp', 'tif', 'tiff', 'webp');
		return in_array($medium->getExtension(), $exts);
	}

	protected function isInUse(sly_Model_Medium $medium) {
		$sql      = sly_DB_Persistence::getInstance();
		$filename = addslashes($medium->getFilename());
		$prefix   = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
		$query    =
			'SELECT s.article_id, s.clang FROM '.$prefix.'slice_value sv, '.$prefix.'article_slice s, '.$prefix.'article a '.
			'WHERE sv.slice_id = s.slice_id AND a.id = s.article_id AND a.clang = s.clang AND ('.
			'(sv.type = "'.rex_var_media::MEDIALIST.'" AND (value LIKE "'.$filename.',%" OR value LIKE "%,'.$filename.',%" OR value LIKE "%,'.$filename.'")) OR '.
			'(sv.type <> "'.rex_var_media::MEDIALIST.'" AND value LIKE "%'.$filename.'%")'.
			') GROUP BY s.article_id, s.clang';

		$res    = array();
		$usages = array();

		$sql->query($query);
		foreach ($sql as $row) $res[] = $row;

		foreach ($res as $row) {
			$article = sly_Util_Article::findById($row['article_id'], $row['clang']);

			$usages[] = array(
				'title' => $article->getName(),
				'type'  => 'rex-article',
				'id'    => (int) $row['article_id'],
				'clang' => (int) $row['clang'],
				'link'  => 'index.php?page=content&article_id='.$row['article_id'].'&mode=edit&clang='.$row['clang']
			);
		}

		$usages = sly_Core::dispatcher()->filter('SLY_OOMEDIA_IS_IN_USE', $usages, array(
			'filename' => $medium->getFilename(),
			'media'    => $medium
		));

		return empty($usages) ? false : $usages;
	}
}
