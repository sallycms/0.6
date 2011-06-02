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

		$this->render('views/mediapool/toolbar.phtml');

		if ($fileID == -1) {
			$this->warning = $this->t('file_not_found');
			return $this->render('views/mediapool/index.phtml');
		}

		$this->render('views/mediapool/detail.phtml');
	}

	protected function getCurrentFile() {
		if ($this->file === null) {
			$fileID   = sly_request('file_id', 'int');
			$fileName = sly_request('file_name', 'string');
			$service  = sly_Service_Factory::getService('Media_Medium');

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
		$media  = OOMedia::getMediaById($fileID);
		$target = $this->getCurrentCategory();

		// only continue if a file was found, we can access it and have access
		// to the target category

		if (!$media || !$this->canAccessFile($media) || !$this->canAccessCategory($target)) {
			$this->warning = t('no_permission');
			return $this->index();
		}

		// update our file

		$service = sly_Service_Factory::getService('Media_Medium');
		$fileObj = $service->findById($fileID);

		$fileObj->setTitle(sly_request('ftitle', 'string'));
		$fileObj->setCategoryId($target);

		$msg = $this->t('file_infos_updated');
		$ok  = true;

		if (!empty($_FILES['file_new']['name']) && $_FILES['file_new']['name'] != 'none') {
			$filename = $_FILES['file_new']['tmp_name'];
			$filetype = $_FILES['file_new']['type'];
			$filesize = (int) $_FILES['file_new']['size'];
			$oldType  = $fileObj->getFiletype();

			if ($filetype == $oldType || OOMedia::compareImageTypes($filetype, $oldType)) {
				$targetFile = SLY_MEDIAFOLDER.'/'.$fileObj->getFilename();

				if (@move_uploaded_file($filename, $targetFile)) {
					$msg = $this->t('file_changed');

					$fileObj->setFiletype($filetype);
					$fileObj->setFilesize($filesize);

					if ($size = getimagesize($targetFile)) {
						$fileObj->setWidth($size[0]);
						$fileObj->setHeight($size[1]);
					}

					@chmod($targetFile, sly_Core::config()->get('FILEPERM'));
				}
				else {
					$msg = $this->t('file_upload_error');
					$ok  = false;
				}
			}
			else {
				$msg = $this->t('file_upload_errortype');
				$ok  = false;
			}
		}

		if ($ok) {
			// save changes
			$fileObj->setUpdateColumns();
			$service->save($fileObj);

			// re-validate asset cache
			$service = sly_Service_Factory::getAssetService();
			$service->validateCache();

			// notify the listeners and clear our own cache
			sly_Core::dispatcher()->notify('SLY_MEDIA_UPDATED', $fileObj);
			sly_Core::cache()->delete('sly.medium', $fileID);
		}

		// setup messages
		if ($ok) $this->info = $msg;
		else $this->warning = $msg;

		// show details page again
		$this->index();
	}

	public function delete() {
		$fileID = $this->getCurrentFile();
		$media  = OOMedia::getMediaById($fileID);

		// only continue if a file was found and we can access it

		if (!$media || !$this->canAccessFile($media)) {
			$this->warning = t('no_permission');
			return $this->index();
		}

		$this->deleteMedia($media);
		parent::index();
	}
}
