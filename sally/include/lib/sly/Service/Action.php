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
 * DB Model Klasse für Actions
 *
 * @author  christoph@webvariants.de
 * @ingroup service
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
