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

// Nötige Konstanten (aus class.rex_list.inc.php)
//define('REX_LIST_OPT_SORT', 0);

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
var_dump($fullPath);
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
			'OOAddon'                           => 'ooaddon',
			'OOArticle'                         => 'ooarticle',
			'OOCategory'                        => 'oocategory',
			'OOMedia'                           => 'oomedia',
			'OOMediaCategory'                   => 'oomediacategory',
			'OOPlugin'                          => 'ooplugin',
			'OORedaxo'                          => 'ooredaxo',
			'rex_addon'                         => 'rex_addon',
			'rex_article'                       => 'rex_article',
			'rex_form'                          => 'rex_form',
			'rex_form_element'                  => 'rex_form',
			'rex_form_control_element'          => 'rex_form',
			'rex_form_select_element'           => 'rex_form',
			'rex_form_options_element'          => 'rex_form',
			'rex_form_checkbox_element'         => 'rex_form',
			'rex_form_radio_element'            => 'rex_form',
			'rex_form_widget_media_element'     => 'rex_form',
			'rex_form_widget_medialist_element' => 'rex_form',
			'rex_form_widget_linkmap_element'   => 'rex_form',
			'rex_login_sql'                     => 'rex_login',
			'rex_login'                         => 'rex_login',
			'rex_backend_login'                 => 'rex_login',
			'rex_baseManager'                   => 'rex_manager',
			'rex_addonManager'                  => 'rex_manager',
			'rex_pluginManager'                 => 'rex_manager',
			'rex_navigation'                    => 'rex_navigation',
			'rex_select'                        => 'rex_select',
			'rex_category_select'               => 'rex_select',
			'rex_sql'                           => 'rex_sql',
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

sly_Loader::addLoadPath($REX['INCLUDE_PATH'].'/lib');
sly_Loader::addLoadPath($REX['INCLUDE_PATH'].'/controllers', 'sly_Controller_');
sly_Loader::register();

require_once $REX['INCLUDE_PATH'].'/lib/functions.php';

// Funktionen

require_once $REX['INCLUDE_PATH'].'/functions/function_rex_globals.inc.php';
require_once $REX['INCLUDE_PATH'].'/functions/function_rex_client_cache.inc.php';
require_once $REX['INCLUDE_PATH'].'/functions/function_rex_url.inc.php';
require_once $REX['INCLUDE_PATH'].'/functions/function_rex_extension.inc.php';
require_once $REX['INCLUDE_PATH'].'/functions/function_rex_addons.inc.php';
require_once $REX['INCLUDE_PATH'].'/functions/function_rex_plugins.inc.php';
require_once $REX['INCLUDE_PATH'].'/functions/function_rex_other.inc.php';

if ($REX['REDAXO']) {
	require_once $REX['INCLUDE_PATH'].'/functions/function_rex_time.inc.php';
	require_once $REX['INCLUDE_PATH'].'/functions/function_rex_title.inc.php';
	require_once $REX['INCLUDE_PATH'].'/functions/function_rex_generate.inc.php';
	require_once $REX['INCLUDE_PATH'].'/functions/function_rex_mediapool.inc.php';
	require_once $REX['INCLUDE_PATH'].'/functions/function_rex_structure.inc.php';
}
