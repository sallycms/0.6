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
	public function initAction() {
		$subline = null;

		// add link to help page for bugreports

		if (sly_Util_User::getCurrentUser()->isAdmin()) {
			$subline = array(
				array('credits', t('credits')),
				array('credits_bugreport', 'Fehler gefunden?')
			);
		}

		$layout = sly_Core::getLayout();
		$layout->pageHeader(t('credits'), $subline);
	}

	public function indexAction() {
		print $this->render('credits/index.phtml');
	}

	public function checkPermission() {
		return sly_Util_User::getCurrentUser() !== null;
	}
}
