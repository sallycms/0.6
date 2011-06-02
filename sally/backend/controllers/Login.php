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
	protected $func = '';

	public function __construct() {
		parent::__construct();

		if (!method_exists($this, $this->action)) {
			$this->action = 'index';
		}

		sly_Core::getI18N()->appendFile(SLY_COREFOLDER.'/lang/pages/login/');
	}

	public function init() {
		$layout = sly_Core::getLayout();
		$layout->showNavigation(false);
		$layout->pageHeader(t('login_title'));
		print '<div class="sly-content">';
	}

	public function teardown() {
		print '</div>';
	}

	public function index() {
		if(empty($this->message)) $this->message = t('login_welcome');
		print $this->render('login/index.phtml');
	}

	protected function login() {
		$user_login = sly_post('rex_user_login', 'string');
		$user_psw   = sly_post('rex_user_psw', 'string');
		$loginCheck = sly_Service_Factory::getUserService()->login($user_login, $user_psw);

		if ($loginCheck !== true) {
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
		global $REX;
		sly_Service_Factory::getUserService()->logout();
		$REX['USER'] = null;
		$this->message = t('login_logged_out');
		$this->index();
	}

	public function checkPermission() {
		return true;
	}
}
