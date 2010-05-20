<?php

class rex_backend_login extends rex_login
{
  var $tableName;

  function rex_backend_login($tableName)
  {
    global $REX;

    parent::rex_login();

    $this->setSqlDb(1);
    $this->setSysID($REX['INSTNAME']);
    $this->setSessiontime($REX['SESSION_DURATION']);
    $this->setUserID($tableName .'.user_id');
    $this->setUserquery('SELECT * FROM '. $tableName .' WHERE status=1 AND user_id = "USR_UID"');
    $this->setLoginquery('SELECT * FROM '.$tableName .' WHERE status=1 AND login = "USR_LOGIN" AND psw = "USR_PSW" AND lasttrydate <'. (time()-$REX['RELOGINDELAY']).' AND login_tries<'.$REX['MAXLOGINS']);
    $this->tableName = $tableName;
  }

  function checkLogin()
  {
    global $REX;

    $fvs = new rex_sql;
    // $fvs->debugsql = true;
    $userId = $this->getSessionVar('UID');
    $check = parent::checkLogin();

    if($check)
    {
      // gelungenen versuch speichern | login_tries = 0
      if($this->usr_login != '')
      {
        $this->sessionFixation();
        $fvs->setQuery('UPDATE '.$this->tableName.' SET login_tries=0, lasttrydate='.time().', session_id="'. session_id() .'" WHERE login="'. $this->usr_login .'" LIMIT 1');
        
        if($fvs->hasError())
          return $fvs->getError();
      }
    }
    else
    {
      // fehlversuch speichern | login_tries++
      if($this->usr_login != '')
      {
        $fvs->setQuery('UPDATE '.$this->tableName.' SET login_tries=login_tries+1,session_id="",lasttrydate='.time().' WHERE login="'. $this->usr_login .'" LIMIT 1');
        
        if($fvs->hasError())
          return $fvs->getError();
      }
    }

    if ($this->isLoggedOut() && $userId != '')
    {
      $fvs->setQuery('UPDATE '.$this->tableName.' SET session_id="" WHERE user_id="'. $userId .'" LIMIT 1');
    }

    if($fvs->hasError())
      return $fvs->getError();

    return $check;
  }
  
  function getLanguage()
	{
	  global $REX;
	  
		if (preg_match_all('@#be_lang\[([^\]]*)\]#@' , $this->getValue("rights"), $matches))
    {
      foreach ($matches[1] as $match)
      {
        return $match;
      }
    }
    return $REX['LANG'];
	}

	function getStartpage()
	{
	  global $REX;
	  
  	if (preg_match_all('@#startpage\[([^\]]*)\]#@' , $this->getValue("rights"), $matches))
  	{
    	foreach ($matches[1] as $match)
    	{
      	return $match;
    	}
  	}
  	return $REX['START_PAGE'];
	}
}
