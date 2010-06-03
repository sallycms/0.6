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

if ($REX['SETUP']) return;

$REX['ADDON']['name']['sallycache']        = 'Sally Cache';
$REX['ADDON']['version']['sallycache']     = file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'version');
$REX['ADDON']['author']['sallycache']      = 'Christian Zozmann';
$REX['ADDON']['supportpage']['sallycache'] = 'www.webvariants.de';
$REX['ADDON']['requires']['sallycache']    = array('developer_utils');
if(class_exists('WV_DeveloperUtils')){
	include_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'sallycache.class.php');
	sly_Core::getInstance()->setCache(new SallyCache());
	rex_register_extension('ALL_GENERATED', array('SallyCache', 'flushstatic'));
}
?>