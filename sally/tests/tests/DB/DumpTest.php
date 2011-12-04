<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_DB_DumpTest extends PHPUnit_Framework_TestCase {
	public static function setUpBeforeClass() {
		$dir = dirname(__FILE__);

		file_put_contents("$dir/dumpA.sql", "-- Sally Database Dump Version 0.6\r\n-- Prefix foo_");
		file_put_contents("$dir/dumpB.sql", "-- Sally Database Dump Version 1\n");
		file_put_contents("$dir/dumpC.sql", "");
		file_put_contents("$dir/dumpD.sql", "-- Sally Database Dump Version 0.6\n-- Prefix "); // empty prefix!
	}

	public static function tearDownAfterClass() {
		$dir = dirname(__FILE__);

		unlink("$dir/dumpA.sql");
		unlink("$dir/dumpB.sql");
		unlink("$dir/dumpC.sql");
		unlink("$dir/dumpD.sql");
	}

	/**
	 * @dataProvider dumpProvider
	 */
	public function testGetProperties($dump, $version, $prefix) {
		$d = new sly_DB_Dump(dirname(__FILE__).'/'.$dump);
		$this->assertEquals($version, $d->getVersion());
		$this->assertEquals($prefix, $d->getPrefix());
	}

	public function dumpProvider() {
		return array(
			array('dumpA.sql', '0.6', 'foo_'),
			array('dumpB.sql', '1', false),
			array('dumpC.sql', false, false),
			array('dumpD.sql', '0.6', '')
		);
	}
}
