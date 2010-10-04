<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Util {

	/**
	 * Includes a template
	 *
	 * This method includes a template. Params can be passed via an associative
	 * array in the second parameter. The parameters will be extracted to
	 * variables.
	 *
	 * @param mixed $template  Name of the template or an object of sly_Model_Template
	 * @param array $params    Template variables as an associative array of parameters
	 */
	public static function includeTemplate($name, $params = array()) {
		if ($template instanceof sly_Model_Template) {
			$template = $template->getName();
		}

		$service = sly_Service_Factory::getService('Template');
		if ($service instanceof sly_Service_Template) {
			$service->includeFile($name, $params);
		}
	}

}