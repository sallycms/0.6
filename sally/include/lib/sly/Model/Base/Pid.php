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
 * Basisklasse für alle Models, die auf PIDs basieren
 *
 * @author  christoph@webvariants.de
 * @ingroup model
 */
abstract class sly_Model_Base_Pid extends sly_Model_Base {
	protected $pid = self::NEW_ID;
	protected $id  = 0;

	public function __construct($params = array()) {
		parent::__construct($params);
		if (isset($params['pid'])) $this->setPid($params['pid']);
	}

	public function getPid()     { return $this->pid;         }
	public function setPid($pid) { $this->pid = intval($pid); }

	public function toHash() {
		$hash = parent::toHash();
		$hash['pid'] = $this->pid;
		return $hash;
	}
}
