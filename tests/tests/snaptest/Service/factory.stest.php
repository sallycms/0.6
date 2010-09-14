<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

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

	public function testGetSingleton()
	{
		$a = sly_Service_Factory::getService('AddOn');
		$b = sly_Service_Factory::getService('AddOn');
		return $this->assertIdentical($a, $b);
	}
}
