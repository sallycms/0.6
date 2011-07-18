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
		print $this->render('mediapool/upload.phtml');
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
				print $this->render('mediapool/javascript.phtml');
				print $this->render('mediapool/upload_js.phtml', array('file' => $file));
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

	protected function saveMedium(array $fileData, $category, $title) {
		try {
			sly_Util_Medium::upload($fileData, $category, $title);
			$this->info = $this->t('file_added');
		}
		catch (sly_Exception $e) {
			$this->warning = $this->t('file_movefailed');
		}

		return $file;
	}
}
