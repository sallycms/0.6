<?php

class Service_Factory_Test extends Snap_UnitTestCase
{
	public function setUp() {}
	public function tearDown() {}
	
	public function testGetService()
	{
		$service = sly_Service_Factory::getService('AddOn');
		return $this->assertIsA($service, 'sly_Service_AddOn');
	}
	
	public function testGetMissingService()
	{
		$this->willThrow('sly_Exception');
		$service = sly_Service_Factory::getService('FooBar'.uniqid());
	}
}
