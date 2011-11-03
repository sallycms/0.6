<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Profile extends sly_Controller_Backend {
	public function init() {
		$layout = sly_Core::getLayout();
		$layout->pageHeader(t('profile_title'));
	}

	public function index() {
		print $this->render('profile/index.phtml', array('user' => $this->getUser()));
	}

	public function update() {
		$user = $this->getUser();

		$user->setName(sly_post('username', 'string'));
		$user->setDescription(sly_post('description', 'string'));
		$user->setUpdateDate(time());
		$user->setUpdateUser($user->getLogin());

		// Backend-Sprache

		$backendLocale  = sly_post('locale', 'string');
		$backendLocales = $this->getBackendLocales();

		if (isset($backendLocales[$backendLocale])) {
			$user->toggleRight('#be_lang['.$user->getBackendLocale().']', false);
			$user->toggleRight('#be_lang['.$backendLocale.']');
		}

		// timezone
		$timezone  = sly_post('timezone', 'string');
		$user->setTimezone($timezone ? $timezone : null);

		// Passwort ändern?

		$password = sly_post('password', 'string');
		$service  = sly_Service_Factory::getUserService();

		if (!empty($password)) {
			$user->setPassword($password);
		}

		// Speichern, fertig.

		$service->save($user);
		print sly_Helper_Message::info(t('user_data_updated'));
		return $this->index();
	}

	public function checkPermission() {
		return $this->getUser() !== null;
	}

	protected function getBackendLocales() {
		$langpath = SLY_SALLYFOLDER.'/backend/lang';
		$langs    = sly_I18N::getLocales($langpath);
		$result   = array('' => t('use_default_locale'));

		foreach ($langs as $locale) {
			$i18n = new sly_I18N($locale, $langpath);
			$result[$locale] = $i18n->msg('lang');
		}

		return $result;
	}

	protected function getUser() {
		return sly_Util_User::getCurrentUser();
	}
}
