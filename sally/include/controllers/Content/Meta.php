<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Content_Meta extends sly_Controller_Content {
	
	protected function index() {
		$this->render('index.phtml');
	}
	
	protected function render($filename, $params = array()) {
		$filename = DIRECTORY_SEPARATOR . 'meta' . DIRECTORY_SEPARATOR . $filename;
		parent::render($filename, $params);
	}
	
	
	
}
