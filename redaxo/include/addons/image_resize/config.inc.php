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
$rex_resize = sly_get('rex_resize', 'string', null);
if (!$REX['REDAXO'] && !$rex_resize) return;

$mypage = 'image_resize';

$REX['ADDON']['rxid'][$mypage]        = '469';
$REX['ADDON']['page'][$mypage]        = $mypage;
$REX['ADDON']['name'][$mypage]        = 'Image Resize';
$REX['ADDON']['perm'][$mypage]        = 'image_resize[]';
$REX['ADDON']['version'][$mypage]     = file_get_contents(dirname(__FILE__).'/version');
$REX['ADDON']['author'][$mypage]      = 'Wolfgang Hutteger, Markus Staab, Jan Kristinus, Christian Zozmann, Christoph Mewes';
$REX['ADDON']['supportpage'][$mypage] = 'forum.redaxo.de, www.webvariants.de';

$REX['PERM'][] = 'image_resize[]';

/* Standard-Konfiguration */
$REX['ADDON']['image_resize']['default_filters'] = array();
$REX['ADDON']['image_resize']['max_cachefiles'] = 5;
$REX['ADDON']['image_resize']['max_filters'] = 5;
$REX['ADDON']['image_resize']['max_resizekb'] = 1000;
$REX['ADDON']['image_resize']['max_resizepixel'] = 2000;
$REX['ADDON']['image_resize']['jpg_quality'] = 75;

/* benutzerdefinierte Konf. einbinden */
$internal   = sly_Service_Factory::getService('AddOn')->internalFolder($mypage);
$configFile = $internal.'/config.inc.php';
if (!file_exists($configFile)) copy(dirname(__FILE__).'/example.config.inc.php', $configFile);
include $configFile;

/* API einbinden */
require_once $REX['INCLUDE_PATH'].'/addons/image_resize/classes/class.thumbnail.inc.php';
require_once $REX['INCLUDE_PATH'].'/addons/image_resize/extensions/extension_wysiwyg.inc.php';
rex_register_extension('OUTPUT_FILTER', 'rex_resize_wysiwyg_output');

// Resize Script
if (!empty($rex_resize)) {
	Thumbnail::getResizedImage(urldecode($rex_resize));
}

if ($REX['REDAXO']) {
	// Bei Update Cache löschen
	
	rex_register_extension('MEDIA_UPDATED', array('Thumbnail', 'mediaUpdated'));
	
	$I18N->appendFile($REX['INCLUDE_PATH'].'/addons/'.$mypage.'/lang/');
	$REX['ADDON'][$mypage]['SUBPAGES'] = array (
		array('',            $I18N->msg('iresize_subpage_desc')),
		array('settings',    $I18N->msg('iresize_subpage_config')),
		array('clear_cache', $I18N->msg('iresize_subpage_clear_cache')),
	);
}
