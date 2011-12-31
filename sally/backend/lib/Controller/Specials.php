<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Specials extends sly_Controller_Backend {
	protected $warning;
	protected $info;

	protected function init() {
		$subline = array(
			array('',          t('main_preferences')),
			array('languages', t('languages'))
		);

		// add subpages

		$navigation = sly_Core::getNavigation();
		$specials   = $navigation->get('specials', 'system');

		$specials->addSubpage('', t('main_preferences'));
		$specials->addSubpage('languages', t('languages'));

		// show error log when using the original system error handler
		// (that's the only case when we can ensure that we're able to parse the error log)

		if (!sly_Core::isDeveloperMode()) {
			$handler = sly_Core::getErrorHandler();

			if (get_class($handler) === 'sly_ErrorHandler_Production') {
				$specials->addSubpage('errorlog', t('errorlog'));
				$subline[] = array('errorlog',  t('errorlog'));
			}
		}

		// allow addOns to extend the system page
		$subline = sly_Core::dispatcher()->filter('SLY_SPECIALS_MENU', $subline, array('page' => $specials));

		$layout = sly_Core::getLayout();
		$layout->pageHeader(t('specials'), $subline);
	}

	protected function index() {
		print $this->render('specials/index.phtml');
	}

	protected function clearcache() {
		$this->info = rex_generateAll();
		$this->index();
	}

	protected function update() {
		$startArticle    = sly_post('start_article',    'int');
		$notFoundArticle = sly_post('notfound_article', 'int');
		$defaultClang    = sly_post('default_clang',    'int');
		$defaultType     = sly_post('default_type',     'string');
		$developerMode   = sly_post('developer_mode',   'string');
		$backendLocale   = sly_post('backend_locale',   'string');
		$projectName     = sly_post('projectname',      'string');
		$cachingStrategy = sly_post('caching_strategy', 'string');
		$timezone        = sly_post('timezone',         'string');

		// Änderungen speichern

		$conf = sly_Core::config();
		$this->warning = array();

		if (sly_Util_Article::exists($startArticle)) {
			$conf->set('START_ARTICLE_ID', $startArticle);
		}
		else {
			$this->warning[] = t('settings_invalid_sitestart_article');
		}

		if (sly_Util_Article::exists($notFoundArticle)) {
			$conf->set('NOTFOUND_ARTICLE_ID', $notFoundArticle);
		}
		else {
			$this->warning[] = t('settings_invalid_notfound_article').'<br />';
		}

		if (sly_Util_Language::exists($defaultClang)) {
			$conf->set('DEFAULT_CLANG_ID', $defaultClang);
		}
		else {
			$this->warning[] = t('settings_invalid_sitestart_clang').'<br />';
		}

		// Standard-Artikeltyp

		try {
			$service = sly_Service_Factory::getArticleTypeService();

			if (!empty($defaultType) && !$service->exists($defaultType)) {
				$this->warning[] = t('settings_invalid_default_type').'<br />';
			}
			else {
				$conf->set('DEFAULT_ARTICLE_TYPE', $defaultType);
			}
		}
		catch (Exception $e) {
			$conf->set('DEFAULT_ARTICLE_TYPE', '');
		}

		// Sonstige Einstellungen

		$conf->set('DEVELOPER_MODE', $developerMode === 'true');
		$conf->set('DEFAULT_LOCALE', $backendLocale);
		$conf->set('PROJECTNAME', $projectName);
		$conf->setLocal('CACHING_STRATEGY', $cachingStrategy);

		if (class_exists('DateTimeZone')) {
			$conf->set('TIMEZONE', $timezone);
		}

		$this->info    = t('info_updated');
		$this->warning = implode("<br />\n", $this->warning);

		// notify system
		sly_Core::dispatcher()->notify('SLY_SETTINGS_UPDATED');

		$this->index();
	}

	protected function setup() {
		sly_Core::config()->setLocal('SETUP', true);
		sly_Util_HTTP::redirect('index.php', array(), '', 302);
	}

	protected function checkPermission() {
		$user = sly_Util_User::getCurrentUser();
		return $user && $user->isAdmin();
	}
}
