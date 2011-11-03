<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Helper_Modernizr {
	private static $data = null;

	public static function getCapabilities() {
		if (self::$data === null) {
			$cookie = isset($_COOKIE['sly_modernizr']) ? $_COOKIE['sly_modernizr'] : 'false';
			$cookie = @json_decode($cookie, true);

			self::$data = $cookie;
		}

		return self::$data;
	}

	public static function hasInputtype($type) {
		$data = self::getCapabilities();
		return !empty($data['inputtypes'][$type]);
	}

	public static function hasInput($type) {
		$data = self::getCapabilities();
		return !empty($data['input'][$type]);
	}
}
