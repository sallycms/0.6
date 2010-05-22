<?php

class sly_Controller_Specials extends sly_Controller_Base
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
		global $SLY, $I18N;
		
		$startArticle      = sly_post('start_article',       'int');
		$notFoundArticle   = sly_post('notfound_article',    'int');
		$defaultTemplateID = sly_post('default_template_id', 'int');
		$backendLocale     = sly_post('backend_locale',      'string');
		
		$errorEMail = addcslashes(sly_post('error_email', 'string'), '"');
		$server     = addcslashes(sly_post('server', 'string'), '"');
		$serverName = addcslashes(sly_post('servername', 'string'), '"');
		$modRewrite = sly_post('mod_rewrite', 'string');

		$SLY['LANG'] = $backendLocale;
		$master_file = $SLY['INCLUDE_PATH'].'/master.inc.php';
		$cont        = file_get_contents($master_file);
		
		// Startartikel
		
		if (OOArticle::exists($startArticle)) {
			$cont = preg_replace("#^(\\\$REX\['START_ARTICLE_ID'\].?=.?)[^;]*#m", '${1}'.$startArticle, $cont);
			$SLY['START_ARTICLE_ID'] = $startArticle;
		}
		else {
			$this->warning = $I18N->msg('settings_invalid_sitestart_article');
		}
		
		// 404-Seite
		
		if (OOArticle::exists($notFoundArticle)) {
			$cont = preg_replace("#^(\\\$REX\['NOTFOUND_ARTICLE_ID'\].?=.?)[^;]*#m", '${1}'.$notFoundArticle, $cont);
			$SLY['NOTFOUND_ARTICLE_ID'] = $notFoundArticle;
		}
		else {
			$this->warning .= $I18N->msg('settings_invalid_notfound_article').'<br />';
		}
		
		// Standard-Artikel
		
		$sql = sly_DB_Persistence::getInstance();
		$id  = $sql->fetch('id', 'template', 'id = ?', $defaultTemplateID);

		if ($id === false && $defaultTemplateID != 0) {
			$this->warning .= $I18N->msg('settings_invalid_default_template').'<br />';
		}
		else {
			$cont = preg_replace("#^(\\\$REX\['DEFAULT_TEMPLATE_ID'\].?=.?)[^;]*#m", '${1}'.$defaultTemplateID, $cont);
			$REX['DEFAULT_TEMPLATE_ID'] = $defaultTemplateID;
		}
		
		// Sonstige Einstellungen

		$cont = preg_replace("#^(\\\$REX\['ERROR_EMAIL'\].?=.?)[^;]*#m", '${1}"'.strtolower($errorEMail).'"', $cont);
		$cont = preg_replace("#^(\\\$REX\['LANG'\].?=.?)[^;]*#m",        '${1}"'.$backendLocale.'"', $cont);
		$cont = preg_replace("#^(\\\$REX\['SERVER'\].?=.?)[^;]*#m",      '${1}"'.$server.'"', $cont);
		$cont = preg_replace("#^(\\\$REX\['SERVERNAME'\].?=.?)[^;]*#m",  '${1}"'.$serverName.'"', $cont);
		$cont = preg_replace("#^(\\\$REX\['MOD_REWRITE'\].?=.?)[^;]*#m", '${1}'.strtolower($modRewrite), $cont);
		
		// Änderungen speichern

		rex_put_file_contents($master_file, $cont);
		$this->info = $I18N->msg('info_updated');

		// Zuweisungen für Wiederanzeige
		
		$REX['MOD_REWRITE'] = $modRewrite === 'TRUE';
		$REX['ERROR_EMAIL'] = $errorEMail;
		$REX['SERVER']      = $server;
		$REX['SERVERNAME']  = $serverName;
		
		$this->index();
	}
	
	public function setup()
	{
		global $SLY, $I18N;
		
		$master_file = $SLY['INCLUDE_PATH'].'/master.inc.php';
		$cont        = file_get_contents($master_file);
		$cont        = preg_replace("#^(\\\$REX\['SETUP'\].?=.?)[^;]*#m", '$1true', $cont);
		
		if (file_put_contents($master_file, $cont) !== false) {
			$this->info = $I18N->msg('setup_error1', '<a href="index.php">', '</a>');
		}
		else {
			$this->warning = $I18N->msg('setup_error2');
		}
		
		$this->index();
	}
	
	public function checkPermission()
	{
		global $REX;
		return !empty($REX['USER']);
	}
}
