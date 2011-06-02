<?php

/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Specials_Languages extends sly_Controller_Backend {

	// for now just copy those two fields and the init() method, until
	// I find a nice way to generalize it into. --xrstf

	protected $warning = '';
	protected $info = '';
	protected $func = '';
	protected $id = '';
	protected $languages = array();

	public function init() {
		$subline = array(
			array('', t('main_preferences')),
			array('languages', t('languages'))
		);

		$layout = sly_Core::getLayout();
		$layout->pageHeader(t('specials'), $subline);
	}

	public function index() {
		$languageService = sly_Service_Factory::getLAnguageService();
		$this->languages = $languageService->find(null, null, 'id');
		$this->render('specials/languages.phtml');
	}

	public function add() {
		if (sly_post('sly-submit', 'boolean', false)) {
			$this->id = sly_post('clang_id', 'int', -1);
			$clangName = sly_post('clang_name', 'string');
			$clangLocale = sly_post('clang_locale', 'string');

			if (!empty($clangName)) {
				try {
					$languageService = sly_Service_Factory::getLanguageService();
					$languageService->create(array('name' => $clangName, 'locale' => $clangLocale));
					$this->info = t('clang_edited');
				}
				catch (Exception $e) {
					$this->warning = $e->getMessage();
				}
			}
			else {
				$this->warning = t('enter_name');
				$this->func = 'add';
			}
		}
		else {
			$this->func = 'add';
		}

		$this->index();
	}

	public function edit() {
		$this->id = sly_request('clang_id', 'int', -1);

		if (sly_post('sly-submit', 'boolean', false)) {
			$clangName = sly_post('clang_name', 'string');
			$clangLocale = sly_post('clang_locale', 'string');
			$languageService = sly_Service_Factory::getLanguageService();
			$clang = $languageService->findById($this->id);

			if ($clang) {
				$clang->setName($clangName);
				$clang->setLocale($clangLocale);
				$languageService->save($clang);

				$this->info = t('clang_edited');
			}
		}
		else {
			$this->func = 'edit';
		}

		$this->index();
	}

	public function delete() {
		$clangID   = sly_request('clang_id', 'int', -1);
		$languages = sly_Util_Language::findAll();

		if (isset($languages[$clangID])) {
			$ok = sly_Service_Factory::getLanguageService()->delete(array('id' =>$clangID));
			if ($ok > 0) $this->info = t('clang_deleted');
			else $this->warning = t('clang_delete_error');
		}

		$this->index();
	}

	public function checkPermission() {
		return sly_Util_User::getCurrentUser() != null;
	}
}
