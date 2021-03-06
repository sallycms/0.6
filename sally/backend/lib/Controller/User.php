<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_User extends sly_Controller_Backend implements sly_Controller_Interface {
	protected function init() {
		$layout = sly_Core::getLayout();
		$layout->pageHeader(t('users'));
	}

	public function indexAction() {
		$this->init();
		$this->listUsers();
	}

	public function addAction() {
		$this->init();

		if (sly_post('save', 'boolean', false)) {
			$password = sly_post('userpsw', 'string');
			$login    = sly_post('userlogin', 'string');
			$timezone = sly_post('timezone', 'string');
			$service  = sly_Service_Factory::getUserService();
			$params   = array(
				'login'       => $login,
				'name'        => sly_post('username', 'string'),
				'description' => sly_post('userdesc', 'string'),
				'status'      => sly_post('userstatus', 'boolean', false),
				'timezone'    => $timezone ? $timezone : null,
				'psw'         => $password,
				'rights'      => $this->getRightsFromForm(null)
			);

			try {
				$service->create($params);
				print sly_Helper_Message::info(t('user_added'));
				$this->listUsers();
				return;
			}
			catch (Exception $e) {
				print sly_Helper_Message::warn($e->getMessage());
			}
		}

		$this->func = 'add';
		$this->render('user/edit.phtml', array('user' => null), false);
	}

	public function editAction() {
		$this->init();

		$user = $this->getUser();

		if ($user === null) {
			return $this->listUsers();
		}

		$save        = sly_post('save', 'boolean', false);
		$service     = sly_Service_Factory::getUserService();
		$currentUser = sly_Util_User::getCurrentUser();
		$isSelf      = $currentUser->getId() === $user->getId();
		$safeMode    = $user->isAdmin() && !$currentUser->isAdmin();

		if ($save) {
			$status = sly_post('userstatus', 'boolean', false) ? 1 : 0;
			$tz     = sly_post('timezone', 'string', '');

			if ($isSelf || $safeMode) {
				$status = $user->getStatus();
			}

			$user->setName(sly_post('username', 'string'));
			$user->setDescription(sly_post('userdesc', 'string'));
			$user->setStatus($status);
			$user->setUpdateColumns();
			$user->setTimezone($tz ? $tz : null);

			// change password

			$password = sly_post('userpsw', 'string');

			if (!empty($password) && $password != $user->getPassword()) {
				$user->setPassword($password);
			}

			$user->setRights($this->getRightsFromForm($user));

			// save it

			try {
				$user = $service->save($user);
				$goon = sly_post('apply', 'string');

				print sly_Helper_Message::info(t('user_updated'));
			}
			catch (Exception $e) {
				print sly_Helper_Message::warn($e->getMessage());
				$goon = true;
			}

			if (!$goon) {
				$this->listUsers();
				return true;
			}
		}

		$params     = array('user' => $user);
		$this->func = 'edit';

		$this->render('user/edit.phtml', $params, false);
	}

	public function deleteAction() {
		$this->init();

		$user = $this->getUser();

		if ($user === null) {
			return $this->listUsers();
		}

		$service = sly_Service_Factory::getUserService();
		$current = sly_Util_User::getCurrentUser();

		if ($current->getId() == $user->getId()) {
			print sly_Helper_Message::warn(t('you_cannot_delete_yourself'));
			return false;
		}

		if ($user->isAdmin() && !$current->isAdmin()) {
			print sly_Helper_Message::warn(t('you_cannot_delete_admins'));
			return false;
		}

		$user->delete();
		print sly_Helper_Message::info(t('user_deleted'));

		$this->listUsers();
	}

	public function viewAction() {
		$this->init();

		$user = $this->getUser();

		if ($user === null) {
			return $this->listUsers();
		}

		$params = array('user' => $user);
		$this->render('user/view.phtml', $params, false);
	}

	public function checkPermission($action) {
		$user = sly_Util_User::getCurrentUser();
		if (!$user) return false;

		if ($user->isAdmin()) {
			return true;
		}

		if (!$user->hasRight('pages', 'user')) {
			return false;
		}

		if (in_array($action, array('add', 'edit', 'delete'))) {
			return $user->hasRight('user', $action);
		}

		return true;
	}

	protected function listUsers() {
		sly_Table::setElementsPerPageStatic(20);

		$search  = sly_Table::getSearchParameters('users');
		$paging  = sly_Table::getPagingParameters('users', true, false);
		$service = sly_Service_Factory::getUserService();
		$where   = null;

		if (!empty($search)) {
			$db    = sly_DB_Persistence::getInstance();
			$where = 'login LIKE ? OR description LIKE ? OR name LIKE ?';
			$where = str_replace('?', $db->quote('%'.$search.'%'), $where);
		}

		// allow addOns to filter on their own and append something like ' AND id IN (the,ids,the,addon,found)'
		// do not only do this when !empty($search) to allow addOns to have their own filtering GUI
		$where = sly_Core::dispatcher()->filter('SLY_USER_FILTER_WHERE', $where, array('search' => $search, 'paging' => $paging));

		$users = $service->find($where, null, 'name', $paging['start'], $paging['elements']);
		$total = $service->count($where);

		$this->render('user/list.phtml', compact('users', 'total'), false);
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
			$i18n = new sly_I18N($locale, $langpath, false);
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
				$startpages[$page] = sly_translate($name);
			}
		}

		return $startpages;
	}

	protected function getRightsFromForm($user) {
		$permissions = array();
		$curUser     = sly_Util_User::getCurrentUser();
		$isAdmin     = $curUser->isAdmin();
		$isSelfEdit  = $user !== null && $curUser->getId() === $user->getId();
		$safeMode    = $user && $user->isAdmin() && !$curUser->isAdmin();

		// admin status

		if ($safeMode || ($isAdmin && ($isSelfEdit || sly_post('is_admin', 'boolean', false)))) {
			$permissions[] = 'admin[]';
		}

		// backend locale and startpage

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

		// and build the permission string

		return '#'.implode('#', $permissions).'#';
	}
}
