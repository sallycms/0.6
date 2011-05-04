<?php

class sly_Controller_Content_Meta extends sly_Controller_Content {
	
	protected function index() {
		$this->render('index.phtml', array('mode' => 'meta'));
	}
	
	protected function render($filename, $params = array()) {
		$filename = DIRECTORY_SEPARATOR . 'meta' . DIRECTORY_SEPARATOR . $filename;
		parent::render($filename, $params);
	}
	
	
	
}
