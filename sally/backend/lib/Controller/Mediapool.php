<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Mediapool extends sly_Controller_Backend implements sly_Controller_Interface {
	protected $warning;
	protected $info;
	protected $category;
	protected $selectBox;
	protected $categories;
	protected $action;

	private $init = false;

	protected function init($action = '') {
		if ($this->init) return;
		$this->init = true;

		// load our i18n stuff
		sly_Core::getI18N()->appendFile(SLY_SALLYFOLDER.'/backend/lang/pages/mediapool/');

		$this->info       = sly_request('info', 'string');
		$this->warning    = sly_request('warning', 'string');
		$this->args       = sly_requestArray('args', 'string');
		$this->categories = array();
		$this->action     = $action;

		// init category filter
		if (isset($this->args['categories'])) {
			$cats             = array_map('intval', explode('|', $this->args['categories']));
			$this->categories = array_unique($cats);
		}

		$this->getCurrentCategory();

		// build navigation

		$layout = sly_Core::getLayout();
		$nav    = $layout->getNavigation();
		$page   = $nav->find('mediapool');
		$cur    = sly_Core::getCurrentControllerName();

		$subline = array(
			array('mediapool',        t('media_list')),
			array('mediapool_upload', t('upload_file'))
		);

		if ($this->isMediaAdmin()) {
			$subline[] = array('mediapool_structure', t('categories'));
			$subline[] = array('mediapool_sync',      t('sync_files'));
		}

		foreach ($subline as $item) {
			$sp = $page->addSubpage($item[0], $item[1]);

			if (!empty($this->args)) {
				$sp->setExtraParams(array('args' => $this->args));

				// ignore the extra params when detecting the current page
				if ($cur === $item[0]) $sp->forceStatus(true);
			}
		}

		$page = sly_Core::dispatcher()->filter('SLY_MEDIAPOOL_MENU', $page);

		$layout->showNavigation(false);
		$layout->pageHeader(t('media_list'), $page);
		$layout->setBodyAttr('class', 'sly-popup sly-mediapool');

		print $this->render('mediapool/javascript.phtml');
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

			// respect category filter
			if (!empty($this->categories) && !in_array($category, $this->categories)) {
				$category = reset($this->categories);
			}

			$category = $service->findById($category);
			$category = $category ? $category->getId() : 0;

			sly_util_Session::set('media[rex_file_category]', $category);
			$this->category = $category;
		}

		return $this->category;
	}

	protected function getOpenerLink(sly_Model_Medium $file) {
		$callback = sly_request('callback', 'string');
		$link     = '';

		if (!empty($callback)) {
			$filename = $file->getFilename();
			$title    = $file->getTitle();
			$link     = '<a href="#" data-filename="'.sly_html($filename).'" data-title="'.sly_html($title).'">'.t('apply_file').'</a>';
		}

		return $link;
	}

	protected function getFiles() {
		$cat   = $this->getCurrentCategory();
		$where = 'f.category_id = '.$cat;
		$where = sly_Core::dispatcher()->filter('SLY_MEDIA_LIST_QUERY', $where, array('category_id' => $cat));
		$where = '('.$where.')';

		if (isset($this->args['types'])) {
			$types = explode('|', preg_replace('#[^a-z0-9/+.-|]#i', '', $this->args['types']));

			if (!empty($types)) {
				$where .= ' AND filetype IN ("'.implode('","', $types).'")';
			}
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

	public function indexAction() {
		$this->init('index');

		$files = $this->getFiles();

		print $this->render('mediapool/toolbar.phtml');

		if (empty($files)) {
			print sly_Helper_Message::info(t('no_media_found'));
		}
		else {
			print $this->render('mediapool/index.phtml', compact('files'));
		}
	}

	public function batchAction() {
		$this->init('batch');

		if (!empty($_POST['delete'])) {
			return $this->deleteAction();
		}

		return $this->moveAction();
	}

	public function moveAction() {
		$this->init('move');

		if (!$this->isMediaAdmin()) {
			return $this->indexAction();
		}

		$media = sly_postArray('selectedmedia', 'int', array());

		if (empty($media)) {
			$this->warning = t('no_files_selected');
			return $this->indexAction();
		}

		$service = sly_Service_Factory::getMediumService();

		foreach ($media as $mediumID) {
			$medium = sly_Util_Medium::findById($mediumID);
			if (!$medium) continue;

			$medium->setCategoryId($this->category);
			$service->update($medium);
		}

		// refresh asset cache in case permissions have changed
		$this->revalidate();

		$this->info = t('selected_files_moved');
		$this->indexAction();
	}

	public function deleteAction() {
		$this->init('delete');

		if (!$this->isMediaAdmin()) {
			return $this->indexAction();
		}

		$files = sly_postArray('selectedmedia', 'int', array());

		if (empty($files)) {
			$this->warning = t('no_files_selected');
			return $this->indexAction();
		}

		foreach ($files as $fileID) {
			$media = sly_Util_Medium::findById($fileID);

			if ($media) {
				$retval = $this->deleteMedia($media);
			}
			else {
				$this->warning[] = t('file_not_found', $fileID);
			}
		}

		$this->indexAction();
	}

	protected function deleteMedia(sly_Model_Medium $medium) {
		$filename = $medium->getFileName();
		$user     = sly_Util_User::getCurrentUser();

		// TODO: Is $this->isMediaAdmin() redundant? The user rights are already checked in delete()...

		if ($this->isMediaAdmin() || $user->hasRight('mediacategory', 'access', $medium->getCategoryId())) {
			$usages = $this->isInUse($medium);

			if ($usages === false) {
				$service = sly_Service_Factory::getMediumService();

				try {
					$service->delete($medium->getId());
					$this->revalidate();
					$this->info[] = t('medium_deleted');
				}
				catch (sly_Exception $e) {
					$this->warning[] = $e->getMessage();
				}
			}
			else {
				$tmp   = array();
				$tmp[] = t('file_delete_error_1', $filename).'. '.t('file_delete_error_2').'<br />';
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

	public function checkPermission($action) {
		$user = sly_Util_User::getCurrentUser();
		if (is_null($user)) return false;

		return $user->hasStructureRight() || $user->hasRight('pages', 'mediapool');
	}

	protected function isMediaAdmin() {
		$user = sly_Util_User::getCurrentUser();
		return $user->isAdmin() || $user->hasRight('mediacategory', 'access', sly_Authorisation_ListProvider::ALL);
	}

	protected function canAccessFile(sly_Model_Medium $medium) {
		return $this->canAccessCategory($medium->getCategoryId());
	}

	protected function canAccessCategory($cat) {
		$user = sly_Util_User::getCurrentUser();
		return $this->isMediaAdmin() || $user->hasRight('mediacategory', 'access', intval($cat));
	}

	protected function getCategorySelect() {
		$user = sly_Util_User::getCurrentUser();

		if ($this->selectBox === null) {
			$this->selectBox = sly_Form_Helper::getMediaCategorySelect('rex_file_category', null, $user);
			$this->selectBox->setLabel(t('categories'));
			$this->selectBox->setMultiple(false);
			$this->selectBox->setAttribute('value', $this->getCurrentCategory());

			// filter categories if args[categories] is set
			if (isset($this->args['categories'])) {
				$cats = array_map('intval', explode('|', $this->args['categories']));
				$cats = array_unique($cats);

				if (!empty($cats)) {
					$values = array_keys($this->selectBox->getValues());

					foreach ($values as $catID) {
						if (!in_array($catID, $cats)) $this->selectBox->removeValue($catID);
					}
				}
			}
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
				'type'  => 'sly-article',
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

	protected function revalidate() {
		// re-validate asset cache
		sly_Service_Factory::getAssetService()->validateCache();
	}
}
