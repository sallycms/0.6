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

	// re-set the values if the user profile has no value (meaining 'default')
	if (empty($locale))   $locale   = sly_Core::getDefaultLocale();
	if (empty($timezone)) $timezone = sly_Core::getTimezone();
}

// set the i18n object
$i18n = new sly_I18N($locale, SLY_SALLYFOLDER.'/backend/lang');
sly_Core::setI18N($i18n);

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

if (is_null($user) && !$isSetup) {
	sly_Controller_Base::setCurrentPage('login');
}

// leave the index.php when only unit testing the API
if (SLY_IS_TESTING) return;

try {
	// get contoller and dispatch
	$controller = sly_Controller_Base::factory();

	if ($controller === null) {
		throw new sly_Controller_Exception(t('unknown_page'), 404);
	}

	print $controller->dispatch();
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
