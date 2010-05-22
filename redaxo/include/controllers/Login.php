<?php

class sly_Controller_Login extends sly_Controller_Sally
{
	protected $func = '';
	
	public function __construct()
	{
		$this->action = 'index';
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

	public function checkPermission()
	{
		return true;
	}
}
