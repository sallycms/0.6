<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
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
