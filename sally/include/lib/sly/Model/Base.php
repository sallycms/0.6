<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
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

	protected $_pk;
	protected $_attributes;

	public function __construct($params = array()) {
		foreach ($this->_pk as $name => $type){
			if (isset($params[$name])) {
				$this->$name = $params[$name];
				settype($this->$name, $type);
			}
		}
		foreach ($this->_attributes as $name => $type){
			if (isset($params[$name])) {
				$this->$name = $params[$name];
				settype($this->$name, $type);
			}
		}
	}

	public function toHash() {
		return $this->attrsToHash($this->_attributes);
	}

	public function getPKHash() {
		return $this->attrsToHash($this->_pk);
	}

	public function setUpdateColumns($user = null) {
		if (!$user) {
			$user = sly_Util_User::getCurrentUser()->getLogin();
		}

		$this->setUpdateDate(time());
		$this->setUpdateUser($user);
	}

	public function setCreateColumns($user = null) {
		if (!$user) {
			$user = sly_Util_User::getCurrentUser()->getLogin();
		}

		$this->setCreateDate(time());
		$this->setCreateUser($user);
		$this->setUpdateColumns($user);
	}

	protected function attrsToHash($attrs) {
		$data = array();
		foreach($attrs as $name => $type) {
			$data[$name] = $this->$name;
		}
		return $data;
	}
	
	public function getDeleteCascades() {
		$cascade = array();
		if(!isset($this->_hasMany)) return $cascade;
		
		foreach($this->_hasMany as $model => $config) {
			if (isset($config['delete_cascade']) && $config['delete_cascade'] === true) {
				$fk = $config['foreign_key'];
				foreach($fk as $column => $value) {
					$fk[$column] = $this->$value;
				}
				$cascade[$model] = $fk;
			}
		}
		return $cascade;
	}
}
