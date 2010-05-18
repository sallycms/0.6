<?php
/**
 * Image-Resize Addon
 *
 * @author office[at]vscope[dot]at Wolfgang Hutteger
 * @author <a href="http://www.vscope.at">www.vscope.at</a>
 *
 * @author markus[dot]staab[at]redaxo[dot]de Markus Staab
 * 
 *
 * @package redaxo4
 * @version svn:$Id$
 */

$error   = '';
$service = sly_Service_Factory::getService('AddOn');

if (!extension_loaded('gd')) {
	$error = 'GD-LIB-extension not available! See <a href="http://www.php.net/gd">http://www.php.net/gd</a>';
}

if (empty($error)) {
	$folder = $service->internalFolder('image_resize');
	
	if (!file_exists($folder.'/config.inc.php')) {
		copy(dirname(__FILE__).'/example.config.inc.php', $folder.'/config.inc.php');
	}
	
	$file = $folder.'/config.inc.php';
	if (($state = rex_is_writable($file)) !== true) $error = $state;
}

if (empty($error)) {
	$file = $service->publicFolder('image_resize');
	if (($state = rex_is_writable($file)) !== true) $error = $state;
}

if (!empty($error)) {
	$REX['ADDON']['installmsg']['image_resize'] = $error;
}
else {
	$REX['ADDON']['install']['image_resize'] = true;
}
