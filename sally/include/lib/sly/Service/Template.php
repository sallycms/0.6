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
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_Template extends sly_Service_DevelopBase {

	const DEFAULT_TYPE = 'default';

	protected function isFileValid($filename) {
		return preg_match('#\.php$#i', $filename);
	}

	protected function getClassIdentifier() {
		return 'templates';
	}

	protected function getFileType($filename = '') {
		return self::DEFAULT_TYPE;
	}

	public function getFileTypes() {
		return array(self::DEFAULT_TYPE);
	}

	protected function buildData($filename, $mtime, $data) {
		$result = array(
			'filename' => $filename,
			'title'    => isset($data['title']) ? $data['title'] : $data['name'],
			'class'    => isset($data['class']) ? $data['class'] : null,
			'slots'    => sly_makeArray(isset($data['slots']) ? $data['slots'] : 0),
			'modules'  => isset($data['modules']) ? $data['modules'] : array(),
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
			$files = array($this->getCacheFile($name));
		}
		else {
			return false;
		}

		array_map('unlink', array_filter($files, 'file_exists'));
		return true;
	}

	/**
	 * Get available templates from this service
	 *
	 * Templates may be filtered by a class parameter. If class is set, only
	 * the from this class will be returned. If class is null, all templates
	 * will be returned.
	 *
	 * @param  string  $class  The class to filter. (default: null - no filtering)
	 * @return array           List of templates of the form: array('NAME' => 'TITLE', ...)
	 */
	public function getTemplates($class = null) {
		$result = array();
		foreach ($this->getData() as $name => $types) {
			if (empty($class) || $this->getClass($name) == $class) {
				$result[$name] = $types[self::DEFAULT_TYPE]['title'];
			}
		}
		return $result;
	}

	public function getCacheFolder() {
		$dir = sly_Util_Directory::join(SLY_DYNFOLDER, 'internal/sally', $this->getClassIdentifier());
		if (!is_dir($dir) && !@mkdir($dir, sly_Core::config()->get('DIRPERM'), true)) throw new sly_Exception('Konnte Cache-Verzeichnis '.$dir.' nicht erstellen.');
		return $dir;
	}

	public function generate($name) {
		if (!$this->exists($name)) throw new sly_Exception("Template '$name' does not exist.");

		$content = $this->getContent($name);

		foreach (sly_Core::getVarTypes() as $var) {
			$content = $var->getTemplate($content);
		}

		$templateFile = $this->getCacheFile($name);
		return file_put_contents($templateFile, $content) > 0;
	}

	public function isGenerated($name) {
		return file_exists($this->getCacheFile($name));
	}

	public function getCacheFile($name) {
		return sly_Util_Directory::join($this->getCacheFolder(), $name.'.php');
	}

	/**
	 * Return the title of the template
	 *
	 * @param  string  $name     Unique template name
	 * @param  string  $default  Default return value
	 * @return string            The Template title
	 */
	public function getTitle($name, $default = '') {
		return $this->get($name, 'title', $default);
	}

	/**
	 * Returns the class of a template
	 *
	 * The class may be used for classification and filtering
	 *
	 * @param  string  $name     Unique template name
	 * @param  string  $default  Default return value
	 * @return string            The templates class
	 */
	public function getClass($name, $default = '') {
		return $this->get($name, 'class', $default);
	}

	/**
	 * Gets the slots for a template
	 *
	 * The slots will be returned as an associative array.
	 *
	 * If the slots were defined as a list of titles it might look like:
	 * array(0 => 'Slot Title 1', 1 => 'Slot Title 2')
	 *
	 * If the slots were defined with names, it may look like:
	 * array('slot1' => 'Slot Title 1', 'slot2' => 'Slot Title 2')
	 *
	 * @param  string  $name  Template name
	 * @return array          Array of slots
	 */
	public function getSlots($name) {
		return $this->get($name, 'slots', array(0));
	}

	/**
	 * Checks, if the given template has a given slot
	 *
	 * @param  string  $name  Template name
	 * @param  string  $slot  Slot name
	 * @return boolean        true, when the template has this slot
	 */
	public function hasSlot($name, $slot) {
		$slots = $this->getSlots($name);
		return isset($slots[$slot]);
	}

	/**
	 * Get the valid modules for the given template and slot
	 *
	 * The modules are filtered by the constraints of the template and the
	 * modules.
	 *
	 * @param  string  $name  Template name
	 * @param  string  $slot  Slot identifier
	 * @return array          Array of module names
	 */
	public function getModules($name, $slot = null) {
		$moduleService = sly_Service_Factory::getService('Module');
		$modules       = sly_makeArray($this->get($name, 'modules'));
		$slots         = $this->getSlots($name);

		// only use _ALL_ keyword, when ther is no _ALL_ slot
		$checkAll      = array_search('_ALL_', $slots) === false;

		$result = array();

		// check if slot is valid
		if (empty($slot) || array_search($slot, $slots) !== false) {
			$allModules = array_keys($moduleService->getModules());

			// find modules for this template
			if (empty($modules)) $modules = $allModules;
			elseif ($this->isModulesDefComplex($modules)) {
				$tmp = array();
				foreach ($modules as $key => $value) {
					$value = sly_makeArray($value);
					if (empty($slot) || $slot == $key || ($checkAll && $key == '_ALL_')) {
						$tmp = array_merge($tmp, array_values($value));
					}
				}
				$modules = $tmp;
			}

			// filter modules by their constraints
			foreach ($modules as $module) {
				if ($moduleService->exists($module) && $moduleService->hasTemplate($module, $name)) {
					$result[$module] = $moduleService->getTitle($module);
				}
			}
		}

		return $result;
	}

	/**
	 * Checks, if the modules definitions are complex in the template
	 *
	 * complex: {slot1: wymeditor, slot2: [module1, module2]}
	 * simple:  [wymeditor, module1]
	 *
	 * @param  array  $modules  Array of modules parsed from the template
	 * @return boolean          true, when the definition is complex
	 */
	private function isModulesDefComplex($modules) {
		return sly_Util_Array::isAssoc($modules) || sly_Util_Array::isMultiDim($modules);
	}

	public function getFilename($name) {
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

	public function includeFile($name, $params = array()) {
		if (!$this->isGenerated($name)) $this->generate($name);
		$templateFile_C3476zz3g21ug327ur623 = $this->getCacheFile($name);
		if (!empty($params)) extract($params);
		include $templateFile_C3476zz3g21ug327ur623;
	}

}
