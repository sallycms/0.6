<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/*
This script creates a cache file that contains the Sally classes that are needed
for *every* request. This list is based on a clean installation with no addOns
and special error handler. No external classes are included.
*/

$classes = array(
	'sly_Loader',
	'sly_Core',
	'sly_Event_Dispatcher',
	'sly_Configuration',
	'sly_Util_Array',
	'sly_Util_YAML',
	'sly_Util_Directory',
	'sly_Util_String',
	'sly_ErrorHandler_Base',
	'sly_ErrorHandler',
//	'sly_Cache',                  // would require BabelCache -> external component
	'sly_Util_Cache',
	'sly_Util_Versions',
	'sly_I18N_Base',
	'sly_I18N',
	'sly_Service_Factory',
	'sly_Service_Asset',
	'sly_Service_AddOn_Base',
	'sly_Service_AddOn',
	'sly_Service_Plugin',
	'sly_Util_Article',
	'sly_Service_Model_Base',
	'sly_Service_Article',
	'sly_Model_Base',
	'sly_Model_Base_Article',
	'sly_Model_Article'
);

$here   = dirname(__FILE__);
$target = $here.'/../sally/core/lib/bootcache.php';

if (file_exists($target)) {
	unlink($target);
}

foreach ($classes as $class) {
	$path = $here.'/../sally/core/lib/'.str_replace('_', '/', $class).'.php';
	print "$class...\n";

	$code = file_get_contents($path);
	$code = trim($code);

	file_put_contents($target, $code."\n\n?>", FILE_APPEND);
}

print "done.\n";
