<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_StructureTest extends sly_BaseTest {
	private static $origStart;
	private static $origNotFound;

	public static function setUpBeforeClass() {
		$conf = sly_Core::config();

		self::$origStart    = $conf->get('START_ARTICLE_ID');
		self::$origNotFound = $conf->get('NOTFOUND_ARTICLE_ID');

		$conf->set('START_ARTICLE_ID', 0);
		$conf->set('NOTFOUND_ARTICLE_ID', 0);
	}

	public static function tearDownAfterClass() {
		$conf = sly_Core::config();

		$conf->set('START_ARTICLE_ID', self::$origStart);
		$conf->set('NOTFOUND_ARTICLE_ID', self::$origNotFound);
	}

	protected function parseTree($tree) {
		$tree = preg_replace('/([0-9]+)</', '"\1":{', $tree);
		$tree = str_replace('>', '}', $tree);
		$tree = preg_replace('/(^|(?<=[{,]))\s*([0-9]+)\s*(?=([},]|$))/', '"\2":[]', $tree);

		return json_decode('{"0":{'.$tree.'}}', true);
	}

	protected function assertPositions(array $expected, $clang) {
		foreach ($expected as $idx => $id) {
			if ($id === null) continue;
			$this->assertPosition($id, $idx + 1, $clang);
		}
	}

	protected function moves($moves, $clang) {
		$moves = json_decode($moves, true);

		foreach ($moves as $move) {
			$this->move($move[0], $move[1], $clang);
		}
	}

	protected function makeMove($id, $to, array $expected, $clang) {
		$this->move($id, $to, $clang);
		$this->assertPositions($expected, $clang);
	}

	protected function assertTree($tree, $clang, $parent = 0) {
		$tree    = is_string($tree) ? $this->parseTree($tree) : $tree;
		$pos     = 1;
		$service = $this->getService();

		// $tree = array(1 => array(2 => array(3)))
		foreach ($tree as $elemID => $children) {
			if ($elemID > 0) {
				$elem = $service->findById($elemID, $clang);
				$msg  = 'Parent of element '.$elemID.' should be '.$parent.'.';

				$this->assertEquals($parent, $elem->getParentId(), $msg);
				$this->assertPosition($elemID, $pos, $clang);

				++$pos;
			}

			if (!empty($children)) {
				$this->assertTree($children, $clang, $elemID);
			}
		}
	}

	abstract protected function move($id, $to, $clang);
	abstract protected function assertPosition($id, $pos, $clang);
	abstract protected function getService();
}
