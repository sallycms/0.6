<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Credits extends sly_Controller_Backend {
	protected $func = '';

	public function init() {
		$layout = sly_Core::getLayout();
		$layout->pageHeader(t('credits'));
		print '<div class="sly-content">';
	}

	public function teardown() {
		print '</div>';
	}

	public function index() {
		$this->render('credits/index.phtml');
		return true;
	}

	public function checkPermission() {
		return true;
	}
}
