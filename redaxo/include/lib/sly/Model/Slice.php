<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

/**
 * Business Model Klasse für Slices
 * 
 * @author zozi@webvariants.de
 *
 */

class sly_Model_Slice extends sly_Model_Base {

	protected $module_id;
	
    protected $_attributes = array('module_id' => 'int');
    
    public function getModuleId(){ return $this->module_id; }
    
    public function setModuleId($module_id){ $this->module_id = $module_id; }
	
	public function addValue($type, $finder, $value = null){
		$service = sly_Service_Factory::getService('SliceValue');
		return $service->create(array('slice_id' => $this->getId(), 'type' => $type, 'finder' => $finder, 'value' => $value));
	}
	
	/**
	 * 
	 * @param string $type
	 * @param string $finder
	 * 
	 * @return Model_SliceValue
	 */
	public function getValue($type, $finder){
		$service = sly_Service_Factory::getService('SliceValue');
		$sliceValue = $service->findBySliceTypeFinder(array('slice_id' => $this->getId(), 'type' => $type, 'finder' => $finder));
		return $sliceValue; 
	}
	
	public function flushValues(){
		$service = sly_Service_Factory::getService('SliceValue');
		return $service->delete(array('slice_id' => $this->getId()));
	}

	/**
	 *
	 * @return Model_Module
	 */
	public function getModule(){
		$moduleService = sly_Service_Factory::getService('Module');
		return $moduleService->findById($this->getModuleId());
	}
	
	public function getOutput(){
		$output = $this->getModule()->getOutput();
		foreach(sly_Core::getVarTypes() as $idx => $var){
			$output = $var->getFEOutput($this->getId(), $output);
		}
		return $output;
	}

}