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
		$service = new Service_SliceValue();
		return $service->create(array('slice_id' => $this->getId(), 'type' => 'file', 'finder' => '1', 'value' => 'tada'));
	}
	
	public function flushValues(){
		$service = new Service_SliceValue();
		return $service->delete(array('slice_id' => $this->getId()));
	}
	
	public function getModule(){
		$moduleService = Service_Factory::getService('Module');
		return $moduleService->findById($this->getModuleId());
	}

}