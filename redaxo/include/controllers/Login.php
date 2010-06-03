<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

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
