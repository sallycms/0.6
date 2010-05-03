<?php

define('IS_SALLY', true);

/**
 * @package redaxo4
 */

ob_start();
ob_implicit_flush(0);

// ----------------- MAGIC QUOTES CHECK
require 'include/functions/function_rex_mquotes.inc.php';

// ----- REX UNSET
unset($REX, $SLY);

// Flag ob Inhalte mit Redaxo aufgerufen oder
// von der Webseite aus
// Kann wichtig für die Darstellung sein
// Sollte immer true bleiben

$REX['REDAXO'] = true;
$SLY = &$REX;

// setzte pfad und includiere klassen und funktionen
$SLY['HTDOCS_PATH'] = '../';
require 'include/master.inc.php';

// ----- addon/normal page path
$SLY['PAGEPATH'] = '';

// ----- pages, verfuegbare seiten
// array(name,addon=1,htmlheader=1);
$SLY['PAGES'] = array();
$SLY['PAGE'] = '';

// ----------------- SETUP
$SLY['USER']  = null;
$SLY['LOGIN'] = null;

if ($SLY['SETUP'])
{
	// ----------------- SET SETUP LANG
	$SLY['LANG'] = '';
	$requestLang = rex_request('lang', 'string');
	$langpath = $SLY['INCLUDE_PATH'].'/lang';
	$SLY['LANGUAGES'] = array();
	if ($handle = opendir($langpath))
	{
		while (false !== ($file = readdir($handle)))
		{
			if (substr($file,-5) == '.lang')
			{
				$locale = substr($file,0,strlen($file)-strlen(substr($file,-5)));
				$SLY['LANGUAGES'][] = $locale;
				if($requestLang == $locale)
					$SLY['LANG'] = $locale;
			}
		}
	}
	closedir($handle);
	if($SLY['LANG'] == '')
		$SLY['LANG'] = 'de_de';

  $I18N = rex_create_lang($SLY['LANG']);
	
	$SLY['PAGES']["setup"] = array($I18N->msg('setup'),0,1);
	$SLY['PAGE'] = "setup";
	$_REQUEST['page'] = 'setup';

}else
{
	// ----------------- CREATE LANG OBJ
	$I18N = rex_create_lang($SLY['LANG']);

	// ---- prepare login
	$SLY['LOGIN'] = new rex_backend_login($SLY['TABLE_PREFIX'] .'user');
	$rex_user_login = rex_post('rex_user_login', 'string');
	$rex_user_psw = rex_post('rex_user_psw', 'string');

	if ($SLY['PSWFUNC'] != '')
	  $SLY['LOGIN']->setPasswordFunction($SLY['PSWFUNC']);

	if (rex_get('rex_logout', 'boolean'))
	  $SLY['LOGIN']->setLogout(true);

	$SLY['LOGIN']->setLogin($rex_user_login, $rex_user_psw);
	$loginCheck = $SLY['LOGIN']->checkLogin();

	$rex_user_loginmessage = "";
	if ($loginCheck !== true)
	{
		// login failed
		$rex_user_loginmessage = $SLY['LOGIN']->message;

		// Fehlermeldung von der Datenbank
		if(is_string($loginCheck))
		  $rex_user_loginmessage = $loginCheck;

		$SLY['PAGES']["login"] = array("login",0,1);
		$SLY['PAGE'] = 'login';
		
		$SLY['USER'] = NULL;
		$SLY['LOGIN'] = NULL;
	}
	else
	{
		// Userspezifische Sprache einstellen, falls gleicher Zeichensatz
		$lang = $SLY['LOGIN']->getLanguage();
		$I18N_T = rex_create_lang($lang,'',FALSE);
		if ($I18N->msg('htmlcharset') == $I18N_T->msg('htmlcharset'))
			$I18N = rex_create_lang($lang);

		$SLY['USER'] = $SLY['LOGIN']->USER;
	}
}

// ----- Prepare Core Pages
if($SLY['USER'])
{
	$SLY['PAGES']["profile"] = array($I18N->msg("profile"),0,1);
	$SLY['PAGES']["credits"] = array($I18N->msg("credits"),0,1);

	if ($SLY['USER']->isAdmin() || $SLY['USER']->hasStructurePerm())
	{
		$SLY['PAGES']["structure"] = array($I18N->msg("structure"),0,1);
		$SLY['PAGES']["mediapool"] = array($I18N->msg("mediapool"),0,0,'NAVI' => array('href' =>'#', 'onclick' => 'openMediaPool()', 'class' => ' rex-popup'));
		$SLY['PAGES']["linkmap"] = array($I18N->msg("linkmap"),0,0);
		$SLY['PAGES']["content"] = array($I18N->msg("content"),0,1);
	}elseif($SLY['USER']->hasPerm('mediapool[]'))
	{
		$SLY['PAGES']["mediapool"] = array($I18N->msg("mediapool"),0,0,'NAVI' => array('href' =>'#', 'onclick' => 'openMediaPool()', 'class' => ' rex-popup'));
	}

	if ($SLY['USER']->isAdmin())
	{
	  $SLY['PAGES']["template"] = array($I18N->msg("template"),0,1);
	  $SLY['PAGES']["module"] = array($I18N->msg("modules"),0,1,'SUBPAGES'=>array(array('',$I18N->msg("modules")),array('actions',$I18N->msg("actions"))));
	  $SLY['PAGES']["user"] = array($I18N->msg("user"),0,1);
	  $SLY['PAGES']["addon"] = array($I18N->msg("addon"),0,1);
	  $SLY['PAGES']["specials"] = array($I18N->msg("specials"),0,1,'SUBPAGES'=>array(array('',$I18N->msg("main_preferences")),array('languages',$I18N->msg("languages"))));
	}
}

// ----- INCLUDE ADDONS
include_once $SLY['INCLUDE_PATH'].'/addons.inc.php';

// ----- Prepare AddOn Pages
if($SLY['USER'])
{
	if (is_array($SLY['ADDON']['status']))
	  reset($SLY['ADDON']['status']);

	$onlineAddons = array_filter(array_values($SLY['ADDON']['status']));
	if(count($onlineAddons) > 0)
	{
		for ($i = 0; $i < count($SLY['ADDON']['status']); $i++)
		{
			$apage = key($SLY['ADDON']['status']);
			
			$perm = '';
			if(isset ($SLY['ADDON']['perm'][$apage]))
			  $perm = $SLY['ADDON']['perm'][$apage];
			  
			$name = '';
			if(isset ($SLY['ADDON']['name'][$apage]))
			  $name = $SLY['ADDON']['name'][$apage];
			  
			if(isset ($SLY['ADDON']['link'][$apage]) && $SLY['ADDON']['link'][$apage] != "")
			  $link = '<a href="'.$SLY['ADDON']['link'][$apage].'">';
			else
			  $link = '<a href="index.php?page='.$apage.'">';
			  
			if (current($SLY['ADDON']['status']) == 1 && $name != '' && ($perm == '' || $SLY['USER']->hasPerm($perm) || $SLY['USER']->isAdmin()))
			{
				$popup = 1;
				if(isset ($SLY['ADDON']['popup'][$apage]))
				  $popup = 0;
				  
				$SLY['PAGES'][strtolower($apage)] = array($name,1,$popup,$link);
			}
			next($SLY['ADDON']['status']);
		}
	}
}

// Set Startpage
if($SLY['USER'])
{
	$SLY['USER']->pages = $SLY['PAGES'];

	// --- page herausfinden
	$SLY['PAGE'] = trim(strtolower(rex_request('page', 'string')));
	if($rex_user_login != "")
		$SLY['PAGE'] = $SLY['LOGIN']->getStartpage();
	if(!isset($SLY['PAGES'][strtolower($SLY['PAGE'])]))
	{
		$SLY['PAGE'] = $SLY['LOGIN']->getStartpage();
		if(!isset($SLY['PAGES'][strtolower($SLY['PAGE'])]))
		{
			$SLY['PAGE'] = $SLY['START_PAGE'];
			if(!isset($SLY['PAGES'][strtolower($SLY['PAGE'])]))
			{
				$SLY['PAGE'] = "profile";
			}
		}
	}
	 
	// --- login ok -> redirect
	if ($rex_user_login != "")
	{
		header('Location: index.php?page='. $SLY['PAGE']);
		exit();
	}
}

$SLY["PAGE_NO_NAVI"] = 1;
if($SLY['PAGES'][strtolower($SLY['PAGE'])][2] == 1)
	$SLY["PAGE_NO_NAVI"] = 0;

// ----- EXTENSION POINT
// page variable validated
rex_register_extension_point( 'PAGE_CHECKED', $SLY['PAGE'], array('pages' => $SLY['PAGES']));

// View laden
$view = sly_View::factory();

// Gewünschte Seite einbinden
$forceLogin = !$SLY['SETUP'] && !$SLY['USER'];
$controller = sly_Controller_Base::factory($forceLogin ? 'login' : null, $forceLogin ? 'index' : null);

if ($controller !== null) {
	$view->openBuffer();
	
	try {
		$controller->dispatch();
	}
	catch (sly_Authorisation_Exception $e1) {
		rex_title('Sicherheitsverletzung');
		print rex_warning($e1->getMessage());
	}
	catch (sly_Controller_Exception $e2) {
		rex_title('Controller-Fehler');
		print rex_warning($e2->getMessage());
	}
	catch (Exception $e3) {
		rex_title('Ausnahme');
		print rex_warning('Es ist eine unerwartete Ausnahme aufgetreten: '.$e3->getMessage());
	}
	
	$view->closeBuffer();
	$CONTENT = $view->render();
}
elseif (isset($SLY['PAGES'][$SLY['PAGE']]['PATH']) && $SLY['PAGES'][$SLY['PAGE']]['PATH'] != "") {
	// If page has a new/overwritten path
	require $SLY['PAGES'][$SLY['PAGE']]['PATH'];
	$CONTENT = ob_get_clean();
}
elseif ($SLY['PAGES'][strtolower($SLY['PAGE'])][1]) {
	// Addon Page
	require $SLY['INCLUDE_PATH'].'/addons/'. $SLY['PAGE'] .'/pages/index.inc.php';
	$CONTENT = ob_get_clean();
}
else { // Core Page
	$view->openBuffer();
	require $SLY['INCLUDE_PATH'].'/pages/'.$SLY['PAGE'].'.inc.php';
	$view->closeBuffer();
	$CONTENT = $view->render();
}

rex_send_article(null, $CONTENT, 'backend', true);
<?php

define('IS_SALLY', true);

/**
 * @package redaxo4
 */

ob_start();
ob_implicit_flush(0);

// ----------------- MAGIC QUOTES CHECK
require 'include/functions/function_rex_mquotes.inc.php';

// ----- REX UNSET
unset($REX);

// Flag ob Inhalte mit Redaxo aufgerufen oder
// von der Webseite aus
// Kann wichtig für die Darstellung sein
// Sollte immer true bleiben

$REX['REDAXO'] = true;

// setzte pfad und includiere klassen und funktionen
$REX['HTDOCS_PATH'] = '../';
require 'include/master.inc.php';

// ----- addon/normal page path
$REX['PAGEPATH'] = '';

// ----- pages, verfuegbare seiten
// array(name,addon=1,htmlheader=1);
$REX['PAGES'] = array();
$REX['PAGE'] = '';

// ----------------- SETUP
$REX['USER']  = null;
$REX['LOGIN'] = null;

if ($REX['SETUP'])
{
	// ----------------- SET SETUP LANG
	$REX['LANG'] = '';
	$requestLang = rex_request('lang', 'string');
	$langpath = $REX['INCLUDE_PATH'].'/lang';
	$REX['LANGUAGES'] = array();
	if ($handle = opendir($langpath))
	{
		while (false !== ($file = readdir($handle)))
		{
			if (substr($file,-5) == '.lang')
			{
				$locale = substr($file,0,strlen($file)-strlen(substr($file,-5)));
				$REX['LANGUAGES'][] = $locale;
				if($requestLang == $locale)
					$REX['LANG'] = $locale;
			}
		}
	}
	closedir($handle);
	if($REX['LANG'] == '')
		$REX['LANG'] = 'de_de';

  $I18N = rex_create_lang($REX['LANG']);
	
	$REX['PAGES']["setup"] = array($I18N->msg('setup'),0,1);
	$REX['PAGE'] = "setup";

}else
{
	// ----------------- CREATE LANG OBJ
	$I18N = rex_create_lang($REX['LANG']);

	// ---- prepare login
	$REX['LOGIN'] = new rex_backend_login($REX['TABLE_PREFIX'] .'user');
	$rex_user_login = rex_post('rex_user_login', 'string');
	$rex_user_psw = rex_post('rex_user_psw', 'string');

	if ($REX['PSWFUNC'] != '')
	  $REX['LOGIN']->setPasswordFunction($REX['PSWFUNC']);

	if (rex_get('rex_logout', 'boolean'))
	  $REX['LOGIN']->setLogout(true);

	$REX['LOGIN']->setLogin($rex_user_login, $rex_user_psw);
	$loginCheck = $REX['LOGIN']->checkLogin();

	$rex_user_loginmessage = "";
	if ($loginCheck !== true)
	{
		// login failed
		$rex_user_loginmessage = $REX['LOGIN']->message;

		// Fehlermeldung von der Datenbank
		if(is_string($loginCheck))
		  $rex_user_loginmessage = $loginCheck;

		$REX['PAGES']["login"] = array("login",0,1);
		$REX['PAGE'] = 'login';
		
		$REX['USER'] = NULL;
		$REX['LOGIN'] = NULL;
	}
	else
	{
		// Userspezifische Sprache einstellen, falls gleicher Zeichensatz
		$lang = $REX['LOGIN']->getLanguage();
		$I18N_T = rex_create_lang($lang,'',FALSE);
		if ($I18N->msg('htmlcharset') == $I18N_T->msg('htmlcharset'))
			$I18N = rex_create_lang($lang);

		$REX['USER'] = $REX['LOGIN']->USER;
	}
}

// ----- Prepare Core Pages
if($REX['USER'])
{
	$REX['PAGES']["profile"] = array($I18N->msg("profile"),0,1);
	$REX['PAGES']["credits"] = array($I18N->msg("credits"),0,1);

	if ($REX['USER']->isAdmin() || $REX['USER']->hasStructurePerm())
	{
		$REX['PAGES']["structure"] = array($I18N->msg("structure"),0,1);
		$REX['PAGES']["mediapool"] = array($I18N->msg("mediapool"),0,0,'NAVI' => array('href' =>'#', 'onclick' => 'openMediaPool()', 'class' => ' rex-popup'));
		$REX['PAGES']["linkmap"] = array($I18N->msg("linkmap"),0,0);
		$REX['PAGES']["content"] = array($I18N->msg("content"),0,1);
	}elseif($REX['USER']->hasPerm('mediapool[]'))
	{
		$REX['PAGES']["mediapool"] = array($I18N->msg("mediapool"),0,0,'NAVI' => array('href' =>'#', 'onclick' => 'openMediaPool()', 'class' => ' rex-popup'));
	}

	if ($REX['USER']->isAdmin())
	{
	  $REX['PAGES']["template"] = array($I18N->msg("template"),0,1);
	  $REX['PAGES']["module"] = array($I18N->msg("modules"),0,1,'SUBPAGES'=>array(array('',$I18N->msg("modules")),array('actions',$I18N->msg("actions"))));
	  $REX['PAGES']["user"] = array($I18N->msg("user"),0,1);
	  $REX['PAGES']["addon"] = array($I18N->msg("addon"),0,1);
	  $REX['PAGES']["specials"] = array($I18N->msg("specials"),0,1,'SUBPAGES'=>array(array('',$I18N->msg("main_preferences")),array('lang',$I18N->msg("languages"))));
	}
}

// ----- INCLUDE ADDONS
include_once $REX['INCLUDE_PATH'].'/addons.inc.php';

// ----- Prepare AddOn Pages
if($REX['USER'])
{
	if (is_array($REX['ADDON']['status']))
	  reset($REX['ADDON']['status']);

	$onlineAddons = array_filter(array_values($REX['ADDON']['status']));
	if(count($onlineAddons) > 0)
	{
		for ($i = 0; $i < count($REX['ADDON']['status']); $i++)
		{
			$apage = key($REX['ADDON']['status']);
			
			$perm = '';
			if(isset ($REX['ADDON']['perm'][$apage]))
			  $perm = $REX['ADDON']['perm'][$apage];
			  
			$name = '';
			if(isset ($REX['ADDON']['name'][$apage]))
			  $name = $REX['ADDON']['name'][$apage];
			  
			if(isset ($REX['ADDON']['link'][$apage]) && $REX['ADDON']['link'][$apage] != "")
			  $link = '<a href="'.$REX['ADDON']['link'][$apage].'">';
			else
			  $link = '<a href="index.php?page='.$apage.'">';
			  
			if (current($REX['ADDON']['status']) == 1 && $name != '' && ($perm == '' || $REX['USER']->hasPerm($perm) || $REX['USER']->isAdmin()))
			{
				$popup = 1;
				if(isset ($REX['ADDON']['popup'][$apage]))
				  $popup = 0;
				  
				$REX['PAGES'][strtolower($apage)] = array($name,1,$popup,$link);
			}
			next($REX['ADDON']['status']);
		}
	}
}

// Set Startpage
if($REX['USER'])
{
	$REX['USER']->pages = $REX['PAGES'];

	// --- page herausfinden
	$REX['PAGE'] = trim(strtolower(rex_request('page', 'string')));
	if($rex_user_login != "")
		$REX['PAGE'] = $REX['LOGIN']->getStartpage();
	if(!isset($REX['PAGES'][strtolower($REX['PAGE'])]))
	{
		$REX['PAGE'] = $REX['LOGIN']->getStartpage();
		if(!isset($REX['PAGES'][strtolower($REX['PAGE'])]))
		{
			$REX['PAGE'] = $REX['START_PAGE'];
			if(!isset($REX['PAGES'][strtolower($REX['PAGE'])]))
			{
				$REX['PAGE'] = "profile";
			}
		}
	}
	 
	// --- login ok -> redirect
	if ($rex_user_login != "")
	{
		header('Location: index.php?page='. $REX['PAGE']);
		exit();
	}
}

$REX["PAGE_NO_NAVI"] = 1;
if($REX['PAGES'][strtolower($REX['PAGE'])][2] == 1)
	$REX["PAGE_NO_NAVI"] = 0;

// ----- EXTENSION POINT
// page variable validated
rex_register_extension_point( 'PAGE_CHECKED', $REX['PAGE'], array('pages' => $REX['PAGES']));

// Gewünschte Seite einbinden
$controller = Controller::factory();

if($controller !== null) {
    try{
        $controller->dispatch();
    }catch(PermissionException $e1){
        print rex_warning($e1->getMessage());
        if(!isset($REX['USER']) || ($REX['USER'] === null)){
            require $REX['INCLUDE_PATH'].'/pages/login.inc.php';
        }
    }catch(ControllerException $e2){
        print rex_warning($e2->getMessage());
    }
}
elseif(isset($REX['PAGES'][$REX['PAGE']]['PATH']) && $REX['PAGES'][$REX['PAGE']]['PATH'] != "")
{
	// If page has a new/overwritten path
	require $REX['PAGES'][$REX['PAGE']]['PATH'];

}elseif($REX['PAGES'][strtolower($REX['PAGE'])][1])
{
  // Addon Page
  require $REX['INCLUDE_PATH'].'/addons/'. $REX['PAGE'] .'/pages/index.inc.php';
	
}else
{
	// Core Page
	require $REX['INCLUDE_PATH'].'/layout/top.php';
	require $REX['INCLUDE_PATH'].'/pages/'. $REX['PAGE'] .'.inc.php';
	require $REX['INCLUDE_PATH'].'/layout/bottom.php';
}

$CONTENT = ob_get_clean();
rex_send_article(null, $CONTENT, 'backend', TRUE);
