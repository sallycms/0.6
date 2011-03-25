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
 * @ingroup util
 */
class sly_Util_Language {
	public static function findAll() {
		static $languages = null;

		if ($languages === null) {
			$list      = sly_Service_Factory::getService('Language')->find();
			$languages = array();

			foreach ($list as $language) {
				$languages[$language->getId()] = $language;
			}
		}

		return $languages;
	}
}
