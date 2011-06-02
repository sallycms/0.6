<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Mediapool_Upload extends sly_Controller_Mediapool {
	public function index() {
		$this->render('mediapool/upload.phtml');
	}

	public function upload() {
		if (!empty($_FILES['file_new']['name']) && $_FILES['file_new']['name'] != 'none') {
			$title = sly_request('ftitle', 'string');
			$cat   = $this->getCurrentCategory();

			if (!$this->canAccessCategory($cat)) {
				$cat = 0;
			}

			// add the actual database record
			$file = $this->saveMedium($_FILES['file_new'], $cat, $title);

			// close the popup, if requested

			if (sly_post('saveandexit', 'boolean', false) && $file !== null) {
				$this->render('mediapool/javascript.phtml');
				$this->render('mediapool/upload_js.phtml', array('file' => $file));
				exit;
			}
			elseif ($file !== null) {
				header('Location: index.php?page=mediapool&info='.urlencode($this->info));
				exit;
			}
		}
		else {
			$this->warning = $this->t('file_not_found_maybe_too_big');
		}

		$this->index();
	}

	protected function saveMedium($fileData, $category, $title) {
		// check category

		$category = (int) $category;
		$service  = sly_Service_Factory::getService('Media_Category');

		if ($service->findById($category) === null) {
			$category = 0;
		}

		$filename    = $fileData['name'];
		$newFilename = $this->createFilename($filename);

		// create filenames

		$dstFile = SLY_MEDIAFOLDER.'/'.$newFilename;
		$file    = null;

		// move uploaded file

		if (!@move_uploaded_file($fileData['tmp_name'], $dstFile)) {
			$this->warning = $this->t('file_movefailed');
		}
		else {
			@chmod($dstFile, sly_Core::config()->get('FILEPERM'));

			// create and save our file

			$file    = $this->createFileObject($dstFile, $fileData['type'], $title, $category, $filename);
			$service = sly_Service_Factory::getService('Media_Medium');

			$service->save($file);

			// notify the system

			sly_Core::dispatcher()->notify('SLY_MEDIA_ADDED', $file);
			$this->info = $this->t('file_added');
		}

		// return the new file

		return $file;
	}
}
