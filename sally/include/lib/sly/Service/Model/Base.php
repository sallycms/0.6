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
 * @ingroup service
 */
abstract class sly_Service_Model_Base {
	protected $tablename;

	protected abstract function makeInstance(array $params);

	protected function getTableName() {
		return $this->tablename;
	}

	public function create($params) {
		$model = $this->makeInstance($params);
		return $this->save($model);
	}

	public function save(sly_Model_Base $model) {
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

	public function findById($id) {
		return $this->findOne(array('id' => (int)$id));
	}

	public function findOne($where = null, $group = null, $having = null) {
		$res = $this->find($where, $group, null, null, null, $having);
		if (count($res) == 1) return $res[0];
		return null;
	}

	public function find($where = null, $group = null, $order = null, $offset = null, $limit = null, $having = null) {
		$return      = array();
		$persistence = sly_DB_Persistence::getInstance();
		$persistence->select($this->getTableName(), '*', $where, $group, $order, $offset, $limit, $having);

		foreach ($persistence as $row) {
			$return[] = $this->makeInstance($row);
		}

		return $return;
	}

	public function delete($where) {
		$persistence = sly_DB_Persistence::getInstance();
		return $persistence->delete($this->getTableName(), $where);
	}
}
