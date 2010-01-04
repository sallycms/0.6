<?php
/**
 * Bindet nötige Klassen/Funktionen ein
 * @package redaxo4
 * @version svn:$Id$
 */

// Nötige Konstanten (aus class.rex_list.inc.php)
define('REX_LIST_OPT_SORT', 0);

function __autoload($className) {
	global $REX;
	
	static $classes = array(
		'i18n'                              => 'i18n',
		'OOAddon'                           => 'ooaddon',
		'OOArticle'                         => 'ooarticle',
		'OOArticleSlice'                    => 'ooarticleslice',
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
		'rex_formatter'                     => 'rex_formatter',
		'rex_list'                          => 'rex_list',
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
		'rex_template'                      => 'rex_template',
		'rex_var'                           => 'rex_var'
	);
	
	if (isset($classes[$className])) {
		require_once $REX['INCLUDE_PATH'].'/classes/class.'.$classes[$className].'.inc.php';
	}
	elseif (in_array($className, $REX['VARIABLES'])) {
		$index = array_search($className, $REX['VARIABLES']);
		require_once $REX['INCLUDE_PATH'].'/classes/variables/class.'.$className.'.inc.php';
		$REX['VARIABLES'][$index] = new $className();
	}
	else {
		rex_register_extension_point('__AUTOLOAD', $className);
	}
}

// Funktionen

require_once $REX['INCLUDE_PATH'].'/functions/function_rex_time.inc.php';
require_once $REX['INCLUDE_PATH'].'/functions/function_rex_globals.inc.php';
require_once $REX['INCLUDE_PATH'].'/functions/function_rex_client_cache.inc.php';
require_once $REX['INCLUDE_PATH'].'/functions/function_rex_url.inc.php';
require_once $REX['INCLUDE_PATH'].'/functions/function_rex_extension.inc.php';
require_once $REX['INCLUDE_PATH'].'/functions/function_rex_addons.inc.php';
require_once $REX['INCLUDE_PATH'].'/functions/function_rex_plugins.inc.php';
require_once $REX['INCLUDE_PATH'].'/functions/function_rex_other.inc.php';

if ($REX['REDAXO']) {
	require_once $REX['INCLUDE_PATH'].'/functions/function_rex_title.inc.php';
	require_once $REX['INCLUDE_PATH'].'/functions/function_rex_generate.inc.php';
	require_once $REX['INCLUDE_PATH'].'/functions/function_rex_mediapool.inc.php';
	require_once $REX['INCLUDE_PATH'].'/functions/function_rex_structure.inc.php';
}
