<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

if ($REX['SETUP']) return;

if (class_exists('WV_DeveloperUtils')) {
	include_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'sallycache.class.php');
	sly_Core::getInstance()->setCache(new SallyCache());
	rex_register_extension('ALL_GENERATED', array('SallyCache', 'flushstatic'));
}
