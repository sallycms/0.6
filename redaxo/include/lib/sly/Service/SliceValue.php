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
 * DB Model Klasse für Slice Values 
 * 
 * @author zozi@webvariants.de
 *
 */
class sly_Service_SliceValue extends sly_Service_Model_Base{

	protected $tablename = 'slice_value';
	
	protected function makeObject(array $params){
		return new sly_Model_SliceValue($params);
	}
	
	public function findBySliceTypeFinder($slice_id, $type, $finder){
		$where = array('slice_id' => $slice_id, 'type' => $type, 'finder' => $finder);

		$res = $this->find($where);
    	if(count($res) == 1) return $res[0];
    	
    	return null;
	}

}
