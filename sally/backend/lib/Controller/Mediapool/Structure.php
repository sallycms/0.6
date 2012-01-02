<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Mediapool_Structure extends sly_Controller_Mediapool {
	public function indexAction() {
		$this->init('index');
		print $this->render('mediapool/structure.phtml');
	}

	public function addAction() {
		$this->init('add');

		if (!empty($_POST)) {
			$service  = sly_Service_Factory::getMediaCategoryService();
			$name     = sly_post('catname', 'string');
			$parentID = sly_post('cat_id', 'int');

			try {
				$parent = $service->findById($parentID); // may be null
				$service->add($name, $parent);

				$this->info   = t('category_added', $name);
				$this->action = '';
			}
			catch (Exception $e) {
				$this->warning = $e->getMessage();
			}
		}

		$this->indexAction();
	}

	public function editAction() {
		$this->init('edit');

		if (!empty($_POST)) {
			$editID   = sly_request('edit_id', 'int');
			$service  = sly_Service_Factory::getMediaCategoryService();
			$category = $service->findById($editID);

			if ($category) {
				$name = sly_post('catname', 'string');

				try {
					$category->setName($name);
					$service->update($category);

					$this->info   = t('category_updated', $name);
					$this->action = '';
				}
				catch (Exception $e) {
					$this->warning = $e->getMessage();
				}
			}
		}

		$this->indexAction();
	}

	public function deleteAction() {
		$this->init('delete');

		$editID   = sly_request('edit_id', 'int');
		$service  = sly_Service_Factory::getMediaCategoryService();
		$category = $service->findById($editID);

		if ($category) {
			try {
				$service->delete($editID);
				$this->info = t('category_deleted');
			}
			catch (Exception $e) {
				$this->warning = $e->getMessage();
			}
		}

		$this->indexAction();
	}
}
