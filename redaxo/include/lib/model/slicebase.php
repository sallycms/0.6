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

class Model_SliceBase extends Model_Base{

	protected $namespace;
	protected $fk_id;
	protected $module_id;
	
    protected $attributes = array('id' => 'int', 'namespace' => 'string', 'fk_id' => 'int', 'module_id' => 'int');
    
    public function getNamespace(){ return $this->namespace; }

    public function getFkId(){ return $this->fk_id; }
    
    public function getModuleId(){ return $this->module_id; }
    
    public function setNamespace($namespace){ $this->namespace = $namespace; }

    public function setFkId($fk_id){ $this->fk_id = $fk_id; }
    
    public function setModuleId($module_id){ $this->module_id = $module_id; }

}