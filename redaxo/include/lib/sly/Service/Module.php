<?php
/*
 * Copyright (c) 2009, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

/**
 * DB Model Klasse für Module
 * 
 * @author zozi@webvariants.de
 */
class sly_Service_Module extends sly_Service_Model_Base
{
	protected $tablename = 'module';

	protected function makeObject(array $params)
	{
		return new sly_Model_Module($params);
	}
	
	public function deleteWithActions(sly_Model_Module $module)
	{
		$this->delete(array('id' => $module->getId()));
		
		// Aktionen löschen
		
		$pdo = sly_DB_PDO_Persistence::getInstance();
		$pdo->delete('module_action', array('module_id' => $module->getId()));
	}
	
	public function findUsages(sly_Model_Module $module)
	{
		$prefix = sly_Core::config()->get('TABLE_PREFIX');
		$pdo    = sly_DB_PDO_Persistence::getInstance();
		$usages = array();
		
		if ($module->getId() == sly_Model_Base::NEW_ID) {
			return array();
		}
		
		$pdo->select(
			/* table  */ 'article_slice a',
			/* select */ 'a.article_id, a.clang, a.ctype',
			/* where  */ array('a.modultyp_id' => $module->getId()),
			/* group  */ 'a.article_id',
			/* ...    */ null, null, null, null,
			/* joins  */ 'LEFT JOIN '.$prefix.'module m ON a.modultyp_id = m.id'
		);
		
		foreach ($pdo as $row) {
			$row['article'] = OOArticle::getArticleById($row['article_id'], $row['clang']);
			$usages[$row['article_id']] = $row;
		}
		
		return $usages;
	}
}
