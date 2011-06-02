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
	protected $func = '';

	public function init() {
		$layout = sly_Core::getLayout();
		$layout->pageHeader(t('profile_title'));
		print '<div class="sly-content">';
	}

	public function teardown() {
		print '</div>';
	}

	public function index() {
		$this->render('profile/index.phtml', array('user' => $this->getUser()));
		return true;
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
		$user->setTimeZone($timezone);

		// Passwort ändern?

		$password = sly_post('password', 'string');
		$service  = sly_Service_Factory::getUserService();

		if (!empty($password)) {
			$user->setPassword($password);
		}

		// Speichern, fertig.

		$service->save($user);
		print rex_info(t('user_data_updated'));
		return $this->index();
	}

	public function checkPermission() {
		return $this->getUser() !== null;
	}

	protected function getBackendLocales() {
		$cur_htmlcharset = t('htmlcharset');
		$langpath        = SLY_COREFOLDER.DIRECTORY_SEPARATOR.'lang';
		$langs           = glob($langpath.'/*.yml');
		$result          = array('' => 'default');

		foreach ($langs as $file) {
			$locale  = substr(basename($file), 0, -5);
			$tmpI18N = rex_create_lang($locale, $langpath, false); // Locale nicht neu setzen

			if ($cur_htmlcharset == $tmpI18N->msg('htmlcharset')) {
				$result[$locale] = $tmpI18N->msg('lang');
			}

			$tmpI18N = null;
		}

		return $result;
	}

	protected function getUser() {
		return sly_Util_User::getCurrentUser();
	}
}
