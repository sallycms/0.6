<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Business Model Klasse für Module
 *
 * @author zozi@webvariants.de
 *
 */
class sly_Model_Module extends sly_Model_Base {

	protected $name;
	protected $category_id;
	protected $eingabe;
	protected $ausgabe;
	protected $createuser;
	protected $createdate;
	protected $updateuser;
	protected $updatedate;
	protected $attributes;
	protected $revision;

	protected $_attributes = array('name' => 'string', 'category_id' => 'int', 'eingabe' => 'string', 'ausgabe' => 'string',
									'createuser' => 'string', 'createdate' => 'int', 'updateuser' => 'string',
									'updatedate' => 'int', 'attributes' => 'string', 'revision' => 'int');

	public function getName(){ return $this->name; }
	public function setName($name){ $this->name = $name; }
	public function getCategoryId(){ return $this->category_id; }
	public function setCategoryId($categoryId){ $this->category_id = $categoryId; }
	public function getInput(){ return $this->eingabe; }
	public function setInput($input){ $this->eingabe = $input; }
	public function getOutput(){ return $this->ausgabe; }
	public function setOutput($output){ $this->ausgabe = $output; }
	public function getAttributes(){ return $this->attributes; }
	public function setAttributes($attributes){ $this->attributes = $attributes; }
	public function getRevision(){ return $this->revision; }
	public function setRevision($revision){ $this->revision = $revision; }


}