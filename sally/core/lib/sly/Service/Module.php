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
 * Service-Klasse für Module
 *
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_Module extends sly_Service_DevelopBase {

	protected function isFileValid($filename) {
		return preg_match('#\.(input|output)\.php$#i', $filename);
	}

	protected function getClassIdentifier() {
		return 'modules';
	}

	protected function getFileType($filename = '') {
		return substr($filename, -10) == '.input.php' ? 'input' : 'output';
	}

	public function getFileTypes() {
		return array('input', 'output');
	}

	protected function buildData($filename, $mtime, $data) {
		$result = array(
			'filename'  => $filename,
			'title'     => isset($data['title']) ? $data['title'] : $data['name'],
			'actions'   => isset($data['actions']) ? $data['actions'] : array(),
			'templates' => isset($data['templates']) ? $data['templates'] : 'all',
			'mtime'     => $mtime
		);
		unset($data['name'], $data['title'], $data['actions']);
		$result['params'] = $data;

		return $result;
	}

	protected function flush($name = null, $filename = null) {
		$sql = sly_DB_Persistence::getInstance();
		$where = $name === null ? null : array('module' => $name);
		$sql->select('slice', 'id', $where);

		foreach ($sql as $row) {
			rex_deleteCacheSliceContent((int) $row['id']);
		}
	}

	/**
	 * Get available modules from the service
	 *
	 * @return array  Array of modules
	 */
	public function getModules() {
		$result = array();

		foreach ($this->getData() as $name => $params) {
			$result[$name] = isset($params['input']) ? $this->get($name, 'title', '', 'input') : $this->get($name, 'title', '', 'output');
		}

		return $result;
	}

	/**
	 * Get the title of a module
	 *
	 * @param  string  $name  Unique module name
	 * @return string         The title of the module
	 */
	public function getTitle($name) {
		return $this->get($name, 'title', '');
	}

	/**
	 * Get the available actions for this module
	 *
	 * @param  string  $name  Unique module name
	 * @return array          Array of action names
	 */
	public function getActions($name) {
		return sly_makeArray($this->get($name, 'actions', array()));
	}

	/**
	 * Get the filename of the modules input file
	 *
	 * @param  string  $name  Unique module name
	 * @return string         The filename of the input file
	 */
	public function getInputFilename($name) {
		return $this->get($name, 'filename', null, 'input');
	}

	/**
	 * Get the filename of the modules output file
	 *
	 * @param  string  $name  Unique module name
	 * @return string         The filename of the output file
	 */
	public function getOutputFilename($name) {
		return $this->filterByCondition($name, 'output');
	}

	/**
	 * Get a list of templates where this module may be used
	 *
	 * This list is NOT affected by constraints made in template configuration.
	 *
	 * @param  string  $name  Unique module name
	 * @return string         List of templates
	 */
	public function getTemplates($name) {
		static $templates;
		if (!isset($templates[$name])) {
			$t = $this->get($name, 'templates');
			if ($t === 'all') $t = array_keys(sly_Service_Factory::getTemplateService()->getTemplates());
			$templates[$name] = sly_makeArray($t);
		}
		return $templates[$name];
	}

	/**
	 * Checks, if a module may be used with a given template
	 *
	 * @param  string  $name          Unique module name
	 * @param  string  $templateName  Unique template name
	 * @return boolean                true, when the module may be used in the given template
	 */
	public function hasTemplate($name, $templateName) {
		$templates = self::getTemplates($name);
		if(!empty($templates))
			return in_array($templateName, $templates);

		return true;
	}

}
