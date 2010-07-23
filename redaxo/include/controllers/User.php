<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_User extends sly_Controller_Sally
{
	protected $func = '';

	public function init()
	{
		rex_title(t('title_user'));

		$layout = sly_Core::getLayout();
		$layout->appendToTitle(t('title_user'));

		print '<div class="sly-content">';
	}

	public function teardown()
	{
		print '</div>';
	}

	public function index()
	{
		$this->listUsers();
		return true;
	}

	public function add()
	{
		global $I18N, $REX;

		if (sly_post('save', 'boolean', false)) {
			$password = sly_post('userpsw', 'string');
			$login    = sly_post('userlogin', 'string');
			$service  = sly_Service_Factory::getService('User');
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
				print rex_warning($I18N->msg('user_login_exists'));
				$error = true;
			}

			if ($error) {
				$this->func = 'add';
				$this->render('views/user/edit.phtml', array('user' => null));
				return true;
			}

			$params = array(
				'login'       => sly_post('userlogin', 'string'),
				'name'        => sly_post('username', 'string'),
				'description' => sly_post('userdesc', 'string'),
				'status'      => sly_post('userstatus', 'boolean', false) ? 1 : 0,
				'createdate'  => time(),
				'updatedate'  => time(),
				'createuser'  => $REX['LOGIN']->getValue('login'),
				'updateuser'  => $REX['LOGIN']->getValue('login'),
				'psw'         => $service->hashPassword($password),
				'rights'      => $this->getRightsFromForm(null),
				'revision'    => 0
			);

			// Speichern, fertig.

			$service->create($params);

			print rex_info($I18N->msg('user_added'));
			$this->listUsers();
			return true;
		}

		$this->func = 'add';
		$this->render('views/user/edit.phtml', array('user' => null));
		return true;
	}

	public function edit()
	{
		global $I18N, $REX;

		$user = $this->getUser();

		if ($user === null) {
			$this->listUsers();
			return false;
		}

		$save    = sly_post('save', 'boolean', false);
		$service = sly_Service_Factory::getService('User');
		$current = $REX['USER']->getValue('id');

		if ($save) {
			$status = sly_post('userstatus', 'boolean', false) ? 1 : 0;

			if ($current == $user->getId()) {
				$status = $user->getStatus();
			}

			$user->setName(sly_post('username', 'string'));
			$user->setDescription(sly_post('userdesc', 'string'));
			$user->setStatus($status);
			$user->setUpdateDate(time());
			$user->setUpdateUser($REX['LOGIN']->getValue('login'));

			// Logins zurücksetzen?

			if (sly_post('logintriesreset', 'boolean', false)) {
				$user->setLoginTries(0);
			}

			// Passwort ändern?

			$password = sly_post('userpsw', 'string');

			if ($password && $password != $user->getPassword()) {
				$user->setPassword($service->hashPassword($password));
			}

			$user->setRights($this->getRightsFromForm($user));

			// Speichern, fertig.

			$user = $service->save($user);
			$goon = sly_post('apply', 'string');

			print rex_info($I18N->msg('user_data_updated'));

			if (!$goon) {
				$this->listUsers();
				return true;
			}
		}

		$params     = array('user' => $user);
		$this->func = 'edit';

		$this->render('views/user/edit.phtml', $params);
		return true;
	}

	public function delete()
	{
		global $REX, $I18N;

		$user = $this->getUser();

		if ($user === null) {
			$this->listUsers();
			return false;
		}

		$service = sly_Service_Factory::getService('User');
		$current = $REX['USER'];

		if ($current->getValue('id') == $user->getId()) {
			print rex_warning($I18N->msg('user_notdeleteself'));
			return false;
		}

		$user->delete();
		print rex_info($I18N->msg('user_deleted'));

		$this->listUsers();
		return true;
	}

	public function checkPermission()
	{
		global $REX;
		return isset($REX['USER']) && $REX['USER']->isAdmin();
	}

	protected function listUsers()
	{
		$service = sly_Service_Factory::getService('User');
		$users   = $service->find(null, null, 'name', null, null);
		$this->render('views/user/list.phtml', array('users' => $users));
	}

	protected function getUser()
	{
		$userID = sly_request('id', 'int', 0);
		$service  = sly_Service_Factory::getService('User');
		$user   = $service->findById($userID);

		if (!$userID || $user === null) {
			return null;
		}

		return $user;
	}

	protected function getBackendLocales()
	{
		global $I18N, $REX;

		$cur_htmlcharset = $I18N->msg('htmlcharset');
		$langpath        = $REX['INCLUDE_PATH'].'/lang';
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

	protected function getPossibleStartpages()
	{
		global $REX, $I18N;

		$service = sly_Service_Factory::getService('AddOn');
		$addons  = $service->getAvailableAddons();

		$startpages = array();
		$startpages['structure'] = $I18N->msg('structure'); // , '');
		$startpages['profile']   = $I18N->msg('profile'); // ,   '');

		foreach ($addons as $addon) {
			$perm = $service->getProperty($addon, 'perm', false);
			$name = $service->getProperty($addon, 'name', false);

			if ($perm && $name) {
				$startpages[$addon] = $name; // array($name, $perm);
			}
		}

		return $startpages;
	}

	protected function getModules()
	{
		$service = sly_Service_Factory::getService('Module');
		$modules = $service->find(null, null, 'name');
		$result  = array();

		foreach ($modules as $module) $result[$module->getId()] = $module->getName();

		return $result;
	}

	protected function getRightsFromForm($user)
	{
		global $REX;

		$permissions = array();
		$current     = $REX['USER']->getValue('id');
		$config      = sly_Core::config();

		if (sly_post('useradmin', 'boolean', false) || ($user && $current == $user->getId())) {
			$permissions[] = 'admin[]';
		}

		// Rechte, die nur der Optik wegen in diesen Gruppen angeordnet wurden.

		if (sly_post('allcats',   'boolean', false)) $permissions[] = 'csw[0]';
		if (sly_post('allmcats',  'boolean', false)) $permissions[] = 'media[0]';

		foreach (sly_postArray('userperm_all',   'string') as $perm) $permissions[] = $perm;
		foreach (sly_postArray('userperm_ext',   'string') as $perm) $permissions[] = $perm;
		foreach (sly_postArray('userperm_extra', 'string') as $perm) $permissions[] = $perm;

		foreach (sly_postArray('userperm_media',    'int') as $perm) $permissions[] = 'media['.$perm.']';
		foreach (sly_postArray('userperm_sprachen', 'int') as $perm) $permissions[] = 'clang['.$perm.']';
		foreach (sly_postArray('userperm_module',   'int') as $perm) $permissions[] = 'module['.$perm.']';

		// Schreib- und Leserechte für die Kategoriestruktur

		$allowedCategories = sly_postArray('userperm_cat', 'int');

		if (!empty($allowedCategories)) {
			$persistence = sly_DB_Persistence::getInstance();

			$persistence->query(
				'SELECT DISTINCT path FROM '.$config->get('DATABASE/TABLE_PREFIX').'article '.
				'WHERE id IN ('.implode(',', $allowedCategories).') AND clang = 0'
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

	protected function getStructure()
	{
		$rootCats        = OOCategory::getRootCategories();
		$this->structure = array();

		if ($rootCats) {
			foreach ($rootCats as $rootCat) {
				$this->walkTree($rootCat, 0, $this->structure);
			}
		}

		return $this->structure;
	}

	protected function getMediaStructure()
	{
		$rootCats          = OOMediaCategory::getRootCategories();
		$this->mediaStruct = array();

		if ($rootCats) {
			foreach ($rootCats as $rootCat) {
				$this->walkTree($rootCat, 0, $this->mediaStruct);
			}
		}

		return $this->mediaStruct;
	}

	protected function walkTree($category, $depth, &$target)
	{
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
