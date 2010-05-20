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

class sly_Loader
{
	protected static $loadPaths = array();
	
	public static function addLoadPath($path, $hiddenPrefix = '')
	{
		$path = realpath($path);
		
		if ($path) {
			$path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
			self::$loadPaths[$path] = $hiddenPrefix;
		}
	}
	
	public static function register()
	{
		if (function_exists('spl_autoload_register')) {
			spl_autoload_register(array('sly_Loader', 'loadClass'));
		}
		else {
			function __autoload($className)
			{
				self::loadClass($className);
			}
		}
	}
	
	public static function loadClass($className)
	{
		global $SLY, $REX; // $REX für Code, der direkt beim Include ausgeführt wird.
		
		if (class_exists($className, false)) {
			return true;
		}
		
		$file  = str_replace('_', DIRECTORY_SEPARATOR, $className).'.php';
		$found = false;
		
		foreach (self::$loadPaths as $path => $prefix) {
			$shortClass = strlen($className) > strlen($prefix) ? substr($className, strlen($prefix)) : $className;
			$file       = str_replace('_', DIRECTORY_SEPARATOR, $shortClass).'.php';
			$fullPath   = $path.DIRECTORY_SEPARATOR.$file;
			if (is_file($fullPath)) {
				$found = true;
				break;
			}
		}
		
		if ($found) {
			include_once $fullPath;
			return $fullPath;
		}
		
		// Fallback auf alte REDAXO-Klassen
		
		static $classes = array(
			'OOAddon'         => 'ooaddon',
			'OOArticle'       => 'ooarticle',
			'OOCategory'      => 'oocategory',
			'OOMedia'         => 'oomedia',
			'OOMediaCategory' => 'oomediacategory',
			'OOPlugin'        => 'ooplugin',
			'OORedaxo'        => 'ooredaxo'
		);
		
		if (file_exists($SLY['INCLUDE_PATH'].'/classes/'.strtolower($className).'.php')) {
			include_once $SLY['INCLUDE_PATH'].'/classes/'.strtolower($className).'.php';
		}
		elseif (isset($classes[$className])) {
			include_once $SLY['INCLUDE_PATH'].'/classes/class.'.$classes[$className].'.inc.php';
		}
		else {
			rex_register_extension_point('__AUTOLOAD', $className);
		}
	}
}
