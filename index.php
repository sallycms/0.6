<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

define('IS_SALLY_BACKEND', false);

// load core system
require 'sally/core/master.php';

// add the frontend app
sly_Loader::addLoadPath(SLY_SALLYFOLDER.'/frontend/lib/', 'sly_');

// init the app
$app = new sly_App_Frontend();
#sly_Core::setApp($app);
$app->initialize();

// ... and run it
$app->run();
