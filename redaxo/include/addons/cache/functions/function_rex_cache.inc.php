<?php
/**
 * serialisiert einen string, für die performance 
 * auch als json string falls PHP5 vorhanden ist
 * @param $content mixed object
 * @return encoded string
 */
function rex_cache_encode($content){
	global $REX;
	if(function_exists('json_encode') && strpos($REX['LANG'], 'utf8')){
		return json_encode($content);
	}else{
		return serialize($content);
	}
}

/**
 * deserialisiert einen string, für die performance
 * auch als json string falls PHP5 vorhanden ist
 * @param $content encoded string
 * @return mixed decoded object
 */
function rex_cache_decode($content){
	global $REX;
	if(function_exists('json_decode') && strpos($REX['LANG'], 'utf8')){
		return json_decode($content, true);
	}else{
		return unserialize($content);
	}
}

function rex_cache_delete(){
	global $REX;
	rex_cache_deleteArticles();
	foreach($REX['CLANG'] as $clangId => $name){
		rex_cache_deleteLists(array('clang' => $clangId));
	}
}

function rex_cache_write(){
	rex_cache_writeCategoryList();
	rex_cache_writeArticleList();
	rex_cache_writeArticles();
}

/**
 * deletes $REX['ART'] caching file
 * @return void
 */
function rex_cache_deleteArticles(){
	global $REX;
	@unlink($REX['INCLUDE_PATH'].DIRECTORY_SEPARATOR.'addons'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'generated'.DIRECTORY_SEPARATOR.'cache.article');
}

/**
 * Deletes aList and clist caching files for a specifiv language 
 * @param $clang
 * @return void
 */
function rex_cache_deleteLists($params){
	global $REX;
	$clang = $params['clang'];
	@unlink($REX['INCLUDE_PATH'].DIRECTORY_SEPARATOR.'addons'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'generated.'.DIRECTORY_SEPARATOR.$clang.'.alist');
	@unlink($REX['INCLUDE_PATH'].DIRECTORY_SEPARATOR.'addons'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'generated.'.DIRECTORY_SEPARATOR.$clang.'.clist');
}

/**
 * reads caching file to global $REX['ART']
 * 
 * @return void
 */
function rex_cache_readArticles(){
	global $REX;
	$REX['ART'] = null;
	$REX['CAT'] = null;
	$cacheFile = $REX['INCLUDE_PATH'].DIRECTORY_SEPARATOR.'addons'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'generated'.DIRECTORY_SEPARATOR.'cache.article';
	if (file_exists($cacheFile)){
		$cache = rex_cache_decode(file_get_contents($cacheFile));
		$REX['ART'] = $cache['ART'];
		$REX['CAT'] = $cache['CAT'];
	}
}

/**
 * writes global $REX['ART'] to caching file
 *  
 * @return void
 */
function rex_cache_writeArticles(){
	global $REX, $I18N;
	
	if(isset($REX['ART_CACHE_UPDATED'])){
	
		$cacheFile = $REX['INCLUDE_PATH'].DIRECTORY_SEPARATOR.'addons'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'generated'.DIRECTORY_SEPARATOR.'cache.article';
		$content = rex_cache_encode(array( 'ART' => $REX['ART'], 'CAT' => $REX['CAT']));
		if (!rex_put_file_contents($cacheFile, $content)){
			$MSG = $I18N->msg('article_could_not_be_generated')." ".$I18N->msg('check_rights_in_directory').$REX['INCLUDE_PATH']."/addons/cache/generated/";
		}
	}
}

/**
 * sets a OORedaxo instance to cache
 * @param array $params
 * @return object of type OORedaxo || null
 */
function rex_cache_setRedaxo($params){
	global $REX;
	$obj = $params['subject'];
	if($obj instanceof OOArticle){
		$REX['ART'][$obj->getId()][$obj->getClang()] = serialize($obj);
	}elseif($obj instanceof OOCategory){
		$REX['CAT'][$obj->getId()][$obj->getClang()] = serialize($obj);
	}
	
	$REX['ART_CACHE_UPDATED'] = true;
}

/**
 * gets a OORedaxo instance from cache
 * @param array $params
 * @return object of type OORedaxo || null
 */
function rex_cache_getRedaxo($params){
	global $REX;
	$id = $params['id'];
	$clang = $params['clang'];
	$type = $params['oocategory'] ? 'CAT' : 'ART';
	if(!isset($REX[$type]))
		rex_cache_readArticles();
	if(isset($REX[$type][$id][$clang]))
		return unserialize($REX[$type][$id][$clang]);
	
	return null;
}

/**
 * deletes cache entry in $REX['ART'] for specific article  
 * @param $id articleid
 * @param $clang language Id of article
 * @return void
 */
function rex_cache_deleteRedaxo($params){
	global $REX;
	$id = $params['id'];
	$clang = $params['clang'];
	$type = substr($params['extension_point'], 0, 3);
	
	unset($REX[$type][$id][$clang]);
	if($type == 'CAT') unset($REX['ART'][$id][$clang]); //drop art too
	
	$REX['ART_CACHE_UPDATED'] = true;
}

/**
 * reads a $REX['CLIST'] caching file for a language
 * 
 * @param int $clang
 * @return void
 */
function rex_cache_readCategoryList($clang){
	global $REX;
	$cacheFile = $REX['INCLUDE_PATH'].DIRECTORY_SEPARATOR.'addons'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'generated.'.DIRECTORY_SEPARATOR.$clang.'.clist';
	
	if (file_exists($cacheFile)){
		$cache = rex_cache_decode(file_get_contents($cacheFile));
		$REX['CLIST'][$clang] = $cache;
	}
}

/**
 * writes global $REX['CLIST'] to caching file
 * 
 * @return void
 */
function rex_cache_writeCategoryList(){
	global $REX, $I18N;
	if(isset($REX['CLIST_CACHE_UPDATED'])){
		foreach(array_keys($REX['CLIST_CACHE_UPDATED']) as $clang){
			$cacheFile = $REX['INCLUDE_PATH'].DIRECTORY_SEPARATOR.'addons'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'generated.'.DIRECTORY_SEPARATOR.$clang.'.clist';
			$content = rex_cache_encode($REX['CLIST'][$clang]);
			if (!rex_put_file_contents($cacheFile, $content)){
				$MSG = $I18N->msg('article_could_not_be_generated')." ".$I18N->msg('check_rights_in_directory').$REX['INCLUDE_PATH']."/addons/cache/generated/";
			}
		}
	}
}

/**
 * set $REX['CLIST'] cache entry
 * 
 * @return void
 */
function rex_cache_setCategoryList($params){
	global $REX;
	if(!isset($REX['CLIST']))
		rex_cache_readCategoryList($params['clang']);
	$REX['CLIST'][$params['clang']][$params['cat_parent_id']] = $params['subject'];
	$REX['CLIST_CACHE_UPDATED'][$params['clang']] = true;
}

/**
 * get $REX['CLIST'] cache entry
 * 
 * @return array || null
 */
function rex_cache_getCategoryList($params){
	global $REX;
	
	$cat_parent_id = $params['cat_parent_id'];
	$clang = $params['clang'];
	
	if(!isset($REX['CLIST']))
		rex_cache_readCategoryList($clang);
	if(isset($REX['CLIST'][$clang][$cat_parent_id]))
		return $REX['CLIST'][$clang][$cat_parent_id];
	return null;
}

function rex_cache_deleteCategoryList($params){
	global $REX;
	if(!isset($REX['CLIST']))
		rex_cache_readCategoryList($params['clang']);
	if(isset($REX['CLIST'][$params['clang']][$params['re_id']])){
		unset($REX['CLIST'][$params['clang']][$params['re_id']]);
		$REX['CLIST_CACHE_UPDATED'][$params['clang']] = true;
	}
	if(isset($REX['CLIST'][$params['clang']][$params['id']])){
		unset($REX['CLIST'][$params['clang']][$params['id']]);
	}
}

/**
 * reads a $REX['ALIST'] caching file for a language
 * 
 * @param int $clang
 * @return void
 */
function rex_cache_readArticleList($clang){
	global $REX;
	$cacheFile = $REX['INCLUDE_PATH'].DIRECTORY_SEPARATOR.'addons'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'generated.'.DIRECTORY_SEPARATOR.$clang.'.alist';
	if(file_exists($cacheFile)){
		$cache = rex_cache_decode(file_get_contents($cacheFile));
		$REX['ALIST'][$clang] = $cache;
	}
}

/**
 * writes global $REX['ALIST'] to caching file
 * 
 * @return void
 */
function rex_cache_writeArticleList(){
	global $REX, $I18N;
		
	if(isset($REX['ALIST_CACHE_UPDATED'])){
		foreach(array_keys($REX['ALIST_CACHE_UPDATED']) as $clang){
			$cacheFile = $REX['INCLUDE_PATH'].DIRECTORY_SEPARATOR.'addons'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'generated.'.DIRECTORY_SEPARATOR.$clang.'.alist';
			$content = rex_cache_encode($REX['ALIST'][$clang]);
			if (!rex_put_file_contents($cacheFile, $content)){
				$MSG = $I18N->msg('article_could_not_be_generated')." ".$I18N->msg('check_rights_in_directory').$REX['INCLUDE_PATH']."/addons/cache/generated/";
			}
		}
	}
}

/**
 * set $REX['ALIST'] cache entry
 * 
 * @return void
 */
function rex_cache_setArticleList($params){
	global $REX;
		
	if(!isset($REX['ALIST']))
		rex_cache_readArticleList($params['clang']);
	$REX['ALIST'][$params['clang']][$params['category_id']] = $params['subject'];
	$REX['ALIST_CACHE_UPDATED'][$params['clang']] = true;
}

/**
 * get $REX['ALIST'] cache entry
 * 
 * @return array || null
 */
function rex_cache_getArticleList($params){
	global $REX;
	$category_id = $params['category_id'];
	$clang = $params['clang'];
	
	if(!isset($REX['ALIST']))
		rex_cache_readArticleList($clang);
	if(isset($REX['ALIST'][$clang][$category_id]))
		return $REX['ALIST'][$clang][$category_id];
	
	return null;
}

function rex_cache_deleteArticleList($params){
	global $REX;
	if(!isset($REX['ALIST']))
		rex_cache_readArticleList($params['clang']);
	if(isset($REX['ALIST'][$params['clang']][$params['re_id']])){
		unset($REX['ALIST'][$params['clang']][$params['re_id']]);
		$REX['ALIST_CACHE_UPDATED'][$params['clang']] = true;
	}
}