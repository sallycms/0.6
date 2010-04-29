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

function __autoload($className) {
	global $REX;
	
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
	
	$className = str_replace('sly_', '', $className);
	
	if (file_exists($REX['INCLUDE_PATH'].'/classes/'.strtolower($className).'.php')) {
		require_once $REX['INCLUDE_PATH'].'/classes/'.strtolower($className).'.php';
	}
	elseif (isset($classes[$className])) {
		require_once $REX['INCLUDE_PATH'].'/classes/class.'.$classes[$className].'.inc.php';
	}
	elseif (file_exists($REX['INCLUDE_PATH'].'/lib/'.strtolower(str_replace('_', '/', $className)).'.php')){
		require_once $REX['INCLUDE_PATH'].'/lib/'.strtolower(str_replace('_', '/', $className)).'.php';
	}
	else {
		rex_register_extension_point('__AUTOLOAD', $className);
	}
}

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
