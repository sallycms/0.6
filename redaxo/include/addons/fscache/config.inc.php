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

if ($REX['SETUP'] || defined('FILECACHE_PATH')) return;
define('FILECACHE_PATH', $REX['INCLUDE_PATH'].DIRECTORY_SEPARATOR.'addons'.DIRECTORY_SEPARATOR.'fscache/');

$REX['ADDON']['page']['fscache']        = 'fscache';
$REX['ADDON']['name']['fscache']        = 'FSCache';
$REX['ADDON']['perm']['fscache']        = 'fscache[]';
$REX['ADDON']['version']['fscache']     = file_get_contents(FILECACHE_PATH.'version');
$REX['ADDON']['author']['fscache']      = 'Christian Zozmann';
$REX['ADDON']['supportpage']['fscache'] = 'www.webvariants.de';

include_once(FILECACHE_PATH.'classes/filecache.class.php');
Core::getInstance()->setCache(new FileCache());

rex_register_extension('ALL_GENERATED', array('FileCache', 'flushstatic'));
?>