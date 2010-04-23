<?php
class sly_Authorisation {

	private static $provider;
	
	public static function setAuthorisationProvider(sly_AuthorisationProvider $provider){
		self::$provider = $provider;
	}
	
	



}