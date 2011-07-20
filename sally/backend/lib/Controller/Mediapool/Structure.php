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
		print $this->render('mediapool/structure.phtml');
	}

	public function add() {
		if (!empty($_POST)) {
			$service  = sly_Service_Factory::getMediaCategoryService();
			$name     = sly_post('catname', 'string');
			$parentID = sly_post('cat_id', 'int');

			try {
				$parent = $service->findById($parentID); // may be null
				$service->add($name, $parent);

				$this->info   = $this->t('kat_saved', $name);
				$this->action = '';
			}
			catch (Exception $e) {
				$this->warning = $e->getMessage();
			}
		}

		$this->index();
	}

	public function edit() {
		if (!empty($_POST)) {
			$editID   = sly_request('edit_id', 'int');
			$service  = sly_Service_Factory::getMediaCategoryService();
			$category = $service->findById($editID);

			if ($category) {
				$name = sly_post('catname', 'string');

				try {
					$category->setName($name);
					$service->update($category);

					$this->info   = $this->t('kat_updated', $name);
					$this->action = '';
				}
				catch (Exception $e) {
					$this->warning = $e->getMessage();
				}
			}
		}

		$this->index();
	}

	public function delete() {
		$editID   = sly_request('edit_id', 'int');
		$service  = sly_Service_Factory::getMediaCategoryService();
		$category = $service->findById($editID);

		if ($category) {
			try {
				$service->delete($editID);
				$this->info = $this->t('kat_deleted');
			}
			catch (Exception $e) {
				$code  = $e->getCode();
				$media = $code == sly_Service_MediaCategory::ERR_CAT_HAS_MEDIA;

				$this->warning = $this->t($media ? 'kat_not_deleted_media' : 'kat_not_deleted_subcats');
			}
		}

		$this->index();
	}
}
