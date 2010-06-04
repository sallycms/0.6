<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
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
		$startArticle      = sly_post('start_article',       'int');
		$notFoundArticle   = sly_post('notfound_article',    'int');
		$defaultTemplateID = sly_post('default_template_id', 'int');
		$backendLocale     = sly_post('backend_locale',      'string');
		
		$errorEMail = addcslashes(sly_post('error_email', 'string'), '"');
		$server     = addcslashes(sly_post('server', 'string'), '"');
		$serverName = addcslashes(sly_post('servername', 'string'), '"');
		$modRewrite = sly_post('mod_rewrite', 'string');

		
		// Änderungen speichern

		$conf = sly_Core::config();
		
       	if (OOArticle::exists($startArticle)) {
			$conf->set('START_ARTICLE_ID', $startArticle);
		}
		else {
			$this->warning = t('settings_invalid_sitestart_article');
		}

		if (OOArticle::exists($notFoundArticle)) {
			$conf->set('NOTFOUND_ARTICLE_ID', $notFoundArticle);
		}
		else {
			$this->warning .= t('settings_invalid_notfound_article').'<br />';
		}
		
		// Standard-Artikel
		
		$sql = sly_DB_Persistence::getInstance();
		$id  = $sql->fetch('template', 'id', array('id' => $defaultTemplateID));

		if ($id === null && $defaultTemplateID != 0) {
			$this->warning .= t('settings_invalid_default_template').'<br />';
		}
		else {
			$conf->set('DEFAULT_TEMPLATE_ID', $defaultTemplateID);
		}

		//Sonstige Einstellungen

		$conf->setLocal('ERROR_EMAIL', strtolower($errorEMail));
		$conf->set('LANG', $backendLocale);
		$conf->setLocal('SERVER', $server);
		$conf->setLocal('SERVERNAME', $serverName);
		$conf->set('MOD_REWRITE', $modRewrite);

		$this->info = t('info_updated');

		$this->index();
	}
	
	public function setup()
	{
		global $REX, $I18N;
		
		$master_file = $REX['INCLUDE_PATH'].'/master.inc.php';
		$cont        = file_get_contents($master_file);
		$cont        = preg_replace("#^(\\\$REX\['SETUP'\].?=.?)[^;]*#m", '$1true', $cont);
		
		if (file_put_contents($master_file, $cont) !== false) {
			$this->info = t('setup_error1', '<a href="index.php">', '</a>');
		}
		else {
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
