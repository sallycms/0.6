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
 * DB Model Klasse für Slices
 * 
 * @author zozi@webvariants.de
 *
 */
class sly_Service_Slice extends sly_Service_Model_Base{

	protected $tablename = 'slice';
	
	protected function makeObject(array $params){
		return new sly_Model_Slice($params);
	}
	
	/**
	 * Kopiert einen Slice und seine Values 
	 * 
	 * @return sly_Model_Slice
	 */
	public function copy(sly_Model_Slice $slice){

		$valueservice = sly_Service_Factory::getService('SliceValue');
		$clone = $this->create(array('module_id' => $slice->getModuleId()));
		
		foreach($valueservice->find(array('slice_id' => $slice->getId())) as $sliceValue){
			$sliceValue->setId(sly_Model_Base::NEW_ID);
			$sliceValue->setSliceId($clone->getId());
			$valueservice->save($sliceValue);
		}
		return $clone;	
	}

}