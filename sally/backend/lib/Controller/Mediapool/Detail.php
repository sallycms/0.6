<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Mediapool_Detail extends sly_Controller_Mediapool {
	protected $file;

	public function index() {
		$fileID = $this->getCurrentFile();

		print $this->render('mediapool/toolbar.phtml');

		if ($fileID == -1) {
			$this->warning = $this->t('file_not_found');
			print $this->render('mediapool/index.phtml');
			return;
		}

		print $this->render('mediapool/detail.phtml');
	}

	protected function getCurrentFile() {
		if ($this->file === null) {
			$fileID   = sly_request('file_id', 'int', -1);
			$fileName = sly_request('file_name', 'string');
			$service  = sly_Service_Factory::getMediumService();

			if (!empty($fileName)) {
				$files = $service->find(array('filename' => $fileName), null, null, 'LIMIT 1');

				if (!empty($files)) {
					$file   = reset($files);
					$fileID = $file->getID();
				}
				else {
					$fileID = -1;
				}
			}
			elseif (!empty($fileID)) {
				$file = $service->findById($fileID);
				if (!$file) $fileID = -1;
			}

			$this->file = (int) $fileID;
		}

		return $this->file;
	}

	public function save() {
		if (!empty($_POST['delete'])) {
			return $this->delete();
		}

		return $this->update();
	}

	public function update() {
		$fileID = $this->getCurrentFile();
		$medium = sly_Util_Medium::findById($fileID);
		$target = $this->getCurrentCategory();

		// only continue if a file was found, we can access it and have access
		// to the target category

		if (!$medium || !$this->canAccessFile($medium) || !$this->canAccessCategory($target)) {
			$this->warning = t('no_permission');
			return $this->index();
		}

		// update our file

		$title = sly_request('title', 'string');
		$msg   = $this->t('file_infos_updated');
		$ok    = true;

		// upload new file or just change file properties?

		if (!empty($_FILES['file_new']['name']) && $_FILES['file_new']['name'] != 'none') {
			try {
				sly_Util_Medium::upload($_FILES['file_new'], $target, $title, $medium);
				$msg = $this->t('file_changed');
			}
			catch (Exception $e) {
				$ok   = false;
				$code = $e->getCode();
				$msg  = $this->t($code === sly_Util_Medium::ERR_TYPE_MISMATCH ? 'file_upload_errortype' : 'file_upload_error');
			}
		}
		else {
			$medium->setTitle($title);
			$medium->setCategoryId($target);

			$service = sly_Service_Factory::getMediumService();
			$service->update($medium);
		}

		// setup messages
		if ($ok) $this->info = $msg;
		else $this->warning = $msg;

		// show details page again
		$this->index();
	}

	public function delete() {
		$fileID = $this->getCurrentFile();
		$media  = sly_Util_Medium::findById($fileID);

		// only continue if a file was found and we can access it

		if (!$media || !$this->canAccessFile($media)) {
			$this->warning = t('no_permission');
			return $this->index();
		}

		$this->deleteMedia($media);
		parent::index();
	}
}
