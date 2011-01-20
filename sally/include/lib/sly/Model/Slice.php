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
 * Business Model Klasse für Slices
 *
 * @author  zozi@webvariants.de
 * @ingroup model
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
		$service  = sly_Service_Factory::getModuleService();
		$filename = $service->getOutputFilename($this->getModule());
		$output   = $service->getContent($filename);

		foreach (sly_Core::getVarTypes() as $idx => $var) {
			$output = $var->getFEOutput($this->getId(), $output);
		}

		return $output;
	}
}
