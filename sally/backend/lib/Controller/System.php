<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_System extends sly_Controller_Backend implements sly_Controller_Interface {
	protected $warning;
	protected $info;
	protected $init;

	protected function init() {
		if ($this->init) return;
		$this->init = true;

		// add subpages

		$layout = sly_Core::getLayout();
		$layout->pageHeader(t('system'));
	}

	public function indexAction() {
		$this->init();
		print $this->render('system/index.phtml');
	}

	public function clearcacheAction() {
		$this->init();

		// do not call sly_Core::clearCache(), since we want to have fine-grained
		// control over what caches get cleared

		clearstatcache();

		// clear loader cache
		sly_Loader::clearCache();

		// create bootcache
		sly_Util_BootCache::recreate('frontend');
		sly_Util_BootCache::recreate('backend');

		// clear our own data caches
		if ($this->isCacheSelected('sly_core')) {
			sly_Core::cache()->flush('sly', true);
		}

		// sync develop files
		if ($this->isCacheSelected('sly_develop')) {
			sly_Service_Factory::getTemplateService()->refresh();
			sly_Service_Factory::getModuleService()->refresh();
		}

		// clear asset cache
		if ($this->isCacheSelected('sly_asset')) {
			sly_Service_Factory::getAssetService()->clearCache();
		}

		$this->info = sly_Core::dispatcher()->filter('SLY_CACHE_CLEARED', t('delete_cache_message'), array('backend' => true));
		$this->indexAction();
	}

	public function isCacheSelected($name) {
		$caches = sly_postArray('caches', 'string', array());
		return in_array($name, $caches);
	}

	public function updateAction() {
		$this->init();

		$startArticle    = sly_post('start_article',    'int');
		$notFoundArticle = sly_post('notfound_article', 'int');
		$defaultClang    = sly_post('default_clang',    'int');
		$defaultType     = sly_post('default_type',     'string');
		$developerMode   = sly_post('developer_mode',   'string');
		$backendLocale   = sly_post('backend_locale',   'string');
		$projectName     = sly_post('projectname',      'string');
		$cachingStrategy = sly_post('caching_strategy', 'string');
		$timezone        = sly_post('timezone',         'string');

		$keys = array(
			'START_ARTICLE_ID', 'NOTFOUND_ARTICLE_ID', 'DEFAULT_CLANG_ID', 'DEFAULT_ARTICLE_TYPE',
			'DEVELOPER_MODE', 'DEFAULT_LOCALE', 'PROJECTNAME', 'CACHING_STRATEGY', 'TIMEZONE'
		);

		// Änderungen speichern

		$conf = sly_Core::config();
		$this->warning = array();

		foreach ($keys as $key) {
			$originals[$key] = $conf->get($key);
		}

		if (sly_Util_Article::exists($startArticle)) {
			$conf->set('START_ARTICLE_ID', $startArticle);
		}
		elseif ($startArticle > 0) {
			$this->warning[] = t('invalid_start_article_selected');
		}

		if (sly_Util_Article::exists($notFoundArticle)) {
			$conf->set('NOTFOUND_ARTICLE_ID', $notFoundArticle);
		}
		elseif ($notFoundArticle > 0) {
			$this->warning[] = t('invalid_not_found_article_selected').'<br />';
		}

		if (sly_Util_Language::exists($defaultClang)) {
			$conf->set('DEFAULT_CLANG_ID', $defaultClang);
		}
		else {
			$this->warning[] = t('invalid_default_language_selected').'<br />';
		}

		// Standard-Artikeltyp

		try {
			$service = sly_Service_Factory::getArticleTypeService();

			if (!empty($defaultType) && !$service->exists($defaultType)) {
				$this->warning[] = t('invalid_default_articletype_selected').'<br />';
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
		$conf->set('TIMEZONE', $timezone);

		$this->info    = t('configuration_updated');
		$this->warning = implode("<br />\n", $this->warning);

		// notify system
		sly_Core::dispatcher()->notify('SLY_SETTINGS_UPDATED', null, compact('originals'));

		$this->indexAction();
	}

	public function setupAction() {
		$this->init();
		sly_Core::config()->setLocal('SETUP', true);
		sly_Util_HTTP::redirect('index.php', array(), '', 302);
	}

	public function checkPermission($action) {
		$user = sly_Util_User::getCurrentUser();
		return $user && $user->isAdmin();
	}
}
