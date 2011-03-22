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
 * Business Model Klasse für Kategorien
 *
 * @author christoph@webvariants.de
 */
class sly_Model_Category extends sly_Model_Article_Base {

	/**
	 *
	 * @return boolean
	 */
	public function isOnline() {
		return $this->getStatus() == 1;
	}

	/**
	 *
	 * @return boolean
	 */
	public function isOffline() {
		return !$this->isOnline();
	}
}
