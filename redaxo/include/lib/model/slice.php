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
 * Business Model Klasse für Slices
 * 
 * @author zozi@webvariants.de
 *
 */

class Model_Slice extends Model_SliceBase {

	public function addValue($type, $finder, $value = null){
		$service = Service_Factory::getService('SliceValue');
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
		$service = Service_Factory::getService('SliceValue');
		$sliceValue = $service->findBySliceTypeFinder(array('slice_id' => $this->getId(), 'type' => $type, 'finder' => $finder));
		return $sliceValue; 
	}
	
	public function flushValues(){
		$service = Service_Factory::getService('SliceValue');
		return $service->delete(array('slice_id' => $this->getId()));
	}

	/**
	 *
	 * @return Model_Module
	 */
	public function getModule(){
		$moduleService = Service_Factory::getService('Module');
		return $moduleService->findById($this->getModuleId());
	}
	
	/**
	 * Kopiert einen Slice und seine Values 
	 * 
	 * @return Model_Slice
	 */
	public function copy(){
		$sliceservice = Service_Factory::getService('Slice');
		$valueservice = Service_Factory::getService('SliceValue');
		$clone = $sliceservice->create(array('module_id' => $this->getModuleId()));
		
		foreach($valueservice->find(array('slice_id' => $this->getId())) as $sliceValue){
			$sliceValue->setId(Model_Base::NEW_ID);
			$sliceValue->setSliceId($clone->getId());
			$valueservice->save($sliceValue);
		}
		return $clone;	
	}
	
	public function getOutput(){
		$output = $this->getModule()->getOutput();
		foreach(Core::getVarTypes() as $idx => $var){
			$output = $var->getFEOutput($this->getId(), $output);
		}
		return $output;
	}

}