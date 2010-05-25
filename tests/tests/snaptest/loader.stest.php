<?php

class Loader_Test extends Snap_UnitTestCase
{
	public function setUp() {}
	public function tearDown() {}
	
	public function testLoadExisting()
	{
		$classToTest = 'sly_Util_Pager';
		sly_Loader::loadClass($classToTest);
		return $this->assertTrue(class_exists($classToTest, false));
	}
	
	public function testLoadNotExisting()
	{
		$classToTest = 'sly_Util_Pager'.uniqid();
		sly_Loader::loadClass($classToTest);
		return $this->assertFalse(class_exists($classToTest, false));
	}
}
