<?php
/*
 * Copyright (c) 2009, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

if ($REX['SETUP']) return;

$REX['ADDON']['page']['fscache']        = 'fscache';
$REX['ADDON']['name']['fscache']        = 'FSCache';
$REX['ADDON']['perm']['fscache']        = 'fscache[]';
$REX['ADDON']['version']['fscache']     = file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'version');
$REX['ADDON']['author']['fscache']      = 'Christian Zozmann';
$REX['ADDON']['supportpage']['fscache'] = 'www.webvariants.de';
$REX['ADDON']['requires']['fscache']    = array('developer_utils');

if(class_exists('WV_DeveloperUtils')){
	include_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'filecache.class.php');
	Core::getInstance()->setCache(new FileCache());
	rex_register_extension('ALL_GENERATED', array('FileCache', 'flushstatic'));
}
?>