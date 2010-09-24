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

// addon/normal page path
$REX['PAGEPATH'] = '';
$REX['PAGES']    = array(); // array(name,addon=1,htmlheader=1)
$REX['PAGE']     = '';
$REX['USER']     = null;
$REX['LOGIN']    = null;

// Setup vorbereiten

if (!SLY_IS_TESTING && $config->get('SETUP')) {
	$REX['LANG']      = 'de_de';
	$REX['LANGUAGES'] = array();

	$requestLang = sly_request('lang', 'string');
	$langpath    = $REX['INCLUDE_PATH'].'/lang';
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

	$REX['PAGES']['setup'] = array(t('setup'), 0, 1);
	$REX['PAGE']           = 'setup';
	$_REQUEST['page']      = 'setup';
}
else {

	// Wir vermeiden es, das Locale hier schon zu setzen, da setlocale() sehr
	// teuer ist und wir es ggf. weiter unten nochmal ändern müssten.

	$I18N = rex_create_lang($REX['LANG'], '', false);

	// Login vorbereiten

	$REX['LOGIN']   = new rex_backend_login($config->get('DATABASE/TABLE_PREFIX').'user');
	$rex_user_login = rex_post('rex_user_login', 'string');  // addslashes()!
	$rex_user_psw   = rex_post('rex_user_psw', 'string');    // addslashes()!

	if (sly_get('page', 'string') == 'login' && sly_get('func', 'string') == 'logout') {
		$loginCheck = false;
	}
	else {
		$REX['LOGIN']->setLogin($rex_user_login);
		$loginCheck = $REX['LOGIN']->checkLogin($rex_user_psw);
	}

	// Login OK / Session gefunden?

	if ($loginCheck === true) {

		// Userspezifische Sprache einstellen, falls gleicher Zeichensatz
		$lang = $REX['LOGIN']->getLanguage();

		if (t('htmlcharset') == rex_create_lang($lang, '', false)->msg('htmlcharset')) {
			$I18N = rex_create_lang($lang);
		}
		else {
			sly_set_locale($lang);
		}

		$REX['USER'] = $REX['LOGIN']->USER;
	}
	else {
		$rex_user_loginmessage = $REX['LOGIN']->message;

		// Fehlermeldung von der Datenbank

		if (is_string($loginCheck)) {
			$rex_user_loginmessage = $loginCheck;
		}

		$REX['PAGES']['login'] = array('login', 0, 1);
		$REX['PAGE']           = 'login';
		$REX['USER']           = null;
		$REX['LOGIN']          = null;

	}
}

// synchronize develop

if (!$config->get('SETUP')) {
	sly_Service_Factory::getService('Template')->refresh();
	sly_Service_Factory::getService('Module')->refresh();
}

// AddOns einbinden

require_once $REX['INCLUDE_PATH'].'/addons.inc.php';

if ($REX['USER']) {
	// Core-Seiten initialisieren
	$REX['PAGES']['profile'] = array(t('profile'), 0, false);
	$REX['PAGES']['credits'] = array(t('credits'), 0, false);

	if ($REX['USER']->isAdmin() || $REX['USER']->hasStructurePerm()) {
		$REX['PAGES']['structure'] = array(t('structure'), 0, false);
		$REX['PAGES']['mediapool'] = array(t('mediapool'), 0, true, 'NAVI' => array('href' =>'#', 'onclick' => 'openMediaPool()', 'class' => ' rex-popup'));
		$REX['PAGES']['linkmap']   = array(t('linkmap'), 0, true);
		$REX['PAGES']['content']   = array(t('content'), 0, false);
	}
	elseif ($REX['USER']->hasPerm('mediapool[]')) {
		$REX['PAGES']['mediapool'] = array(t('mediapool'), 0, true, 'NAVI' => array('href' =>'#', 'onclick' => 'openMediaPool()', 'class' => ' rex-popup'));
	}

	if ($REX['USER']->isAdmin()) {
	  $REX['PAGES']['user']     = array(t('user'), 0, false);
	  $REX['PAGES']['addon']    = array(t('addons'), 0, false);
	  $REX['PAGES']['specials'] = array(t('specials'), 0, false, 'SUBPAGES' => array(array('', t('main_preferences')), array('languages', t('languages'))));
	}

	// AddOn-Seiten initialisieren
	$addonService = sly_Service_Factory::getService('AddOn');

	foreach ($addonService->getAvailableAddons() as $addon) {
		$link = '';
		$perm = $addonService->getProperty($addon, 'perm', '');
		$page = $addonService->getProperty($addon, 'page', '');

		if (!empty($page) && (empty($perm) || $REX['USER']->hasPerm($perm) || $REX['USER']->isAdmin())) {
			$name  = $addonService->getProperty($addon, 'name', '');
			$name  = rex_translate($name);
			$popup = $addonService->getProperty($addon, 'popup', false);
			$REX['PAGES'][strtolower($addon)] = array($name, 1, $popup, $page);
		}
	}

	// Startseite ermitteln

	$REX['USER']->pages = $REX['PAGES'];
	$REX['PAGE']        = sly_Controller_Base::getPage(!empty($rex_user_login));

	// Login OK -> Redirect auf Startseite

	if (!empty($rex_user_login)) {
		// if relogin, forward to previous page
		$referer = sly_post('referer', 'string', false);

		if ($referer && !sly_startsWith(basename($referer), 'index.php?page=login')) {
			$url = $referer;
			$msg = 'Sie werden zu Ihrer <a href="'.$referer.'">vorherigen Seite</a> weitergeleitet.';
		}
		else {
			$url = 'index.php?page='.urlencode($REX['PAGE']);
			$msg = 'Sie werden zur <a href="'.$url.'">Startseite</a> weitergeleitet.';
		}

		header('Location: '.$url);
		exit($msg);
	}
}

// Seite gefunden. AddOns benachrichtigen

rex_register_extension_point('PAGE_CHECKED', $REX['PAGE'], array('pages' => $REX['PAGES']), true);

// Im Testmodus verlassen wir das Script jetzt.

if (SLY_IS_TESTING) return;

// Gewünschte Seite einbinden
$forceLogin = !$REX['SETUP'] && !$REX['USER'];
$controller = sly_Controller_Base::factory($forceLogin ? 'login' : null, $forceLogin ? 'index' : null);

if ($controller !== null) {
	try {
		$CONTENT = $controller->dispatch();
	}
	catch (Exception $e) {
		// View laden
		$layout = sly_Core::getLayout('Sally');
		$layout->openBuffer();

		if($e instanceof sly_Authorisation_Exception) {
			rex_title('Sicherheitsverletzung');
		}elseif($e instanceof sly_Controller_Exception){
			rex_title('Controller-Fehler');
		}else {
			rex_title('Unerwartete Ausnahme');
		}

		print rex_warning($e->getMessage());
		$layout->closeBuffer();
		$CONTENT = $layout->render();
	}
}
else {
	// View laden
	$layout = sly_Core::getLayout('Sally');
	$layout->openBuffer();

	if (!empty($REX['PAGES'][$REX['PAGE']]['PATH'])) { // If page has a new/overwritten path
		require $REX['PAGES'][$REX['PAGE']]['PATH'];
	}
	elseif ($REX['PAGES'][strtolower($REX['PAGE'])][1]) { // Addon Page
		require $REX['INCLUDE_PATH'].'/addons/'. $REX['PAGE'] .'/pages/index.inc.php';
	}
	else { // Core Page
		require $REX['INCLUDE_PATH'].'/pages/'.$REX['PAGE'].'.inc.php';
	}

	$layout->closeBuffer();
	$CONTENT = $layout->render();
}

rex_send_article(null, $CONTENT, 'backend', true);
