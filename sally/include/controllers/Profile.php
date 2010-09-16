<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Profile extends sly_Controller_Sally
{
	protected $func = '';

	public function init()
	{
		$layout = sly_Core::getLayout();
		$layout->pageHeader(t('profile_title'));

		$layout = sly_Core::getLayout();
		$layout->appendToTitle(t('profile_title'));

		print '<div class="sly-content">';
	}

	public function teardown()
	{
		print '</div>';
	}

	public function index()
	{
		$this->render('views/profile/index.phtml', array('user' => $this->getUser()));
		return true;
	}

	public function update()
	{
		global $I18N, $REX;

		$user = $this->getUser();

		$user->setName(sly_post('username', 'string'));
		$user->setDescription(sly_post('userdesc', 'string'));
		$user->setUpdateDate(time());
		$user->setUpdateUser($user->getLogin());

		// Backend-Sprache

		$backendLocale  = sly_post('userperm_mylang', 'string');
		$backendLocales = $this->getBackendLocales();

		if (isset($backendLocales[$backendLocale])) {
			$rights = $user->getRights();
			$rights = preg_replace('/#be_lang\[.*?\]/', '#be_lang['.$backendLocale.']', $rights);
			$user->setRights($rights);
		}

		// Passwort ändern?

		$password = sly_post('userpsw', 'string');
		$service  = sly_Service_Factory::getService('User');

		if ($password && $password != $user->getPassword()) {
			$user->setPassword($service->hashPassword($password));
		}

		// Speichern, fertig.

		$service = sly_Service_Factory::getService('User');
		$service->save($user);

		print rex_info($I18N->msg('user_data_updated'));
		return $this->index();
	}

	public function checkPermission()
	{
		return $this->getUser() !== null;
	}

	protected function getBackendLocales()
	{
		global $I18N, $REX;

		$cur_htmlcharset = $I18N->msg('htmlcharset');
		$langpath        = SLY_INCLUDE_PATH.DIRECTORY_SEPARATOR.'lang';
		$langs           = glob($langpath.'/*.lang');
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
		return sly_Service_Factory::getService('User')->getCurrentUser();
	}
}