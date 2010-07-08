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
 * Business Model Klasse für Slices
 *
 * @author zozi@webvariants.de
 *
 */
class sly_Model_Slice extends sly_Model_Base {
	protected $module;
	protected $_attributes = array('module' => 'string');

	public function getModule()        { return $this->module; }
	public function setModule($module) { $this->module = $module; }

	public function addValue($type, $finder, $value = null) {
		$service = sly_Service_Factory::getService('SliceValue');
		return $service->create(array('slice_id' => $this->getId(), 'type' => $type, 'finder' => $finder, 'value' => $value));
	}

	/**
	 *
	 * @param string $type
	 * @param string $finder
	 *
	 * @return Model_SliceValue
	 */
	public function getValue($type, $finder) {
		$service    = sly_Service_Factory::getService('SliceValue');
		$sliceValue = $service->findBySliceTypeFinder(array('slice_id' => $this->getId(), 'type' => $type, 'finder' => $finder));
		return $sliceValue;
	}

	public function flushValues() {
		$service = sly_Service_Factory::getService('SliceValue');
		return $service->delete(array('slice_id' => $this->getId()));
	}

	public function getOutput() {
		$service = sly_Service_Factory::getService('Module');
		$output  = $service->getContent($this->getModule(), 'output');

		foreach (sly_Core::getVarTypes() as $idx => $var) {
			$output = $var->getFEOutput($this->getId(), $output);
		}

		return $output;
	}
}
