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
 * Basisklasse für alle Models
 * 
 * @author zozi@webvariants.de
 *
 */
abstract class Model_Base {
	
	const NEW_ID = -1;
	
    protected $id = self::NEW_ID;
    
    public function __construct($params) {
    	if(isset($params['id'])) $this->id = $params['id'];
    	
    	foreach($this->_attributes as $name => $type){
    		if(isset($params[$name])) $this->$name = $params[$name];
    	}
    }

    public function getId() { return $this->id; }
	public function setId($id) { $this->id = $id; }

    public function toHash(){
    	$return = array('id' => $this->id);
    	foreach($this->_attributes as $name => $type){
    		$return[$name] = $this->$name;
    	}
    	return $return;
    }

}