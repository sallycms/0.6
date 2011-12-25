<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Login extends sly_Controller_Backend {
	public function __construct() {
		parent::__construct();

		if (!method_exists($this, $this->action)) {
			$this->action = 'index';
		}
	}

	public function init() {
		$layout = sly_Core::getLayout();
		$layout->showNavigation(false);
		$layout->pageHeader(t('login_title'));
	}

	public function index() {
		print $this->render('login/index.phtml');
	}

	protected function login() {
		$username = sly_post('username', 'string');
		$password = sly_post('password', 'string');
		$loginOK  = sly_Service_Factory::getUserService()->login($username, $password);

		if ($loginOK !== true) {
			$this->message = t('login_error', '<strong>'.sly_Core::config()->get('RELOGINDELAY').'</strong>');
			$this->index();
		}
		else {
			// if relogin, forward to previous page
			$referer = sly_post('referer', 'string', false);

			if ($referer && !sly_Util_String::startsWith(basename($referer), 'index.php?page=login')) {
				$url = $referer;
				$msg = t('redirect_previous_page', $referer);
			}
			else {
				$user = sly_Util_User::getCurrentUser();
				$url  = 'index.php?page='.$user->getStartPage();
				$msg  = t('redirect_startpage', $url);
			}

			sly_Util_HTTP::redirect($url, array(), $msg, 302);
		}
	}

	public function logout() {
		sly_Service_Factory::getUserService()->logout();
		$this->message = t('you_have_been_logged_out');
		$this->index();
	}

	public function checkPermission() {
		return true;
	}
}
