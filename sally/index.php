<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

define('IS_SALLY', true);
define('IS_SALLY_BACKEND', true);

ob_start();
ob_implicit_flush(0);

if (!defined('SLY_IS_TESTING')) {
	define('SLY_IS_TESTING', false);
}

unset($REX);

$REX['REDAXO']      = true;
$REX['SALLY']       = true;
$REX['HTDOCS_PATH'] = SLY_IS_TESTING ? SLY_TESTING_ROOT : '../';

require 'include/master.inc.php';

if (!SLY_IS_TESTING) sly_Util_Session::start();

// addon/normal page path
$REX['PAGEPATH'] = '';
$REX['PAGE']     = '';
$REX['USER']     = null;

$navigation = sly_Core::getNavigation();

// Setup vorbereiten

if (!SLY_IS_TESTING && $config->get('SETUP')) {
	$REX['LANG']      = 'de_de';
	$REX['LANGUAGES'] = array();

	$requestLang = sly_request('lang', 'string');
	$langpath    = SLY_INCLUDE_PATH.'/lang';
	$languages   = glob($langpath.'/*.lang');

	if ($languages) {
		foreach ($languages as $language) {
			$locale = substr(basename($language), 0, -5);
			$REX['LANGUAGES'][] = $locale;

			if ($requestLang == $locale) {
				$REX['LANG'] = $locale;
			}
		}
	}

	$I18N = rex_create_lang($REX['LANG']);

	$navigation->addPage('system', 'setup', false);

	$REX['PAGE']      = 'setup';
	$_REQUEST['page'] = 'setup';
	date_default_timezone_set(@date_default_timezone_get());
}
else {
	$locale = '';
	$timezone = '';
	$REX['USER'] = sly_Service_Factory::getUserService()->getCurrentUser();

	//get user values
	if ($REX['USER'] instanceof sly_Model_User) {
		$locale = $REX['USER']->getBackendLocale();
		$timezone = $REX['USER']->getTimeZone();
	}
	//create $I18N and set locale
	if(empty($locale)) $locale = $config->get('LANG');
	$I18N = rex_create_lang($locale);
	//set timezone
	if(empty($timezone)) $timezone = $config->get('TIMEZONE');
	date_default_timezone_set($timezone);
}

// synchronize develop

if (!$config->get('SETUP')) {
	sly_Service_Factory::getService('Template')->refresh();
	sly_Service_Factory::getService('Module')->refresh();
}

// include AddOns

require_once SLY_INCLUDE_PATH.'/addons.inc.php';

if ($REX['USER']) {
	// Core-Seiten initialisieren

	$navigation->addPage('system', 'profile');
	$navigation->addPage('system', 'credits');

	if ($REX['USER']->isAdmin() || $REX['USER']->hasStructurePerm()) {
		$navigation->addPage('system', 'structure');
		$navigation->addPage('system', 'mediapool', null, true);
		$navigation->addPage('system', 'linkmap', null, true);
		$navigation->addPage('system', 'content');
	}
	elseif ($REX['USER']->hasPerm('mediapool[]')) {
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
	$addonService = sly_Service_Factory::getService('AddOn');

	foreach ($addonService->getAvailableAddons() as $addon) {
		$link = '';
		$perm = $addonService->getProperty($addon, 'perm', '');
		$page = $addonService->getProperty($addon, 'page', '');

		if (!empty($page) && (empty($perm) || $REX['USER']->hasPerm($perm) || $REX['USER']->isAdmin())) {
			$name  = $addonService->getProperty($addon, 'name', '');
			$popup = $addonService->getProperty($addon, 'popup', false);

			$navigation->addPage('addon', strtolower($addon), $name, $popup, $page);
		}
	}

	// Startseite ermitteln

	$REX['PAGE'] = sly_Controller_Base::getPage(!empty($rex_user_login));

	// Login OK -> Redirect auf Startseite

	if (!empty($rex_user_login)) {
		// if relogin, forward to previous page
		$referer = sly_post('referer', 'string', false);

		if ($referer && !sly_startsWith(basename($referer), 'index.php?page=login')) {
			$url = $referer;
			$msg = t('redirect_previous_page', $referer);
		}
		else {
			$url = 'index.php?page='.urlencode($REX['PAGE']);
			$msg = t('redirect_startpage', $url);
		}

		sly_Util_HTTP::redirect($url, array(), $msg);
	}
}

// Seite gefunden. AddOns benachrichtigen

sly_Core::dispatcher()->notify('PAGE_CHECKED', $REX['PAGE']);

// Im Testmodus verlassen wir das Script jetzt.

if (SLY_IS_TESTING) return;

// Gewünschte Seite einbinden
$forceLogin = !$REX['SETUP'] && !$REX['USER'];
$controller = sly_Controller_Base::factory($forceLogin ? 'login' : null, $forceLogin ? 'index' : null);

// View laden
$layout = sly_Core::getLayout('Sally');
$layout->openBuffer();

try {
	if ($controller !== null) {
		$CONTENT = $controller->dispatch();
	}
	else {
		$filename = '';
		$curGroup = $navigation->getActiveGroup();

		if ($curGroup && $curGroup->getName() == 'addon') {
			$curPage  = $navigation->getActivePage();
			$filename = SLY_INCLUDE_PATH.'/addons/'.$curPage->getName().'/pages/index.inc.php';
		}
		else {
			$filename = SLY_INCLUDE_PATH.'/pages/'.$REX['PAGE'].'.inc.php';
		}

		if (empty($filename) || !file_exists($filename)) {
			throw new sly_Controller_Exception(t('unknown_page'));
		}

		include $filename;
		$layout->closeBuffer();
		$CONTENT = $layout->render();
	}
}
catch (Exception $e) {
	if ($e instanceof sly_Authorisation_Exception) {
		$layout->pageHeader(t('security_violation'));
	}
	elseif ($e instanceof sly_Controller_Exception) {
		$layout->pageHeader(t('controller_error'));
	}
	else {
		$layout->pageHeader(t('unexpected_exception'));
	}

	print rex_warning($e->getMessage());
	$layout->closeBuffer();
	$CONTENT = $layout->render();
}

rex_send_article(null, $CONTENT, 'backend', true);
