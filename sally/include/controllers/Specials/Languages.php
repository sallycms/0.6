<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Specials_Languages extends sly_Controller_Sally {
	// for now just copy those two fields and the init() method, until
	// I find a nice way to generalize it into. --xrstf

	protected $warning   = '';
	protected $info      = '';
	protected $func      = '';
	protected $id        = '';
	protected $languages = array();

	public function init() {
		$subline = array(
			array('',          t('main_preferences')),
			array('languages', t('languages'))
		);

		$layout = sly_Core::getLayout();
		$layout->pageHeader(t('specials'), $subline);
	}

	public function index() {
		$languageService = sly_Service_Factory::getService('Language');
		$this->languages = $languageService->find(null, null, 'id');
		$this->render('views/specials/languages.phtml');
	}

	public function add() {
		global $REX;

		if (isset($_POST['sly-submit'])) {
			$this->id  = sly_request('clang_id', 'int', -1);
			$clangName = sly_request('clang_name', 'string');

			if (!empty($clangName)) {
				if (!isset($REX['CLANG'][$this->id]) && $this->id > 0) {
					rex_addCLang($this->id, $clangName);
					$this->info = t('clang_edited');
				}
				else {
					$this->warning = t('id_exists');
					$this->func    = 'add';
				}
			}
			else {
				$this->warning = t('enter_name');
				$this->func    = 'add';
			}
		}
		else {
			$this->func = 'add';
		}

		$this->index();
	}

	public function edit() {
		$this->id = sly_request('clang_id', 'int', -1);

		if (isset($_POST['sly-submit'])) {
			$clangName       = sly_request('clang_name', 'string');
			$languageService = sly_Service_Factory::getService('Language');
			$clang           = $languageService->findById($this->id);

			if ($clang) {
				$clang->setName($clangName);
				$languageService->save($clang);

				sly_Core::cache()->delete('sly.language', 'all');
				$this->info = t('clang_edited');
			}
		}
		else {
			$this->func = 'edit';
		}

		$this->index();
	}

	public function delete() {
		global $REX;

		$clangID = sly_request('clang_id', 'int', -1);

		if (isset($REX['CLANG'][$clangID])) {
			rex_deleteCLang($clangID);
			$this->info = t('clang_deleted');
		}

		$this->index();
	}

	public function checkPermission() {
		global $REX;
		return !empty($REX['USER']);
	}
}
