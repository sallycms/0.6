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
 * @author christoph@webvariants.de
 */
class sly_Service_User extends sly_Service_Model_Base {
	protected $tablename = 'user';

	protected function makeObject(array $params) {
		return new sly_Model_User($params);
	}

	public function getCurrentUser() {
		global $REX;
		$userID = $REX['LOGIN']->getValue('id');
		return $this->findById($userID);
	}

	public function hashPassword($password) {
		$config = sly_Core::config();
		return sly_Util_Password::hash($password, $config->get('INSTNAME'));
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
}
