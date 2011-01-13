<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
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
}
