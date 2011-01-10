<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_CoreTest extends PHPUnit_Framework_TestCase {
	public function testGetCache() {
		$cache = sly_Core::cache();
		$this->assertInstanceOf('sly_ICache', $cache);
	}

	public function testGetSingleton() {
		$a = sly_Core::getInstance();
		$b = sly_Core::getInstance();
		return $this->assertSame($a, $b);
	}

	public function testGetClang() {
		$_REQUEST['clang'] = 0; // clang 0 always exists
		return $this->assertEquals(0, sly_Core::getCurrentClang());
	}

	public function testGetNotExistingClang() {
		$_REQUEST['clang'] = -1;
		return $this->assertNotEquals(-1, sly_Core::getCurrentClang());
	}

	public function testIsBackend() {
		return $this->assertTrue(sly_Core::isBackend());
	}
}
