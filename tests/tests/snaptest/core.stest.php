<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class Core_Test extends Snap_UnitTestCase
{
	public function setUp() {}
	public function tearDown() {}

	public function testGetCache()
	{
		$cache = sly_Core::cache();
		return $this->assertIsA($cache, 'BabelCache_Interface');
	}

	public function testGetSingleton()
	{
		$a = sly_Core::getInstance();
		$b = sly_Core::getInstance();
		return $this->assertIdentical($a, $b);
	}

	public function testGetClang()
	{
		$_REQUEST['clang'] = 0; // Eine Sprache 0 gibt es immer.
		$clang = sly_Core::getCurrentClang();
		return $this->assertEqual($clang, 0);
	}

	public function testGetNotExistingClang()
	{
		$_REQUEST['clang'] = -1;
		$clang = sly_Core::getCurrentClang();
		return $this->assertNotEqual($clang, -1);
	}

	public function testIsBackend()
	{
		return $this->assertTrue(sly_Core::isBackend());
	}
}
