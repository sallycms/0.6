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

abstract class sly_Service_Base
{
	protected $tablename;

	protected abstract function makeObject(array $params);

	protected function getTableName()
	{
		return $this->tablename;
	}

	public function create($params)
	{
		$model = $this->makeObject($params);
		return $this->save($model);
	}

	public function save(sly_Model_Base $model)
	{
		$persistence = sly_DB_Persistence::getInstance();

		if ($model->getId() == sly_Model_Base::NEW_ID) {
			$data = $model->toHash();
			unset($data['id']);
			$persistence->insert($this->getTableName(), $data);
			$model->setId($persistence->lastId());
		}
		else {
			$persistence->update($this->getTableName(), $model->toHash(), array('id' => $model->getId()));
		}

		return $model;
	}

	public function findById($id)
	{
		$res = $this->find(array('id' => (int)$id));
		if (count($res) == 1) return $res[0];
		return null;
	}

	public function find($where = null, $group = null, $order = null, $limit = null, $having = null)
	{
		$return      = array();
		$persistence = sly_DB_Persistence::getInstance();
		$persistence->select($this->getTableName(), '*', $where, $group, $order, $limit, $having);
		
		foreach ($persistence as $row) {
			$return[] = $this->makeObject($row);
		}
		
		return $return;
	}

	public function delete($where)
	{
		$persistence = sly_DB_Persistence::getInstance();
		return $persistence->delete($this->getTableName(), $where);
	}
}
