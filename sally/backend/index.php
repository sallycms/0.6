<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

define('IS_SALLY', true);
define('IS_SALLY_BACKEND', true);
define('SLY_START_TIME', microtime(true));

if (!defined('SLY_IS_TESTING')) {
	define('SLY_IS_TESTING', false);
}

// Only remove $REX if we're not in test mode, or else we have no global $REX
// (this file is included in PHPUnit method context) and the system will crash
// and burrrrn.

if (SLY_IS_TESTING) {
	global $REX;
}
else {
	ob_start();
	ob_implicit_flush(0);
	unset($REX);
}

define('SLY_HTDOCS_PATH', SLY_IS_TESTING ? SLY_TESTING_ROOT : '../');

require '../core/master.inc.php';

// add backend app
sly_Loader::addLoadPath(SLY_SALLYFOLDER.'/backend/lib/', 'sly_');

// only start session if not running unit tests
if (!SLY_IS_TESTING) sly_Util_Session::start();

// set current page and user
$REX['PAGE'] = '';
$REX['USER'] = null;

// Setup vorbereiten

if (!SLY_IS_TESTING && $config->get('SETUP')) {
	$locale        = $config->get('LANG');
	$locales       = sly_I18N::getLocales(SLY_SALLYFOLDER.'/backend/lang');
	$requestLocale = sly_request('lang', 'string');
	$timezone      = @date_default_timezone_get();

	if (in_array($requestLocale, $locales)) {
		$locale = $requestLocale;
	}

	// force setup page
	$REX['PAGE']      = 'setup';
	$_REQUEST['page'] = 'setup';
}
else {
	$locale      = '';
	$timezone    = '';
	$REX['USER'] = sly_Util_User::getCurrentUser();

	// get user values
	if ($REX['USER'] instanceof sly_Model_User) {
		$locale   = $REX['USER']->getBackendLocale();
		$timezone = $REX['USER']->getTimeZone();
	}

	// re-set the values if the user profile has no value (meaining 'default')
	if (empty($locale))   $locale   = $config->get('LANG');
	if (empty($timezone)) $timezone = $config->get('TIMEZONE');
}

// set the i18n object
$i18n = new sly_I18N($locale, SLY_SALLYFOLDER.'/backend/lang');
sly_Core::setI18N($i18n);

// set navigation object (after i18n has been initialized!)
$navigation = sly_Core::getNavigation();

// add setup page to make the permission system work and allow access to the controller
if (!SLY_IS_TESTING && $config->get('SETUP')) {
	$navigation->addPage('system', 'setup', false);
}

// set timezone
date_default_timezone_set($timezone);

$layout = sly_Core::getLayout('Backend');

// instantiate asset service before addons are loaded to make sure the scaffold css processing is first
$assetService = sly_Service_Factory::getAssetService();

// include AddOns
sly_Core::loadAddons();

// register listeners
sly_Core::registerListeners();

// synchronize develop

if (!$config->get('SETUP') && $config->get('DEVELOPER_MODE')) {
	sly_Service_Factory::getTemplateService()->refresh();
	sly_Service_Factory::getModuleService()->refresh();
	sly_Service_Factory::getAssetService()->validateCache();
}

// Asset-Processing, sofern Assets benötigt werden
sly_Service_Factory::getAssetService()->process();

if ($REX['USER']) {
	// Core-Seiten initialisieren

	$navigation->addPage('system', 'profile');
	$navigation->addPage('system', 'credits');

	if ($REX['USER']->isAdmin() || $REX['USER']->hasStructureRight()) {
		$navigation->addPage('system', 'structure');
		$navigation->addPage('system', 'mediapool', null, true);
		$navigation->addPage('system', 'linkmap', null, true);
		$navigation->addPage('system', 'content');
	}
	elseif ($REX['USER']->hasRight('mediapool[]')) {
		$navigation->addPage('system', 'mediapool', null, true);
	}

	if ($REX['USER']->isAdmin()) {
		$navigation->addPage('system', 'user');
		$navigation->addPage('system', 'addon', 'translate:addons', false);

		$specials = $navigation->createPage('specials');
		$specials->addSubpage('', t('main_preferences'));
		$specials->addSubpage('languages', t('languages'));
		$navigation->addPageObj('system', $specials);
	}

	// AddOn-Seiten initialisieren
	$addonService  = sly_Service_Factory::getAddOnService();
	$pluginService = sly_Service_Factory::getPluginService();

	foreach ($addonService->getAvailableAddons() as $addon) {
		$link = '';
		$perm = $addonService->getProperty($addon, 'perm', '');
		$page = $addonService->getProperty($addon, 'page', '');

		if (!empty($page) && (empty($perm) || $REX['USER']->hasRight($perm) || $REX['USER']->isAdmin())) {
			$name  = $addonService->getProperty($addon, 'name', '');
			$popup = $addonService->getProperty($addon, 'popup', false);

			$navigation->addPage('addon', strtolower($addon), $name, $popup, $page);
		}

		foreach ($pluginService->getAvailablePlugins($addon) as $plugin) {
			$pluginArray = array($addon, $plugin);
			$link        = '';
			$perm        = $pluginService->getProperty($pluginArray, 'perm', '');
			$page        = $pluginService->getProperty($pluginArray, 'page', '');

			if (!empty($page) && (empty($perm) || $REX['USER']->hasRight($perm) || $REX['USER']->isAdmin())) {
				$name  = $pluginService->getProperty($pluginArray, 'name', '');
				$popup = $pluginService->getProperty($pluginArray, 'popup', false);

				$navigation->addPage('addon', strtolower($plugin), $name, $popup, $page);
			}
		}
	}

	// Startseite ermitteln

	$REX['PAGE'] = sly_Controller_Base::getPage();
}
else {
	$REX['PAGE'] = $REX['SETUP'] ? 'setup' : 'login';
}

// Seite gefunden. AddOns benachrichtigen

sly_Core::dispatcher()->notify('PAGE_CHECKED', $REX['PAGE']);

// Im Testmodus verlassen wir das Script jetzt.

if (SLY_IS_TESTING) return;

// Gewünschte Seite einbinden
$forceLogin = !$REX['SETUP'] && !$REX['USER'];
$controller = sly_Controller_Base::factory($forceLogin ? 'login' : null, $forceLogin ? 'index' : null);

try {
	if ($controller !== null) {
		$CONTENT = $controller->dispatch();
	}
	else {
		// View laden
		$layout->openBuffer();

		$filename = '';
		$curGroup = $navigation->getActiveGroup();

		if ($curGroup && $curGroup->getName() == 'addon') {
			$curPage  = $navigation->getActivePage();
			$filename = SLY_ADDONFOLDER.'/'.$curPage->getName().'/pages/index.inc.php';
		}

		if (empty($filename) || !file_exists($filename)) {
			throw new sly_Controller_Exception(t('unknown_page'), 404);
		}

		include $filename;
		$layout->closeBuffer();
		$CONTENT = $layout->render();
	}
}
catch (Exception $e) {
	$layout->closeAllBuffers();
	$layout->openBuffer();

	if ($e instanceof sly_Authorisation_Exception) {
		header('HTTP/1.0 403 Forbidden');
		$layout->pageHeader(t('security_violation'));
	}
	elseif ($e instanceof sly_Controller_Exception) {
		if ($e->getCode() === 404) {
			header('HTTP/1.0 404 Not Found');
		}

		$layout->pageHeader(t('controller_error'));
	}
	else {
		header('HTTP/1.0 500 Internal Server Error');
		$layout->pageHeader(t('unexpected_exception'));
	}

	print rex_warning($e->getMessage());
	$layout->closeBuffer();
	$CONTENT = $layout->render();
}

rex_send_article(null, $CONTENT, 'backend');
