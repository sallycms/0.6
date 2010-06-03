<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

/**
 * Business Model Klasse für Slice Values 
 * 
 * @author zozi@webvariants.de
 *
 */
class sly_Model_SliceValue extends sly_Model_Base{

	protected $slice_id;
	protected $type;
	protected $finder;
	protected $value;
	
    protected $_attributes = array('slice_id' => 'int', 'type' => 'string', 'finder' => 'string', 'value' => 'string');
    
    public function getSliceId(){ return $this->slice_id; }
    
    public function setSliceId($slice_id){ $this->slice_id = $slice_id; }
    
    public function getType(){ return $this->type; }

	public function setType($type){ $this->type = $type; }
    
    public function getFinder(){ return $this->finder; }
    
	public function setFinder($finder){ $this->finder = $finder; }
    
    public function getValue(){	return $this->value; }
    
    public function setValue($value){ $this->value = $value; }
}