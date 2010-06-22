<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

/**
 * DB Model Klasse für Actions
 * 
 * @author christoph@webvariants.de
 */
class sly_Service_Action extends sly_Service_Model_Base
{
	protected $tablename = 'action';

	protected function makeObject(array $params)
	{
		return new sly_Model_Action($params);
	}
	
	public function findModules(sly_Model_Action $action)
	{
		$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
		$pdo    = sly_DB_Persistence::getInstance();
		$usages = array();
		
		if ($action->getId() == sly_Model_Base::NEW_ID) {
			return array();
		}
		
		$pdo->select(
			/* table  */ 'module m',
			/* select */ 'm.id',
			/* where  */ array('a.id' => $action->getId()),
			/* group  */ 'm.id', // Actions können mehrmals an ein Modul gebunden werden!
			/* ...    */ null, null, null, null,
			/* joins  */ 'LEFT JOIN '.$prefix.'module_action ma ON ma.module_id = m.id '.
			             'LEFT JOIN '.$prefix.'action a ON ma.action_id = a.id'
		);
		
		$moduleService = sly_Service_Factory::getService('Module');
		
		foreach ($pdo as $row) {
			extract($row);
			$usages[$id] = $moduleService->findById($id);
		}
		
		return $usages;
	}
}
