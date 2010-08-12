<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Login extends sly_Controller_Sally
{
	protected $func = '';

	public function __construct()
	{
		parent::__construct();
		if (!method_exists($this, $this->action)) {
			$this->action = 'index';
		}
	}

	public function init()
	{
		rex_title('Login');
		print '<div class="sly-content">';
	}

	public function teardown()
	{
		print '</div>';
	}

	public function index()
	{
		$this->render('views/login/index.phtml');
		return true;
	}

	public function logout() {
		$this->message = t('login_logged_out');
		sly_Service_Factory::getService('User')->logout();
		$this->index();
	}

	public function checkPermission()
	{
		return true;
	}
}
