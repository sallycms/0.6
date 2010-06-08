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

if(class_exists('WV_DeveloperUtils')){
	include_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'sallycache.class.php');
	sly_Core::getInstance()->setCache(new SallyCache());
	rex_register_extension('ALL_GENERATED', array('SallyCache', 'flushstatic'));
}
?>