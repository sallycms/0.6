<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Service_CategoryTest extends PHPUnit_Framework_TestCase {
	private static $id;

	public static function setUpBeforeClass() {
		$service = sly_Service_Factory::getService('Category');
		self::$id = $service->add(0, 'Testkategorie', true, -1);
	}

	public static function tearDownAfterClass() {
		$service = sly_Service_Factory::getService('Category');
		$service->delete(self::$id);
	}

	public function testAdd() {
		$service = sly_Service_Factory::getService('Category');
		$newID   = $service->add(0, 'Meine "Kategorie"', true, -1);

		$this->assertInternalType('int', $newID);

		$cat = $service->findById($newID, 0);
		$this->assertEquals('Meine "Kategorie"', $cat->getName());
		$this->assertEquals('Meine "Kategorie"', $cat->getCatname());
		$this->assertEquals(1, $cat->getPrior());
		$this->assertEquals('|', $cat->getPath());
		$this->assertEquals(0, $cat->getReId());

		$service->delete($newID);
		$this->assertNull($service->findById($newID, 0));
	}

	public function testEdit() {
		$service = sly_Service_Factory::getService('Category');
		$service->edit(self::$id, 0, 'Neuer Titel', -1);
	}
}
