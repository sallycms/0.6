<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_User extends sly_Controller_Backend {
	public function init() {
		$layout = sly_Core::getLayout();
		$layout->pageHeader(t('title_user'));
	}

	public function index() {
		$this->listUsers();
	}

	public function add() {
		if (sly_post('save', 'boolean', false)) {
			$password = sly_post('userpsw', 'string');
			$login    = sly_post('userlogin', 'string');
			$timezone = sly_post('timezone', 'string');
			$service  = sly_Service_Factory::getUserService();
			$error    = false;

			if (empty($login)) {
				print rex_warning('Es muss ein Loginname angegeben werden.');
				$error = true;
			}

			if (empty($password)) {
				print rex_warning('Es muss ein Passwort angegeben werden.');
				$error = true;
			}

			if ($service->find(array('login' => $login))) {
				print rex_warning(t('user_login_exists'));
				$error = true;
			}

			if ($error) {
				$this->func = 'add';
				print $this->render('user/edit.phtml', array('user' => null));
				return true;
			}

			$currentUser = sly_Util_User::getCurrentUser();

			$params = array(
				'login'       => sly_post('userlogin', 'string'),
				'name'        => sly_post('username', 'string'),
				'description' => sly_post('userdesc', 'string'),
				'status'      => sly_post('userstatus', 'boolean', false) ? 1 : 0,
				'lasttrydate' => 0,
				'timezone'    => $timezone ? $timezone : null,
				'createdate'  => time(),
				'updatedate'  => time(),
				'createuser'  => $currentUser->getLogin(),
				'updateuser'  => $currentUser->getLogin(),
				'psw'         => $password,
				'rights'      => $this->getRightsFromForm(null),
				'revision'    => 0
			);

			// Speichern, fertig.

			$service->create($params);

			print rex_info(t('user_added'));
			$this->listUsers();
			return true;
		}

		$this->func = 'add';
		print $this->render('user/edit.phtml', array('user' => null));
	}

	public function edit() {
		$user = $this->getUser();

		if ($user === null) {
			return $this->listUsers();
		}

		$save        = sly_post('save', 'boolean', false);
		$service     = sly_Service_Factory::getUserService();
		$currentUser = sly_Util_User::getCurrentUser();

		if ($save) {
			$status = sly_post('userstatus', 'boolean', false) ? 1 : 0;

			if ($currentUser->getId() == $user->getId()) {
				$status = $user->getStatus();
			}

			$user->setName(sly_post('username', 'string'));
			$user->setDescription(sly_post('userdesc', 'string'));
			$user->setStatus($status);
			$user->setUpdateDate(time());
			$user->setUpdateUser($currentUser->getLogin());

			if (class_exists('DateTimeZone')) {
				$tz = sly_post('timezone', 'string', '');
				$user->setTimezone($tz ? $tz : null);
			}

			// Passwort ändern?

			$password = sly_post('userpsw', 'string');

			if (!empty($password) && $password != $user->getPassword()) {
				$user->setPassword($password);
			}

			$user->setRights($this->getRightsFromForm($user));

			// Speichern, fertig.

			$user = $service->save($user);
			$goon = sly_post('apply', 'string');

			print rex_info(t('user_data_updated'));

			if (!$goon) {
				$this->listUsers();
				return true;
			}
		}

		$params     = array('user' => $user);
		$this->func = 'edit';

		print $this->render('user/edit.phtml', $params);
	}

	public function delete() {
		$user = $this->getUser();

		if ($user === null) {
			return $this->listUsers();
		}

		$service = sly_Service_Factory::getUserService();
		$current = sly_Util_User::getCurrentUser();

		if ($current->getId() == $user->getId()) {
			print rex_warning(t('user_notdeleteself'));
			return false;
		}

		$user->delete();
		print rex_info(t('user_deleted'));

		$this->listUsers();
	}

	public function checkPermission() {
		$user = sly_Util_User::getCurrentUser();
		return !is_null($user) && $user->isAdmin();
	}

	protected function listUsers() {
		$service = sly_Service_Factory::getUserService();
		$users   = $service->find(null, null, 'name', null, null);
		print $this->render('user/list.phtml', array('users' => $users));
	}

	protected function getUser() {
		$userID  = sly_request('id', 'int', 0);
		$service = sly_Service_Factory::getUserService();
		$user    = $service->findById($userID);

		return $user;
	}

	protected function getBackendLocales() {
		$langpath = SLY_SALLYFOLDER.'/backend/lang';
		$locales  = sly_I18N::getLocales($langpath);
		$result   = array('' => t('use_default_locale'));

		foreach ($locales as $locale) {
			$i18n = new sly_I18N($locale, $langpath);
			$result[$locale] = $i18n->msg('lang');
		}

		return $result;
	}

	protected function getPossibleStartpages() {
		$service = sly_Service_Factory::getAddOnService();
		$addons  = $service->getAvailableAddons();

		$startpages = array();
		$startpages['structure'] = t('structure');
		$startpages['profile']   = t('profile');

		foreach ($addons as $addon) {
			$page = $service->getProperty($addon, 'page', null);
			$name = $service->getProperty($addon, 'name', $addon);

			if ($page) {
				$startpages[$page] = rex_translate($name);
			}
		}

		return $startpages;
	}

	protected function getModules() {
		$service = sly_Service_Factory::getModuleService();
		return $service->getModules();
	}

	protected function getRightsFromForm($user) {
		$permissions = array();
		$current     = sly_Util_User::getCurrentUser()->getId();
		$config      = sly_Core::config();

		if (sly_post('is_admin', 'boolean', false) || ($user && $current == $user->getId())) {
			$permissions[] = 'admin[]';
		}

		// Rechte, die nur der Optik wegen in diesen Gruppen angeordnet wurden.

		if (sly_post('userperm_cat_all',   'boolean', false)) $permissions[] = 'csw[0]';
		if (sly_post('userperm_media_all', 'boolean', false)) $permissions[] = 'media[0]';

		foreach (sly_postArray('userperm_all',   'string') as $perm) $permissions[] = $perm;
		foreach (sly_postArray('userperm_ext',   'string') as $perm) $permissions[] = $perm;
		foreach (sly_postArray('userperm_extra', 'string') as $perm) $permissions[] = $perm;

		foreach (sly_postArray('userperm_media',    'int') as $perm)    $permissions[] = 'media['.$perm.']';
		foreach (sly_postArray('userperm_sprachen', 'int') as $perm)    $permissions[] = 'clang['.$perm.']';
		foreach (sly_postArray('userperm_module',   'string') as $perm) $permissions[] = 'module['.$perm.']';

		// Schreib- und Leserechte für die Kategoriestruktur

		$allowedCategories = sly_postArray('userperm_cat', 'int');

		if (!empty($allowedCategories)) {
			$persistence = sly_DB_Persistence::getInstance();

			$persistence->query(
				'SELECT DISTINCT path FROM '.$config->get('DATABASE/TABLE_PREFIX').'article '.
				'WHERE id IN ('.implode(',', $allowedCategories).') AND clang = 1'
			);

			$pathIDs = array();

			foreach ($persistence as $row) {
				// aufsplitten und leere Elemente entfernen
				$elements = array_filter(explode('|', $row['path']));

				// Ist vermutlich schneller als array_unique(array_merge(a,b)).
				foreach ($elements as $id) $pathIDs[$id] = true;
			}

			foreach ($allowedCategories as $id)   $permissions[] = 'csw['.$id.']';
			foreach (array_keys($pathIDs) as $id) $permissions[] = 'csr['.$id.']';
		}

		// Backend-Sprache und -Startseite

		$backendLocale  = sly_post('userperm_mylang', 'string');
		$backendLocales = $this->getBackendLocales();
		$startpage      = sly_post('userperm_startpage', 'string');
		$startpages     = $this->getPossibleStartpages();

		if (isset($backendLocales[$backendLocale])) {
			$permissions[] = 'be_lang['.$backendLocale.']';
		}

		if (isset($startpages[$startpage])) {
			$permissions[] = 'startpage['.$startpage.']';
		}

		// Rechte zurückgeben

		return '#'.implode('#', $permissions).'#';
	}

	protected function getStructure() {
		$rootCats        = sly_Util_Category::getRootCategories();
		$this->structure = array();

		if ($rootCats) {
			foreach ($rootCats as $rootCat) {
				$this->walkTree($rootCat, 0, $this->structure);
			}
		}

		return $this->structure;
	}

	protected function getMediaStructure() {
		$rootCats          = sly_Util_MediaCategory::getRootCategories();
		$this->mediaStruct = array();

		if ($rootCats) {
			foreach ($rootCats as $rootCat) {
				$this->walkTree($rootCat, 0, $this->mediaStruct);
			}
		}

		return $this->mediaStruct;
	}

	protected function walkTree($category, $depth, &$target) {
		if (empty($category)) return;

		$target[$category->getId()] = str_repeat(' ', $depth*2).$category->getName();

		$children = $category->getChildren();

		if (is_array($children)) {
			foreach ($children as $child) {
				$this->walkTree($child, $depth + 1, $target);
			}
		}
	}
}
