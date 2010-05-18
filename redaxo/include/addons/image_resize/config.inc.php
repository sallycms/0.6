<?php

/**
 * Image-Resize Addon
 *
 * @author office[at]vscope[dot]at Wolfgang Hutteger
 * @author <a href="http://www.vscope.at">www.vscope.at</a>
 *
 * @author markus.staab[at]redaxo[dot]de Markus Staab
 *
 * @author jan.kristinus[at]redaxo[dot]de Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 *
 * @package redaxo4
 * @version svn:$Id$
 */

// Resize Script verlassen, wenn Frontend und kein Resize-Aufruf
$rex_resize = rex_get('rex_resize', 'string', null);
if (!$REX['REDAXO'] && !$rex_resize) return;

$mypage = 'image_resize';

$REX['ADDON']['rxid'][$mypage]        = '469';
$REX['ADDON']['page'][$mypage]        = $mypage;
$REX['ADDON']['name'][$mypage]        = 'Image Resize';
$REX['ADDON']['perm'][$mypage]        = 'image_resize[]';
$REX['ADDON']['version'][$mypage]     = file_get_contents(dirname(__FILE__).'/version');
$REX['ADDON']['author'][$mypage]      = 'Wolfgang Hutteger, Markus Staab, Jan Kristinus, Christian Zozmann';
$REX['ADDON']['supportpage'][$mypage] = 'forum.redaxo.de, www.webvariants.de';

$REX['PERM'][] = 'image_resize[]';

/* User Parameter */
// $REX['ADDON']['image_resize']['default_filters'] = array('brand');
$REX['ADDON']['image_resize']['default_filters'] = array();

// --- DYN
$REX['ADDON']['image_resize']['max_cachefiles'] = 5;
$REX['ADDON']['image_resize']['max_filters'] = 5;
$REX['ADDON']['image_resize']['max_resizekb'] = 1000;
$REX['ADDON']['image_resize']['max_resizepixel'] = 2000;
$REX['ADDON']['image_resize']['jpg_quality'] = 75;
// --- /DYN

require_once $REX['INCLUDE_PATH'].'/addons/image_resize/classes/class.thumbnail.inc.php';
require_once $REX['INCLUDE_PATH'].'/addons/image_resize/extensions/extension_wysiwyg.inc.php';
rex_register_extension('OUTPUT_FILTER', 'rex_resize_wysiwyg_output');

// Resize Script
if (!empty($rex_resize)) {
	Thumbnail::getResizedImage(urldecode($rex_resize));
}

if ($REX['REDAXO']) {
	// Bei Update Cache loeschen
	
	if (!function_exists('rex_image_ep_mediaupdated')) {
		rex_register_extension('MEDIA_UPDATED', 'rex_image_ep_mediaupdated');
		
		function rex_image_ep_mediaupdated($params)
		{
			Thumbnail::deleteCache($params['filename']);
		}
	}
	
	$I18N->appendFile($REX['INCLUDE_PATH'].'/addons/'.$mypage.'/lang/');
	$REX['ADDON'][$mypage]['SUBPAGES'] = array (
		array('',            $I18N->msg('iresize_subpage_desc')),
		array('settings',    $I18N->msg('iresize_subpage_config')),
		array('clear_cache', $I18N->msg('iresize_subpage_clear_cache')),
	);
}
