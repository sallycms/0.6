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
 * @ingroup model
 */
class sly_Model_Language extends sly_Model_Base_Id {

	protected $name = '';
	protected $locale = '';
	protected $_attributes = array('name' => 'string', 'locale' => 'string');

	/**
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 *
	 * @return string
	 */
	public function getLocale() {
		return $this->locale;
	}

	/**
	 *
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = trim($name);
	}

	/**
	 *
	 * @param string $locale
	 */
	public function setLocale($locale) {
		$this->locale = trim($locale);
	}
}
