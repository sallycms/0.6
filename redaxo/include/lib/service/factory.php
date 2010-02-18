<?php
/*
 * Copyright (c) 2009, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */
 
 abstract class Service_Factory {
  	
  	private static $services = array();
  	
  	public static function getService($modelName){
  		if(!isset(self::$services[$modelName])){
  			$serviceName = 'Service_'.$modelName;
  			$service = new $serviceName();
  			if(!$service instanceof Service_Base){
  				throw new Exception('Service '.$serviceName.' is no inheriting Class of Service_Base.');
  			}
  			self::$services[$modelName] = $service;
  		}
  		return self::$services[$modelName];
  	}
 }