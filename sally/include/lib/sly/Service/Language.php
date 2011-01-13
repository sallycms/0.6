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
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_Language extends sly_Service_Model_Base
{
	protected $tablename = 'clang';

	protected function makeObject(array $params)
	{
		return new sly_Model_Language($params);
	}

	public function add($name)
	{
		return $this->create(array('name' => $name));
	}
}
