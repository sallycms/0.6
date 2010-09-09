<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * @ingroup redaxo
 */
class rex_backend_login extends rex_login
{
	public $tableName;

	public function __construct($tableName)
	{
		parent::__construct();
		$config = sly_Core::config();

		$this->setSqlDb(1);
		$this->setSysID($config->get('INSTNAME'));
		$this->setSessiontime($config->get('SESSION_DURATION'));
		$this->setUserID($tableName.'.id');
		$this->setUserquery('SELECT * FROM '.$tableName.' WHERE status = 1 AND id = "USR_UID"');
		$this->setLoginquery('SELECT * FROM '.$tableName.' WHERE status = 1 AND login = "USR_LOGIN" AND psw = "USR_PSW" AND lasttrydate < '.(time()-$config->get('RELOGINDELAY')));
		$this->tableName = $tableName;
	}

	public function checkLogin()
	{
		global $REX;

		$fvs    = new rex_sql();
		$userId = $this->getSessionVar('UID');
		$check  = parent::checkLogin();

		if (!empty($this->usr_login)) {
			if ($check) {
				// gelungenen Versuch speichern

				$this->sessionFixation();
				$fvs->setQuery('UPDATE '.$this->tableName.' SET lasttrydate = '.time().', session_id = "'.session_id().'" WHERE login = "'.$this->usr_login.'" LIMIT 1');
			}
			else {
				// Fehlversuch speichern

				$fvs->setQuery('UPDATE '.$this->tableName.' SET session_id = "", lasttrydate = '.time().' WHERE login = "'. $this->usr_login .'" LIMIT 1');
			}

			if ($fvs->hasError()) return $fvs->getError();
		}

		if ($this->isLoggedOut() && !empty($userId)) {
			$fvs->setQuery('UPDATE '.$this->tableName.' SET session_id = "" WHERE id = '.intval($userId).' LIMIT 1');
		}

		if ($fvs->hasError()) return $fvs->getError();
		return $check;
	}

	public function getLanguage()
	{
		if (preg_match('@#be_lang\[(.+?)\]#@', $this->getValue('rights'), $match)) {
			return $match[1];
		}

		return sly_Core::config()->get('LANG');
	}

	public function getStartpage()
	{
		if (preg_match('@#startpage\[(.+?)\]#@', $this->getValue('rights'), $match)) {
			return $match[1];
		}

		return sly_Core::config()->get('START_PAGE');
	}
}
