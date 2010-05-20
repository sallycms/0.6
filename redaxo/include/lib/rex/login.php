<?php


/**
 * Klasse zum Handling des Login/Logout-Mechanismus
 *
 * @package redaxo4
 * @version svn:$Id$
 */

class rex_login
{
  var $DB;
  var $session_duration;
  var $login_query;
  var $user_query;
  var $system_id;
  var $usr_login;
  var $usr_psw;
  var $logout;
  var $message;
  var $uid;
  var $USER;
  var $passwordfunction;
  var $cache;
  var $login_status;

  function rex_login()
  {
    $this->DB = 1;
    $this->logout = false;
    $this->message = "";
    $this->system_id = "default";
    $this->cache = false;
    $this->login_status = 0; // 0 = noch checken, 1 = ok, -1 = not ok
    if (session_id() == "") 
    	session_start();
  }

  /**
   * Setzt, ob die Ergebnisse der Login-Abfrage
   * pro Seitenaufruf gecached werden sollen
   */
  function setCache($status = true)
  {
    $this->cache = $status;
  }

  /**
   * Setzt die Id der zu verwendenden SQL Connection
   */
  function setSqlDb($DB)
  {
    $this->DB = $DB;
  }

  /**
   * Setzt eine eindeutige System Id, damit mehrere
   * Sessions auf der gleichen Domain unterschieden werden k�nnen
   */
  function setSysID($system_id)
  {
    $this->system_id = $system_id;
  }

  /**
   * Setzt das Session Timeout
   */
  function setSessiontime($session_duration)
  {
    $this->session_duration = $session_duration;
  }

  /**
   * Setzt den Login und das Password
   */
  function setLogin($usr_login, $usr_psw)
  {
    $this->usr_login = $usr_login;
    $this->usr_psw = $this->encryptPassword($usr_psw);
  }

  /**
   * Markiert die aktuelle Session als ausgeloggt
   */
  function setLogout($logout)
  {
    $this->logout = $logout;
  }

  /**
   * Prüft, ob die aktuelle Session ausgeloggt ist
   */
  function isLoggedOut()
  {
    return $this->logout;
  }

  /**
   * Setzt den UserQuery
   *
   * Dieser wird benutzt, um einen bereits eingeloggten User
   * im Verlauf seines Aufenthaltes auf der Webseite zu verifizieren
   */
  function setUserquery($user_query)
  {
    $this->user_query = $user_query;
  }

  /**
   * Setzt den LoginQuery
   *
   * Dieser wird benutzt, um den eigentlichne Loginvorgang durchzuf�hren.
   * Hier wird das eingegebene Password und der Login eingesetzt.
   */
  function setLoginquery($login_query)
  {
    $this->login_query = $login_query;
  }

  /**
   * Setzt den Namen der Spalte, der die User-Id enth�lt
   */
  function setUserID($uid)
  {
    $this->uid = $uid;
  }

  /**
   * Setzt einen Meldungstext
   */
  function setMessage($message)
  {
    $this->message = $message;
  }

  /**
   * Pr�ft die mit setLogin() und setPassword() gesetzten Werte
   * anhand des LoginQueries/UserQueries und gibt den Status zur�ck
   *
   * Gibt true zur�ck bei erfolg, sonst false
   */
  function checkLogin()
  {
    global $REX, $I18N;

    if (!is_object($I18N)) $I18N = rex_create_lang();

    // wenn logout dann header schreiben und auf error seite verweisen
    // message schreiben

    $ok = false;

    if (!$this->logout)
    {
      // LoginStatus: 0 = noch checken, 1 = ok, -1 = not ok

      // checkLogin schonmal ausgef�hrt ? gecachte ausgabe erlaubt ?
      if ($this->cache)
      {
        if($this->login_status > 0)
          return true;
        elseif ($this->login_status < 0)
          return false;
      }
		

      if ($this->usr_login != '')
      {
        // wenn login daten eingegeben dann checken
        // auf error seite verweisen und message schreiben

        $this->USER = new rex_login_sql($this->DB);
        $USR_LOGIN = $this->usr_login;
        $USR_PSW = $this->usr_psw;

        $query = str_replace('USR_LOGIN', $this->usr_login, $this->login_query);
        $query = str_replace('USR_PSW', $this->usr_psw, $query);

        $this->USER->setQuery($query);
        if ($this->USER->getRows() == 1)
        {
          $ok = true;
          $this->setSessionVar('UID', $this->USER->getValue($this->uid));
          $this->sessionFixation();
        }
        else
        {
          $this->message = $I18N->msg('login_error', '<strong>'. $REX['RELOGINDELAY'] .'</strong>');
          $this->setSessionVar('UID', '');
        }

      }
      elseif ($this->getSessionVar('UID') != '')
      {
        // wenn kein login und kein logout dann nach sessiontime checken
        // message schreiben und falls falsch auf error verweisen

        $this->USER = new rex_login_sql($this->DB);
        $query = str_replace('USR_UID', $this->getSessionVar('UID'), $this->user_query);

        $this->USER->setQuery($query);
        if ($this->USER->getRows() == 1)
        {
          if (($this->getSessionVar('STAMP') + $this->session_duration) > time())
          {
            $ok = true;
            $this->setSessionVar('UID', $this->USER->getValue($this->uid));
          }
          else
          {
	          $this->message = $I18N->msg('login_session_expired');
          }
        }
        else
        {
          $this->message = $I18N->msg('login_user_not_found');
        }
      }
      else
      {
        $this->message = $I18N->msg('login_welcome');
        $ok = false;
      }
    }
    else
    {
      $this->message = $I18N->msg('login_logged_out');
      $this->setSessionVar('UID', '');
    }

    if ($ok)
    {
      // wenn alles ok dann REX[UID][system_id) schreiben
      $this->setSessionVar('STAMP', time());
    }
    else
    {
      // wenn nicht, dann UID loeschen und error seite
      $this->setSessionVar('STAMP', '');
      $this->setSessionVar('UID', '');
    }

    if ($ok)
      $this->login_status = 1;
    else
      $this->login_status = -1;

    return $ok;
  }

  /**
   * Gibt einen Benutzer-Spezifischen Wert zur�ck
   */
  function getValue($value, $default = NULL)
  {
  	if($this->USER)
    	return $this->USER->getValue($value);
    	
  	return $default;
  }

  /**
   * Setzt eine Password-Funktion
   */
  function setPasswordFunction($pswfunc)
  {
    $this->passwordfunction = $pswfunc;
  }

  /**
   * Verschl�sselt den �bergebnen String, falls eine Password-Funktion gesetzt ist.
   */
  function encryptPassword($psw)
  {
    if ($this->passwordfunction == "")
      return $psw;

    return call_user_func($this->passwordfunction, $psw);
  }

  /**
   * Setzte eine Session-Variable
   */
  function setSessionVar($varname, $value)
  {
    $_SESSION[$this->system_id][$varname] = $value;
  }

  /**
   * Gibt den Wert einer Session-Variable zur�ck
   */
  function getSessionVar($varname, $default = '')
  {
    if (isset ($_SESSION[$this->system_id][$varname]))
      return $_SESSION[$this->system_id][$varname];

    return $default;
  }

  /*
   * Session fixation
   */
  function sessionFixation()
  {
    // 1. parameter ist erst seit php5.1 verf�gbar
    if (version_compare(phpversion(), '5.1.0', '>=') == 1)
    {
      session_regenerate_id(true);
    }
    else if (function_exists('session_regenerate_id'))
    {
      session_regenerate_id();
    }
  }
}
