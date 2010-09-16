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
 * @ingroup util
 */
class sly_Util_Password {
	const ITERATIONS = 100;

	public static function hash($password, $salts = array()) {
		$password = self::iteratedHash($password);

		if (!is_array($salts)) {
			$args  = func_get_args();
			$salts = array_slice($args, 1); // $password abschneiden
		}

		foreach ($salts as $salt) {
			if (!is_string($salt) || empty($salt)) continue;
			$password = self::iteratedHash($password.str_repeat($salt, 15));
		}

		return $password;
	}

	protected static function iteratedHash($input) {
		for ($i = 0; $i < self::ITERATIONS; ++$i) $input = sha1($input);
		return $input;
	}
}