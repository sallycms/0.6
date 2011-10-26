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

if (!defined('SLY_IS_TESTING')) define('SLY_IS_TESTING', false);

define('SLY_HTDOCS_PATH', SLY_IS_TESTING ? SLY_TESTING_ROOT : '../');

// start output buffering
if (!SLY_IS_TESTING) {
	ob_start();
	ob_implicit_flush(0);
}

// load core
require '../core/master.php';

// add backend app
sly_Loader::addLoadPath(SLY_SALLYFOLDER.'/backend/lib/', 'sly_');

// only start session if not running unit tests
if (!SLY_IS_TESTING) sly_Util_Session::start();

// prepare setup
$isSetup = $config->get('SETUP');

if (!SLY_IS_TESTING && $isSetup) {
	$locale        = sly_Core::getDefaultLocale();
	$locales       = sly_I18N::getLocales(SLY_SALLYFOLDER.'/backend/lang');
	$requestLocale = sly_request('lang', 'string');
	$timezone      = @date_default_timezone_get();
	$user          = null;

	if (in_array($requestLocale, $locales)) {
		$locale = $requestLocale;
	}

	// force setup page
	sly_Controller_Base::setCurrentPage('setup');
}
else {
	$locale   = '';
	$timezone = '';
	$user     = sly_Util_User::getCurrentUser();

	// get user values
	if ($user instanceof sly_Model_User) {
		$locale   = $user->getBackendLocale();
		$timezone = $user->getTimeZone();
	}

	// re-set the values if the user profile has no value (meaning 'default')
	if (empty($locale))   $locale   = sly_Core::getDefaultLocale();
	if (empty($timezone)) $timezone = sly_Core::getTimezone();
}

// set the i18n object
$i18n = new sly_I18N($locale, SLY_SALLYFOLDER.'/backend/lang');
sly_Core::setI18N($i18n);

// set navigation object (after i18n has been initialized!)
$navigation = sly_Core::getNavigation();

// add setup page to make the permission system work and allow access to the controller
if (!SLY_IS_TESTING && $isSetup) {
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

if (!$isSetup && sly_Core::isDeveloperMode()) {
	sly_Service_Factory::getTemplateService()->refresh();
	sly_Service_Factory::getModuleService()->refresh();
	sly_Service_Factory::getAssetService()->validateCache();
}

// Asset-Processing, sofern Assets benötigt werden
sly_Service_Factory::getAssetService()->process();

if ($user) {
	$isAdmin = $user->isAdmin();

	// Core-Seiten initialisieren

	$navigation->addPage('system', 'profile');
	$navigation->addPage('system', 'credits');
	if ($user->hasStructureRight()) {
		$hasClangPerm = $isAdmin || count($user->getAllowedCLangs()) > 0;

		if ($hasClangPerm) $navigation->addPage('system', 'structure');
		$navigation->addPage('system', 'mediapool', null, true);
		if ($hasClangPerm) $navigation->addPage('system', 'linkmap', null, true);
		if ($hasClangPerm) $navigation->addPage('system', 'content');
	}
	elseif ($user->hasRight('mediapool[]')) {
		$navigation->addPage('system', 'mediapool', null, true);
	}

	if ($isAdmin) {
		$navigation->addPage('system', 'user');
		$navigation->addPage('system', 'addon', 'translate:addons');
		$navigation->addPage('system', 'specials');
	}

	// AddOn-Seiten initialisieren
	$addonService  = sly_Service_Factory::getAddOnService();
	$pluginService = sly_Service_Factory::getPluginService();

	foreach ($addonService->getAvailableAddons() as $addon) {
		$link = '';
		$perm = $addonService->getProperty($addon, 'perm', '');
		$page = $addonService->getProperty($addon, 'page', '');

		if (!empty($page) && ($isAdmin || empty($perm) || $user->hasRight($perm))) {
			$name  = $addonService->getProperty($addon, 'name', '');
			$popup = $addonService->getProperty($addon, 'popup', false);

			$navigation->addPage('addon', strtolower($addon), $name, $popup, $page);
		}

		foreach ($pluginService->getAvailablePlugins($addon) as $plugin) {
			$pluginArray = array($addon, $plugin);
			$link        = '';
			$perm        = $pluginService->getProperty($pluginArray, 'perm', '');
			$page        = $pluginService->getProperty($pluginArray, 'page', '');

			if (!empty($page) && ($isAdmin || empty($perm) || $user->hasRight($perm))) {
				$name  = $pluginService->getProperty($pluginArray, 'name', '');
				$popup = $pluginService->getProperty($pluginArray, 'popup', false);

				$navigation->addPage('addon', strtolower($plugin), $name, $popup, $page);
			}
		}
	}

	// find best starting page
	sly_Controller_Base::getPage();
}
elseif (!$isSetup) {
	sly_Controller_Base::setCurrentPage('login');
}

// notify addOns about the page to be rendered
$page = sly_Controller_Base::getPage();
sly_Core::dispatcher()->notify('PAGE_CHECKED', $page);

// leave the index.php when only unit testing the API
if (SLY_IS_TESTING) return;

// Gewünschte Seite einbinden
$controller = sly_Controller_Base::factory();

try {
	if ($controller !== null) {
		print $controller->dispatch();
	}
	else {
		throw new sly_Controller_Exception(t('unknown_page'), 404);
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

	print sly_Helper_Message::warn($e->getMessage());
	$layout->closeBuffer();
	$layout->openBuffer();
	print $layout->render();
}

rex_send_article(null, null, 'backend');
