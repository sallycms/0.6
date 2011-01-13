<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_Controller_Sally extends sly_Controller_Base {
	protected function __construct() {
		parent::__construct();
		$this->setContentType('text/html');
		$this->setCharset(t('htmlcharset'));
	}

	public function dispatch() {
		$layout = sly_Core::getLayout('Sally');

		$layout->openBuffer();
		parent::dispatch();
		$layout->closeBuffer();
		return $layout->render();
	}
}
