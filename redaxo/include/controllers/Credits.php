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

class sly_Controller_Credits extends sly_Controller_Sally
{
	protected $func = '';
	
	public function init()
	{
		global $I18N;
		rex_title($I18N->msg('credits'));
		print '<div class="sly-content">';
	}
	
	public function teardown()
	{
		print '</div>';
	}

	public function index()
	{
		$this->render('views/credits/index.phtml');
		return true;
	}

	public function checkPermission()
	{
		return true;
	}
}
