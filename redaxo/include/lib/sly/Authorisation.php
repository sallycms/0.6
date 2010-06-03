<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

class sly_Authorisation {

	private static $provider;
	
	public static function setAuthorisationProvider(sly_Authorisation_Provider $provider){
		self::$provider = $provider;
	}
	
	public static function hasPermission($userId, $context, $permission, $objectId = null){
		if(!self::$provider){
			return true;
		}else {
			try {
				return $provider->hasPermission($userId, $context, $permission, $objectId);
			}catch(Exception $e){
				trigger_error('An error occured in authorisationprovider, for security reasons permission was denied.', E_USER_WARNING);
				return false;
			}
		}
	}

}