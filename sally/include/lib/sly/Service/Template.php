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
 * Service-Klasse für Templates
 *
 * @author christoph@webvariants.de
 */
class sly_Service_Template extends sly_Service_DevelopBase {

	protected function isFileValid($filename) {
		return preg_match('#\.php$#i', $filename);
	}

	protected function getClassIdentifier() {
		return 'templates';
	}

	protected function getFileType($filename = '') {
		return 'default';
	}

	public function getFileTypes() {
		return array('default');
	}

	protected function buildData($filename, $mtime, $data) {
		$result = array(
			'filename' => $filename,
			'title'    => isset($data['title']) ? $data['title'] : $filename,
			'class'    => isset($data['class']) ? $data['class'] : null,
			'slots'    => sly_makeArray(isset($data['slots']) ? $data['slots'] : 1),
			'modules'  => isset($data['modules']) ? $data['modules'] : 'all',
			'mtime'    => $mtime
		);
		unset($data['name'], $data['title'], $data['class'], $data['slots'], $data['modules']);
		$result['params'] = $data;

		return $result;
	}

	protected function flush($name = null) {
		if ($name === null) {
			$dir   = new sly_Util_Directory($this->getCacheFolder());
			$files = $dir->listPlain(true, false, false, true, '');
		}
		elseif ($this->exists($name)) {
			$files = array(sly_Util_Directory::join($this->getFolder(), $this->getFilename($name)));
		}
		else {
			return false;
		}

		array_map('unlink', $files);
		return true;
	}

	public function getTemplates() {
		$result = array();
		foreach ($this->getData() as $name => $types) $result[$name] = $types['default']['title'];
		return $result;
	}

	public function getCacheFolder() {
		$dir = sly_Util_Directory::join(SLY_DYNFOLDER, 'internal/sally', $this->getClassIdentifier());
		if (!is_dir($dir) && !@mkdir($dir, sly_Core::config()->get('DIRPERM'), true)) throw new sly_Exception('Konnte Cache-Verzeichnis '.$dir.' nicht erstellen.');
		return $dir;
	}

	public function generate($name) {
		if (!$this->exists($name)) return false;

		$content = $this->getContent($name);
		if ($content === false) return false;

		foreach (sly_Core::getVarTypes() as $var) {
			$content = $var->getTemplate($content);
		}

		$templateFile = $this->getCacheFile($name);
		return file_put_contents($templateFile, $content) > 0;
	}

	public function getCacheFile($name) {
		return sly_Util_Directory::join($this->getCacheFolder(), $name.'.php');
	}

	public function getTitle($name, $default = '') {
		return $this->get($name, 'title', $default);
	}

	public function getClass($name, $default = '') {
		return $this->get($name, 'class', $default);
	}

	public function getSlots($name) {
		$slots = $this->get($name, 'slots', array());
		$slots = sly_makeArray($slots);
		if (empty($slots)) $slots = array(0 => 'default');
		return $slots;
	}

	public function getModules($name) {
		return $this->get($name, 'modules');
	}

	public function getFilename($name, $fullPath) {
		return $this->get($name, 'filename');
	}

	public function hasModule($template, $ctype, $module) {
		if (!$this->exists($template)) return false;

		$modules = $this->getModules($template);
		$modules = sly_makeArray($modules);

		return
			/* keine Angabe -> alle erlaubt */ !isset($modules[$ctype]) ||
			/* 'all' oder [all] angegeben   */ sly_makeArray($modules[$ctype]) == array('all') ||
			/* Modulkennung angegeben       */ in_array($module, sly_makeArray($modules[$ctype]));
	}
}
