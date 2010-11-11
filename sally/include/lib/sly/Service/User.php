<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * DB Model Klasse für Benutzer
 *
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_User extends sly_Service_Model_Base {
	protected $tablename = 'user';

	protected function makeObject(array $params) {
		return new sly_Model_User($params);
	}

	public function create($params) {
		$model = $this->makeObject($params);
		if (isset($params['psw'])) $model->setPassword($params['psw']);
		return $this->save($model);
	}

	/**
	 * return user object with login
	 *
	 * @return sly_Model_User
	 */
	public function findByLogin($login) {
		$res = $this->find(array('login' => $login));
		if (count($res) == 1) return $res[0];
		return null;
	}

	/**
	 * return current user object
	 *
	 * @return sly_Model_User
	 */
	public function getCurrentUser() {
		$userID = sly_Util_Session::get('UID', 'int', -1);
		return $this->findById($userID);
	}

	public function login($login, $password) {
		$user = $this->findByLogin($login);
		$loginOK = false;
		if($user) {
			$loginOK = $user->getLastTryDate() < time()-sly_Core::config()->get('RELOGINDELAY')
					&& $user->getStatus() == 1
					&& $this->checkPassword($user, $password);

			if($loginOK) {
				sly_Util_Session::set('UID', $user->getId());
				sly_Util_Session::regenerate_id();
			}
			$user->setLastTryDate(time());
			$this->save($user);
		}
		return $loginOK;
	}


	public function logout() {
		sly_Util_Session::set('UID', '');
	}

	/**
	 * Checks if the given password matches to the users password
	 *
	 * @param  sly_Model_User  $user      The user object
	 * @param  string          $password  Password to check
	 * @return boolean                    true if the passwords match, otherwise false.
	 */
	public function checkPassword(sly_Model_User $user, $password) {
		return sly_Util_User::getPasswordHash($user, $password) == $user->getPassword();
	}

}
