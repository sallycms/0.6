<?php

/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_Language extends sly_Service_Model_Base {

	protected $tablename = 'clang';

	protected function makeInstance(array $params) {
		return new sly_Model_Language($params);
	}

	public function add($name, $locale) {
		global $REX;
		$sql = sly_DB_Persistence::getInstance();
		$sql->startTransaction();
		try {
			$newLanguage = $this->create(array('name' => $name, 'locale' => $locale));
			$sql->query(str_replace('#_', sly_Core::config()->get('DATABASE/TABLE_PREFIX'),
							'INSERT INTO #_article (id,re_id,name,catname,catprior,attributes,' .
							'startpage,prior,path,status,createdate,updatedate,type,clang,createuser,' .
							'updateuser,revision) ' .
							'SELECT id,re_id,name,catname,catprior,attributes,startpage,prior,path,0,createdate,' .
							'updatedate,type,?,createuser,updateuser,revision ' .
							'FROM #_article WHERE clang = 1'),
					array($newLanguage->getId())
			);
			$sql->doCommit();
		} catch (Exception $e) {
			$sql->cleanEndTransaction($e);
			throw $e;
		}
		
		sly_Core::dispatcher()->notify('CLANG_ADDED', '', array('id' => $newLanguage->getId(), 'language' => $newLanguage));
		$REX['CLANG'][$newLanguage->getId()] = $newLanguage;
		sly_Core::cache()->set('sly.language', 'all', $REX['CLANG']);
		return $newLanguage;
	}

}
