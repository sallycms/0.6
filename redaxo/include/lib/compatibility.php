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

/**
 * Backport of array_replace_recursive for PHP < 5.3.0
 * 
 * Implementation used from http://de.php.net/manual/en/function.array-replace-recursive.php#92574
 * 
 * @author: Gregor at der-meyer dot de
 */
if (!function_exists('array_replace_recursive')) {
	function array_replace_recursive($array, $array1) {
		if (!function_exists('array_replace_recursive___recurse')) {
			function array_replace_recursive___recurse($array, $array1) {
				foreach ($array1 as $key => $value) {
					// create new key in $array, if it is empty or not an array
					if (!isset($array[$key]) || (isset($array[$key]) && !is_array($array[$key]))) {
						$array[$key] = array();
					}
	
					// overwrite the value in the base array
					if (is_array($value)) {
						$value = array_replace_recursive___recurse($array[$key], $value);
					}
					$array[$key] = $value;
				}
				return $array;
			}
		}

		// handle the arguments, merge one by one
		$args = func_get_args();
		$array = $args[0];
		if (!is_array($array)) {
			return $array;
		}
		for ($i = 1; $i < count($args); $i++) {
			if (is_array($args[$i])) {
				$array = array_replace_recursive___recurse($array, $args[$i]);
			}
		}
		return $array;
	}
}
