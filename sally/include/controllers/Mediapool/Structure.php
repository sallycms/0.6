<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Mediapool_Structure extends sly_Controller_Mediapool {
	public function index() {
		$this->render('views/mediapool/structure.phtml');
	}

	public function add() {
		if (!empty($_POST)) {
			$service  = sly_Service_Factory::getService('Media_Category');
			$category = new sly_Model_Media_Category();
			$name     = sly_post('catname', 'string');

			$category->setName($name);
			$category->setParentId(sly_post('cat_id', 'string'));
			$category->setPath(sly_post('catpath', 'string'));
			$category->setRevision(0);
			$category->setCreateColumns();

			$service->save($category);

			$this->info   = $this->t('kat_saved', $name);
			$this->action = '';
		}

		$this->index();
	}

	public function edit() {
		if (!empty($_POST)) {
			$editID   = sly_request('edit_id', 'int');
			$service  = sly_Service_Factory::getService('Media_Category');
			$category = $service->findById($editID);

			if ($category) {
				$name = sly_post('catname', 'string');

				$category->setName($name);
				$category->setUpdateColumns();
				$service->save($category);

				$this->info   = $this->t('kat_updated', $name);
				$this->action = '';
			}
		}

		$this->index();
	}

	public function delete() {
		$editID   = sly_request('edit_id', 'int');
		$service  = sly_Service_Factory::getService('Media_Category');
		$category = $service->findById($editID);

		if ($category) {
			// we can only delete empty categories with no children

			$db       = sly_DB_Persistence::getInstance();
			$files    = $db->magicFetch('file', 'COUNT(*)', array('category_id' => $editID));
			$children = $db->magicFetch('file_category', 'COUNT(*)', array('re_id' => $editID));

			if ($files == 0 && $children == 0) {
				$service->delete(array('id' => $editID));
				$this->info = $this->t('kat_deleted');
			}
			else {
				$this->warning = $this->t('kat_not_deleted');
			}
		}

		$this->index();
	}
}
