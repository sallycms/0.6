<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_Service_ArticleBase extends sly_StructureTest {
	protected function getService() {
		static $service = null;
		if (!$service) $service = sly_Service_Factory::getArticleService();
		return $service;
	}

	protected function assertPosition($id, $pos, $clang = 1) {
		$service = $this->getService();
		$art     = $service->findById($id, $clang);
		$msg     = 'Position of article '.$id.' should be '.$pos.'.';

		$this->assertEquals($pos, $art->getPrior(), $msg);
	}

	protected function move($id, $to, $clang = 1) {
		$cat = $this->getService()->findById($id, $clang);
		$this->getService()->edit($id, $clang, $cat->getName(), $to);
	}
}
