<?php
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