<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Business Model Klasse für Slice Values
 *
 * @author  zozi@webvariants.de
 * @ingroup model
 */
class sly_Model_SliceValue extends sly_Model_Base_Id {
	protected $slice_id; ///< int
	protected $finder;   ///< string
	protected $value;    ///< string

	protected $_attributes = array('slice_id' => 'int', 'finder' => 'string', 'value' => 'string'); ///< array

	public function __construct($params = array()) {
		parent::__construct($params);
		$this->value =json_decode($this->value);
	}

	public function getSliceId() { return $this->slice_id; } ///< @return int
	public function getFinder()  { return $this->finder;   } ///< @return string
	public function getValue()   { return $this->value;    } ///< @return string

	public function setSliceId($slice_id) { $this->slice_id = $slice_id; } ///< @param int    $slice_id
	public function setFinder($finder)    { $this->finder = $finder;     } ///< @param string $finder
	public function setValue($value)      { $this->value = $value;       } ///< @param string $value
}
