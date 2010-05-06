<?php

/**
 * Hauptkonfigurationsdatei
 * @package redaxo4
 * @version svn:$Id$
 */

// ----------------- SERVER VARS

// Setupservicestatus - if everything ok -> false; if problem set to true;
$REX['SETUP'] = true;
$REX['SERVER'] = "sallycms.de";
$REX['SERVERNAME'] = "webvariants Projekt";
$REX['VERSION'] = "4";
$REX['SUBVERSION'] = "2";
$REX['MINORVERSION'] = "1";
$REX['ERROR_EMAIL'] = "webadmin@webvariants.de";
$REX['FILEPERM'] = octdec(664); // oktaler wert
$REX['DIRPERM'] = octdec(775); // oktaler wert
$REX['INSTNAME'] = "rex20090421000000";
$REX['SESSION_DURATION'] = 3000;

// Is set first time SQL Object ist initialised
$REX['MYSQL_VERSION'] = "";

// default article id
$REX['START_ARTICLE_ID'] = 1;

// if there is no article -> change to this article
$REX['NOTFOUND_ARTICLE_ID'] = 1;

// default clang id
$REX['START_CLANG_ID'] = 0;

// default template id, if > 0 used as default, else template_id determined by inheritance
$REX['DEFAULT_TEMPLATE_ID'] = 0;

// default language
$REX['LANG'] = "de_de_utf8";

// activate frontend mod_rewrite support for url-rewriting
// Boolean: true/false
$REX['MOD_REWRITE'] = true;

// activate gzip output support
// reduces amount of data need to be send to the client, but increases cpu load of the server
$REX['USE_GZIP'] = "true"; // String: "true"/"false"/"fronted"/"backend"

// activate e-tag support
// tag content with a cache key to improve usage of client cache
$REX['USE_ETAG'] = "false"; // String: "true"/"false"/"fronted"/"backend"

// activate last-modified support
// tag content with a last-modified timestamp to improve usage of client cache
$REX['USE_LAST_MODIFIED'] = "false"; // String: "true"/"false"/"fronted"/"backend"

// activate md5 checksum support
// allow client to validate content integrity
$REX['USE_MD5'] = "false"; // String: "true"/"false"/"fronted"/"backend"

// versch. Pfade
$REX['INCLUDE_PATH']  = realpath($REX['HTDOCS_PATH'].'redaxo/include');
$REX['FRONTEND_PATH'] = realpath($REX['HTDOCS_PATH']);
$REX['DATAFOLDER']    = realpath($REX['HTDOCS_PATH'].'data');
$REX['MEDIAFOLDER']   = realpath($REX['HTDOCS_PATH'].'data/files');
$REX['DYNFOLDER']     = realpath($REX['HTDOCS_PATH'].'data/dyn');

// Prefixes
$REX['TABLE_PREFIX']  = 'rex_';
$REX['TEMP_PREFIX']   = 'tmp_';

// Frontenddatei
$REX['FRONTEND_FILE']	= 'index.php';

// Passwortverschlüsselung, z.B: md5 / mcrypt ...
$REX['PSWFUNC'] = "sha1";

// bei fehllogin 5 sekunden kein relogin moeglich
$REX['RELOGINDELAY'] = 2;

// maximal erlaubte versuche
$REX['MAXLOGINS'] = 50;

// Page auf die nach dem Login weitergeleitet wird
$REX['START_PAGE'] = 'structure';

// ----------------- OTHER STUFF
$REX['SYSTEM_ADDONS'] = array('import_export', 'be_search', 'image_resize');
$REX['MEDIAPOOL']['BLOCKED_EXTENSIONS'] = array('.php','.php3','.php4','.php5','.php6','.phtml','.pl','.asp','.aspx','.cfm','.jsp');

// ----------------- DB1
$REX['DB']['1']['DRIVER'] = "mysql";
$REX['DB']['1']['HOST'] = "localhost";
$REX['DB']['1']['LOGIN'] = "develop";
$REX['DB']['1']['PSW'] = "develop";
$REX['DB']['1']['NAME'] = "webvariants_";
$REX['DB']['1']['PERSISTENT'] = false;

// ----------------- REX PERMS

// ----- allgemein
$REX['PERM'] = array();
$REX['PERM'][] = 'mediapool[]';

// ----- optionen
$REX['EXTPERM'] = array();
$REX['EXTPERM'][] = 'advancedMode[]';
$REX['EXTPERM'][] = 'moveSlice[]';
$REX['EXTPERM'][] = 'copyContent[]';
$REX['EXTPERM'][] = 'moveArticle[]';
$REX['EXTPERM'][] = 'copyArticle[]';
$REX['EXTPERM'][] = 'moveCategory[]';
$REX['EXTPERM'][] = 'publishArticle[]';
$REX['EXTPERM'][] = 'publishCategory[]';
$REX['EXTPERM'][] = 'article2startpage[]';

// ----- extras
$REX['EXTRAPERM'] = array();
$REX['EXTRAPERM'][] = 'editContentOnly[]';

// ----------------- default values
if (!isset($REX['NOFUNCTIONS'])) $REX['NOFUNCTIONS'] = false;
// ----------------- INCLUDE LOADER (Autoloading, etc.)
if(!$REX['NOFUNCTIONS']) include_once ($REX['INCLUDE_PATH'].'/loader.php');

if(!isset($REX['SYNC'])) $REX['SYNC'] = false;

if(!$REX['SYNC']){
	// ----- standard variables
	sly_Core::registerVarType('rex_var_globals');
	sly_Core::registerVarType('rex_var_article');
	sly_Core::registerVarType('rex_var_category');
	sly_Core::registerVarType('rex_var_template');
	sly_Core::registerVarType('rex_var_value');
	sly_Core::registerVarType('rex_var_link');
	sly_Core::registerVarType('rex_var_media');

	// ----- SET CLANG
	include_once $REX['INCLUDE_PATH'].'/clang.inc.php';
	
	$REX['CUR_CLANG']  = sly_Core::getCurrentClang();
	$REX['ARTICLE_ID'] = sly_Core::getCurrentArticleId();
}
