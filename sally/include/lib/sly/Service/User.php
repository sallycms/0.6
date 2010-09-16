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

	public function findByLogin($login) {
		$res = $this->find(array('login' => $login));
		if (count($res) == 1) return $res[0];
		return null;
	}

	public function getCurrentUser() {
		global $REX;
		$userID = $REX['LOGIN']->getValue('id');
		return $this->findById($userID);
	}

	public function logout() {
		$this->setSessionVar('UID', '');
	}

	/**
	 * Setzte eine Session-Variable
	 */
	protected function setSessionVar($varname, $value)
	{
		$instname = sly_Core::config()->get('INSTNAME');
		$_SESSION[$instname][$varname] = $value;
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
