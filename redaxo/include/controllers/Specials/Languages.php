<?php

class sly_Controller_Specials_Languages extends sly_Controller_Base
{
	// for now just copy those two fields and the init() method, until
	// I find a nice way to generalize it into. --xrstf
	
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
		$this->render('views/specials/languages.phtml');
	}
	
	public function addclang()    { $this->index(); }
	public function editclang()   { $this->index(); }
	public function deleteclang() { $this->index(); }
	
	public function checkPermission()
	{
		return true;
	}
}
