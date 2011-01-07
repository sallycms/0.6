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
 * @ingroup layout
 */
class sly_Layout_XHTML5 extends sly_Layout_XHTML {
	protected $isTransitional = false;

	protected function setTransitional() {
		throw new sly_Exception('Cannot set transitional on XHTML5 layout.');
	}

	public function printHeader() {
		$this->renderView('views/layout/xhtml5/head.phtml');
	}
}
