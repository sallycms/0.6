<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Specials extends sly_Controller_Sally
{
	protected $warning;
	protected $info;

	public function init()
	{
		global $I18N;

		$subline = array(
			array('',          $I18N->msg('main_preferences')),
			array('languages', $I18N->msg('languages'))
		);

		rex_title($I18N->msg('specials'), $subline);
	}

	public function index()
	{
		$this->render('views/specials/index.phtml');
	}

	public function clearcache()
	{
		$this->info = rex_generateAll();
		$this->index();
	}

	public function update()
	{
		$startArticle    = sly_post('start_article',    'int');
		$notFoundArticle = sly_post('notfound_article', 'int');
		$defaultTemplate = sly_post('default_template', 'string');
		$backendLocale   = sly_post('backend_locale',   'string');
		$errorEMail      = sly_post('error_email',      'string');
		$server          = sly_post('server',           'string');
		$serverName      = sly_post('servername',       'string');
		$modRewrite      = sly_post('mod_rewrite',      'string');

		// Änderungen speichern

		$conf = sly_Core::config();
		$this->warning = array();

		if (OOArticle::exists($startArticle)) {
			$conf->set('START_ARTICLE_ID', $startArticle);
		}
		else {
			$this->warning[] = t('settings_invalid_sitestart_article');
		}

		if (OOArticle::exists($notFoundArticle)) {
			$conf->set('NOTFOUND_ARTICLE_ID', $notFoundArticle);
		}
		else {
			$this->warning[] = t('settings_invalid_notfound_article').'<br />';
		}

		// Standard-Artikel

		$service = sly_Service_Factory::getService('Template');

		if (!empty($defaultTemplate) && !$service->exists($defaultTemplate)) {
			$this->warning[] = t('settings_invalid_default_template').'<br />';
		}
		else {
			$conf->set('DEFAULT_TEMPLATE', $defaultTemplate);
		}

		//Sonstige Einstellungen

		$conf->setLocal('ERROR_EMAIL', strtolower($errorEMail));
		$conf->set('LANG', $backendLocale);
		$conf->setLocal('SERVER', $server);
		$conf->setLocal('SERVERNAME', $serverName);
		$conf->set('MOD_REWRITE', $modRewrite === 'true');

		$this->info    = t('info_updated');
		$this->warning = implode("<br />\n", $this->warning);

		$this->index();
	}

	public function setup()
	{
		try{
			sly_Core::config()->setLocal('SETUP', true);
			$this->info = t('setup_error1', '<a href="index.php">', '</a>');
		}catch(Exception $e){
			$this->warning = t('setup_error2');
		}

		$this->index();
	}

	public function checkPermission()
	{
		global $REX;
		return !empty($REX['USER']);
	}
}
