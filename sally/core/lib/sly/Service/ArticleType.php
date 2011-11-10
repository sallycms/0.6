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
 * Service-Klasse for Article types
 *
 * @author  zozi@webvariants.de
 * @ingroup service
 */
class sly_Service_ArticleType {
	const VIRTUAL_ALL_SLOT = '_ALL_'; ///< string

	private $data; ///< array

	public function __construct() {
		$this->data = (array) sly_Core::config()->get('ARTICLE_TYPES');
	}

	/**
	 * @return array
	 */
	public function getArticleTypes() {
		$types = array();
		foreach (array_keys($this->data) as $name) {
			$types[$name] = $this->getTitle($name);
		}
		return $types;
	}

	/**
	 * @param  string $articleType
	 * @param  string $property
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function get($articleType, $property, $default = '') {
		$this->exists($articleType, true);
		return isset($this->data[$articleType][$property]) ? $this->data[$articleType][$property] : $default;
	}

	/**
	 * @param  string $articleType
	 * @return string
	 */
	public function getTitle($articleType) {
		$title = $this->get($articleType, 'title');
		return empty($title) ? $articleType : $title;
	}

	/**
	 * @param  string $articleType
	 * @return string
	 */
	public function getTemplate($articleType) {
		return $this->get($articleType, 'template');
	}

	/**
	 * @throws sly_Exception
	 * @param  string  $articleType
	 * @param  boolean $throwException
	 * @return boolean
	 */
	public function exists($articleType, $throwException = false) {
		if (!array_key_exists($articleType, $this->data)) {
			if ($throwException) {
				throw new sly_Exception(t('exception_articletype_not_exists', $articleType));
			}

			return false;
		}

		return true;
	}

	/**
	 * Get the valid modules for the given article type and slot
	 *
	 * @param  string $articleType  article type name
	 * @param  string $slot         slot identifier
	 * @return array                module names
	 */
	public function getModules($articleType, $slot = null) {
		$moduleService   = sly_Service_Factory::getModuleService();
		$templateService = sly_Service_Factory::getTemplateService();
		$modules         = sly_makeArray($this->get($articleType, 'modules'));
		$template        = $this->getTemplate($articleType);
		$result          = array();

		// check if slot is valid
		if ($slot === null || $templateService->hasSlot($template, $slot)) {
			$allModules = array_keys($moduleService->getModules());

			// if there is no spec at all, allow all available modules
			if (empty($modules)) {
				$modules = $allModules;
			}

			// if there is a complex spec, we have to look a bit closer
			// $modules = {slotName: [mod,mod,mod], slotName: [mod,mod]
			elseif ($this->isModulesDefComplex($modules)) {
				// if the slot has not been specified, allow all modules
				if ($slot !== null && !array_key_exists($slot, $modules)) {
					$modules = $allModules;
				}

				// check the list
				else {
					$tmp = array();
					$all = self::VIRTUAL_ALL_SLOT;

					foreach ($modules as $key => $value) {
						// $key = 'slotName', $value = [mod,mod,...]
						if ($slot === null || $slot === $key || ($key === $all && !$templateService->hasSlot($template, $all))) {
							$value = sly_makeArray($value);
							$tmp   = array_merge($tmp, array_values($value));
						}
					}

					$modules = $tmp;
				}
			}

			// only return existing modules
			foreach ($modules as $module) {
				if ($moduleService->exists($module)) {
					$result[$module] = $moduleService->getTitle($module);
				}
			}
		}

		return $result;
	}

	/**
	 * Checks, if the module definitions are complex in the template
	 *
	 * complex: {slot1: wymeditor, slot2: [module1, module2]}
	 * simple:  [wymeditor, module1]
	 *
	 * @param  array $modules  array of modules
	 * @return boolean         true when the definition is complex
	 */
	private function isModulesDefComplex($modules) {
		return sly_Util_Array::isAssoc($modules) || sly_Util_Array::isMultiDim($modules);
	}

	/**
	 * Checks, if the template has a specific module
	 *
	 * @param  string $type    article type name
	 * @param  string $module  module name to check
	 * @param  string $slot    the template slot to check
	 * @return boolean         true when the module is allowed in the given template and slot
	 */
	public function hasModule($type, $module, $slot = null) {
		if (!$this->exists($type)) return false;

		$modules = $this->getModules($type, $slot);
		return array_key_exists($module, $modules);
	}
}
