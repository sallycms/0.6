<?php

class sly_Controller_Credits extends sly_Controller_Base
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
