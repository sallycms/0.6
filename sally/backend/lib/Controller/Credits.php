<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Credits extends sly_Controller_Backend implements sly_Controller_Interface {
	protected function init() {
		$subline = null;

		// add link to help page for bugreports

		if (sly_Util_User::getCurrentUser()->isAdmin()) {
			$subline = array(
				array('page' => 'credits', 'label' => ht('credits')),
				array('page' => 'credits_bugreport', 'label' => 'Fehler gefunden?')
			);
		}

		$layout = sly_Core::getLayout();
		$layout->pageHeader(t('credits'), $subline);
	}

	public function indexAction() {
		$this->init();
		print $this->render('credits/index.phtml');
	}

	public function checkPermission($action) {
		return sly_Util_User::getCurrentUser() !== null;
	}
}
