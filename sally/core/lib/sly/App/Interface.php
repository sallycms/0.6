<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

interface sly_App_Interface {
	public function initialize();
	public function run();
	public function dispatch($controller, $action);
	public function getControllerClass($controller);
	public function getCurrentController();
	public function getCurrentAction();
}
