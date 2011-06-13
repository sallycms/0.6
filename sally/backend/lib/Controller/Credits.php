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
	public function init() {
		$layout = sly_Core::getLayout();
		$layout->pageHeader(t('credits'));
	}

	public function index() {
		print $this->render('credits/index.phtml');
	}

	public function checkPermission() {
		return sly_Util_User::getCurrentUser() !== null;
	}
}
