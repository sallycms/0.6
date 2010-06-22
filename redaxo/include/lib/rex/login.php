<?php

/**
 * Klasse zum Handling des Login/Logout-Mechanismus
 *
 * @package redaxo4
 */

class rex_login
{
	/* Elemente sind public, da wir nicht sicher wissen, von wo auf sie zugegriffen wird. */
	
	public $DB;
	public $session_duration;
	public $login_query;
	public $user_query;
	public $system_id;
	public $usr_login;
	public $usr_psw;
	public $logout;
	public $message;
	public $uid;
	public $USER;
	public $cache;
	public $login_status;

	public function __construct()
	{
		$this->DB           = 1;
		$this->logout       = false;
		$this->message      = '';
		$this->system_id    = 'default';
		$this->cache        = false;
		$this->login_status = 0; // 0 = noch checken, 1 = ok, -1 = not ok
		
		if (!session_id() && !SLY_IS_TESTING) session_start();
	}

	/**
	 * Setzt, ob die Ergebnisse der Login-Abfrage
	 * pro Seitenaufruf gecached werden sollen
	 */
	public function setCache($status = true)
	{
		$this->cache = (boolean) $status;
	}

	/**
	 * Setzt die Id der zu verwendenden SQL Connection
	 */
	public function setSqlDb($DB)
	{
		$this->DB = (int) $DB;
	}

	/**
	 * Setzt eine eindeutige System Id, damit mehrere
	 * Sessions auf der gleichen Domain unterschieden werden können
	 */
	public function setSysID($system_id)
	{
		$this->system_id = $system_id;
	}

	/**
	 * Setzt das Session Timeout
	 */
	public function setSessiontime($session_duration)
	{
		$this->session_duration = $session_duration;
	}

	/**
	 * Setzt den Login und das Password
	 */
	public function setLogin($usr_login, $usr_psw)
	{
		$this->usr_login = $usr_login;
		$this->usr_psw   = $this->encryptPassword($usr_psw);
	}

	/**
	 * Markiert die aktuelle Session als ausgeloggt
	 */
	public function setLogout($logout)
	{
		$this->logout = (boolean) $logout;
	}

	/**
	 * Prüft, ob die aktuelle Session ausgeloggt ist
	 */
	public function isLoggedOut()
	{
		return $this->logout;
	}

	/**
	 * Setzt den UserQuery
	 *
	 * Dieser wird benutzt, um einen bereits eingeloggten User
	 * im Verlauf seines Aufenthaltes auf der Webseite zu verifizieren
	 */
	public function setUserquery($user_query)
	{
		$this->user_query = $user_query;
	}

	/**
	 * Setzt den LoginQuery
	 *
	 * Dieser wird benutzt, um den eigentlichne Loginvorgang durchzuführen.
	 * Hier wird das eingegebene Password und der Login eingesetzt.
	 */
	public function setLoginquery($login_query)
	{
		$this->login_query = $login_query;
	}

	/**
	 * Setzt den Namen der Spalte, der die User-Id enthält
	 */
	public function setUserID($uid)
	{
		$this->uid = $uid;
	}

	/**
	 * Setzt einen Meldungstext
	 */
	public function setMessage($message)
	{
		$this->message = $message;
	}

	/**
	 * Prüft die mit setLogin() und setPassword() gesetzten Werte
	 * anhand des LoginQueries/UserQueries und gibt den Status zurück.
	 *
	 * @return boolean  true bei Erfolg, sonst false
	 */
	public function checkLogin()
	{
		global $REX, $I18N;

		if (!is_object($I18N)) $I18N = rex_create_lang();

		// wenn logout dann header schreiben und auf error seite verweisen

		$ok = false;

		if (!$this->logout) {
			// LoginStatus: 0 = noch checken, 1 = ok, -1 = not ok
			// checkLogin() schonmal ausgeführt? Gecachte Ausgabe erlaubt?
			
			if ($this->cache) {
				if ($this->login_status > 0) return true;
				if ($this->login_status < 0) return false;
			}

			if (!empty($this->usr_login)) {
				// wenn login daten eingegeben dann checken
				// auf error seite verweisen und message schreiben

				$this->USER = new rex_login_sql($this->DB);
				$USR_LOGIN  = $this->usr_login;
				$USR_PSW    = $this->usr_psw;
				$query      = str_replace('USR_LOGIN', $this->usr_login, $this->login_query);
				$query      = str_replace('USR_PSW', $this->usr_psw, $query);

				$this->USER->setQuery($query);
				
				if ($this->USER->getRows() == 1) {
					$ok = true;
					$this->setSessionVar('UID', $this->USER->getValue($this->uid));
					$this->sessionFixation();
				}
				else {
					$this->message = $I18N->msg('login_error', '<strong>'.$REX['RELOGINDELAY'].'</strong>');
					$this->setSessionVar('UID', '');
				}
			}
			elseif ($this->getSessionVar('UID') != '') {
				// wenn kein login und kein logout dann nach sessiontime checken
				// message schreiben und falls falsch auf error verweisen

				$this->USER = new rex_login_sql($this->DB);
				$query      = str_replace('USR_UID', $this->getSessionVar('UID'), $this->user_query);

				$this->USER->setQuery($query);
				
				if ($this->USER->getRows() == 1) {
					if (($this->getSessionVar('STAMP') + $this->session_duration) > time()) {
						$ok = true;
						$this->setSessionVar('UID', $this->USER->getValue($this->uid));
					}
					else {
						$this->message = $I18N->msg('login_session_expired');
					}
				}
				else {
					$this->message = $I18N->msg('login_user_not_found');
				}
			}
			else {
				$this->message = $I18N->msg('login_welcome');
				$ok = false;
			}
		}
		else {
			$this->message = $I18N->msg('login_logged_out');
			$this->setSessionVar('UID', '');
		}

		if ($ok) {
			// wenn alles ok dann REX[UID][system_id) schreiben
			$this->setSessionVar('STAMP', time());
		}
		else {
			// wenn nicht, dann UID löschen und error seite
			$this->setSessionVar('STAMP', '');
			$this->setSessionVar('UID', '');
		}

		$this->login_status = $ok ? 1 : -1;
		return $ok;
	}

	/**
	 * Gibt einen Benutzer-spezifischen Wert zurück
	 */
	public function getValue($value, $default = NULL)
	{
		return $this->USER ? $this->USER->getValue($value) : $default;
	}

	/**
	 * Setzt eine Passwort-Funktion
	 *
	 * @deprecated  Zum Hashen von Passwörtern sollte immer
	 *              sly_Service_User::hashPassword() verwendet werden.
	 */
	public function setPasswordFunction($pswfunc)
	{
		/* nichts tun */
	}

	/**
	 * Verschlüsselt den übergebnen String, falls eine Passwort-Funktion gesetzt ist.
	 *
	 * @deprecated  Zum Hashen von Passwörtern sollte immer
	 *              sly_Service_User::hashPassword() verwendet werden.
	 */
	public function encryptPassword($psw)
	{
		$service = sly_Service_Factory::getService('User');
		return $service->hashPassword($psw);
	}

	/**
	 * Setzte eine Session-Variable
	 */
	public function setSessionVar($varname, $value)
	{
		$_SESSION[$this->system_id][$varname] = $value;
	}

	/**
	 * Gibt den Wert einer Session-Variable zurück
	 */
	public function getSessionVar($varname, $default = '')
	{
		if (SLY_IS_TESTING) {
			if ($varname == 'UID')   return SLY_TESTING_USER_ID;
			if ($varname == 'STAMP') return time()-10; // vor 10 Sekunden
		}
		
		if (isset($_SESSION[$this->system_id][$varname])) {
			return $_SESSION[$this->system_id][$varname];
		}

		return $default;
	}

	/**
	 * Session fixation
	 */
	public function sessionFixation()
	{
		if (version_compare(phpversion(), '5.1.0', '>=')) {
			session_regenerate_id(true);
		}
		elseif (function_exists('session_regenerate_id')) {
			session_regenerate_id();
		}
	}
}
