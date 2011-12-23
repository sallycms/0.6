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

			$callback = sly_request('callback', 'string');

			if ($callback && sly_post('saveandexit', 'boolean', false) && $file !== null) {
				print $this->render('mediapool/upload_js.phtml', compact('file', 'callback'));
				exit;
			}
			elseif ($file !== null) {
				header('Location: index.php?page=mediapool&info='.urlencode($this->info));
				exit;
			}
		}
		else {
			$this->warning = t('file_not_found_maybe_too_big');
		}

		$this->index();
	}

	protected function saveMedium(array $fileData, $category, $title) {
		$file = null;

		try {
			$file       = sly_Util_Medium::upload($fileData, $category, $title);
			$this->info = t('file_added');
		}
		catch (sly_Exception $e) {
			$this->warning = $e->getMessage();
		}

		return $file;
	}
}
