<?php

class Core_Test extends Snap_UnitTestCase
{
	public function setUp() {}
	public function tearDown() {}
	
	public function testGetCache()
	{
		$cache = sly_Core::cache();
		return $this->assertIsA($cache, 'sly_ICache');
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
