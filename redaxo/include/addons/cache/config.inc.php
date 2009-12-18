<?php 
$REX['ADDON']['page']['cache']       	= 'cache';
$REX['ADDON']['name']['cache']        	= 'Cache';
$REX['ADDON']['perm']['cache'] 			= 'cache[]';
$REX['ADDON']['version']['cache']     	= '0.2';
$REX['ADDON']['author']['cache']      	= 'Christian Zozmann';
$REX['ADDON']['supportpage']['cache'] 	= 'www.webvariants.de';


include_once($REX['INCLUDE_PATH'].'/addons/cache/functions/function_rex_cache.inc.php');

rex_register_extension('OOREDAXO_GET', 'rex_cache_getRedaxo');
rex_register_extension('OOREDAXO_CREATED', 'rex_cache_setRedaxo');

rex_register_extension('ALIST_GET', 'rex_cache_getArticleList');
rex_register_extension('ALIST_CREATED', 'rex_cache_setArticleList');

rex_register_extension('CLIST_GET', 'rex_cache_getCategoryList');
rex_register_extension('CLIST_CREATED', 'rex_cache_setCategoryList');


rex_register_extension('ART_UPDATED', 'rex_cache_deleteRedaxo');
rex_register_extension('ART_META_UPDATED', 'rex_cache_deleteRedaxo');
rex_register_extension('ART_STATUS', 'rex_cache_deleteRedaxo');
rex_register_extension('ART_DELETED', 'rex_cache_deleteRedaxo');
rex_register_extension('CAT_UPDATED', 'rex_cache_deleteRedaxo');
rex_register_extension('CAT_STATUS', 'rex_cache_deleteRedaxo');
rex_register_extension('CAT_DELETED', 'rex_cache_deleteRedaxo');

rex_register_extension('CAT_ADDED', 'rex_cache_deleteCategoryList');
rex_register_extension('CAT_UPDATED', 'rex_cache_deleteCategoryList');
rex_register_extension('CAT_DELETED', 'rex_cache_deleteCategoryList');

rex_register_extension('ART_ADDED', 'rex_cache_deleteArticleList');
rex_register_extension('ART_UPDATED', 'rex_cache_deleteArticleList');
rex_register_extension('ART_DELETED', 'rex_cache_deleteArticleList');

rex_register_extension('ALL_GENERATED', 'rex_cache_delete');

rex_register_extension('OUTPUT_FILTER', 'rex_cache_write');
?>