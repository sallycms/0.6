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
define('FILECACHE_PATH', $REX['INCLUDE_PATH'].DIRECTORY_SEPARATOR.'addons'.DIRECTORY_SEPARATOR.'cache/');

$REX['ADDON']['page']['cache']        = 'cache';
$REX['ADDON']['name']['cache']        = 'Cache';
$REX['ADDON']['perm']['cache']        = 'cache[]';
$REX['ADDON']['version']['cache']     = '0.2';
$REX['ADDON']['author']['cache']      = 'Christian Zozmann';
$REX['ADDON']['supportpage']['cache'] = 'www.webvariants.de';

include_once(FILECACHE_PATH.'classes/filecache.class.php');
$cache = new FileCache(FILECACHE_PATH.DIRECTORY_SEPARATOR.'generated'.DIRECTORY_SEPARATOR);
Core::getInstance()->setCache($cache);

rex_register_extension('ALL_GENERATED', array('FileCache', 'flushstatic'));
?>