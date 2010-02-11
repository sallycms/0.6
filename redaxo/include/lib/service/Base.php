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

abstract class Service_Base {
	
	protected $tablename;
	
	protected abstract function makeObject(array $params);
	
	protected function getTableName() {
        return $this->tablename;
    }
    
	public function create($params) {
		
        $model = $this->makeObject($params);
        return $this->save($model);
    }
    
    public function save(Model_Base $model) {
    	$persistence = DB_PDO_Persistence::getInstance();

    	if($model->getId() == Model_Base::NEW_ID) {
    		$data = $model->toAssocArray();
    		unset($data['id']);
            $persistence->insert($this->getTableName(), $data);
            $model->setId($persistence->lastId());
        }else {
            $persistence->update($this->getTableName(), $model->toAssocArray(), array('id' => $model->getId()));
        }

        return $model;
    }
    
    public function findById($id){
    	return $this->find(array('id' => (int)$id));
    }
    
	public function find($where = null, $group = null, $order = null, $limit = null, $having = null) {

		$return = array();
        $persistence = DB_PDO_Persistence::getInstance();
        $persistence->select($this->getTableName(), '*', $where, $group, $order, $limit, $having);
        foreach ($persistence as $row) {
            $return[] = $this->makeObject($row);
        }
        return $return;
    }
    
    public function delete($where) {
    	$persistence = DB_PDO_Persistence::getInstance();
    	return $persistence->delete($this->getTableName(), $where);
    }

}
