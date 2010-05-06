<?php

class sly_Controller_Specials extends sly_Controller_Base
{
	protected $warning;
	protected $info;
	
	public function init()
	{
		global $I18N;
		
		$subline = array(
			array('',          $I18N->msg('main_preferences')),
			array('languages', $I18N->msg('languages'))
		);
		
		rex_title($I18N->msg('specials'), $subline);
	}
	
	public function index()
	{
		$this->render('views/specials/index.phtml');
	}
	
	public function clearcache()
	{
		$this->info = rex_generateAll();
		$this->index();
	}
	
	public function checkPermission()
	{
		return true;
	}
}
