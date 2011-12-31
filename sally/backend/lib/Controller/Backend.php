<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_Controller_Backend extends sly_Controller_Base {
	public function __construct() {
		$this->setContentType('text/html');
		$this->setCharset('UTF-8');
	}

	protected function getViewFolder() {
		return SLY_SALLYFOLDER.'/backend/views/';
	}

	public function dispatch() {
		$layout = sly_Core::getLayout('Backend');

		$layout->openBuffer();
		parent::dispatch();
		$layout->closeBuffer();
		return $layout->render();
	}
}
