<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
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
			'title'     => isset($data['title']) ? $data['title'] : $filename,
			'actions'   => isset($data['actions']) ? $data['actions'] : array(),
			'templates' => isset($data['templates']) ? $data['templates'] : 'all',
			'mtime'     => $mtime
		);
		unset($data['name'], $data['title'], $data['actions']);
		$result['params'] = $data;

		return $result;
	}

	protected function flush($name = null) {
		$sql = sly_DB_Persistence::getInstance();
		$where = $name === null ? null : array('module' => $name);
		$sql->select('article_slice', 'slice_id', $where);

		foreach ($sql as $row) {
			rex_deleteCacheSliceContent((int) $row['slice_id']);
		}
	}

	public function getModules() {
		$result = array();

		foreach ($this->getData() as $name => $params) {
			$result[$name] = isset($params['input']) ? $params['input']['title'] : $params['output']['title'];
		}

		return $result;
	}

	public function getTitle($name, $default = '') {
		return $this->get($name, 'title', $default);
	}

	public function getActions($name) {
		return sly_makeArray($this->get($name, 'actions', array()));
	}

	public function getInputFilename($name, $fullPath) {
		return $this->get($name, 'filename', false, 'input');
	}

	public function getOutputFilename($name, $fullPath) {
		return $this->get($name, 'filename', false, 'output');
	}

	public function hasTemplate($name) {
		return true;
	}

}
