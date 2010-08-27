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
 * Basisklasse für alle Models
 *
 * @author  zozi@webvariants.de
 * @ingroup model
 */
abstract class sly_Model_Base {

	const NEW_ID = -1;

	protected $id = self::NEW_ID;

	public function __construct($params) {
		if (isset($params['id'])) $this->id = $params['id'];

		foreach ($this->_attributes as $name => $type){
			if (isset($params[$name])) $this->$name = $params[$name];
		}
	}

	public function getId()    { return $this->id; }
	public function setId($id) { $this->id = $id;  }

	public function toHash() {
		$return = array('id' => $this->id);

		foreach($this->_attributes as $name => $type) {
			$return[$name] = $this->$name;
		}

		return $return;
	}
}
