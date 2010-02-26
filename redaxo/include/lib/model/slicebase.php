<?php
/*
 * Copyright (c) 2009, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

/**
 * DB Model Klasse für Slices
 * 
 * @author zozi@webvariants.de
 *
 */

abstract class Model_SliceBase extends Model_Base{

	protected $module_id;
	
    protected $_attributes = array('module_id' => 'int');
    
    public function getModuleId(){ return $this->module_id; }
    
    public function setModuleId($module_id){ $this->module_id = $module_id; }

}