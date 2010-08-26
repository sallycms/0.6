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
 * @author  christoph@webvariants.de
 * @ingroup model
 */
class sly_Model_Language extends sly_Model_Base
{
	protected $name        = '';
	protected $_attributes = array('name' => 'string');

	public function getName()      { return $this->name;        }
	public function setName($name) { $this->name = trim($name); }
}
