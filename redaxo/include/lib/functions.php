<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

function sly_get($name, $type, $default = '')
{
	$value = rex_get($name, $type, $default, false);
	$value = strtolower($type) == 'string' ? trim($value) : $value;
	return $value;
}

function sly_post($name, $type, $default = '')
{
	$value = rex_post($name, $type, $default, false);
	$value = strtolower($type) == 'string' ? trim($value) : $value;
	return $value;
}

function sly_request($name, $type, $default = '')
{
	$value = rex_request($name, $type, $default, false);
	$value = strtolower($type) == 'string' ? trim($value) : $value;
	return $value;
}

function sly_getArray($name, $types, $default = array())
{
	$values = sly_makeArray(isset($_GET[$name]) ? $_GET[$name] : $default);
	
	foreach ($values as &$value) {
		if (is_array($value)) {
			unset($value);
			continue;
		}
		
		$value = _rex_cast_var($value, $types, $default, 'found', false); // $default und 'found' ab REDAXO 4.2
		$value = strtolower($type) == 'string' ? trim($value) : $value;
	}
	
	return $values;
}

function sly_postArray($name, $types, $default = array())
{
	$values = sly_makeArray(isset($_POST[$name]) ? $_POST[$name] : $default);
	
	foreach ($values as $idx => &$value) {
		if (is_array($value)) {
			unset($values[$idx]);
			continue;
		}
		
		$value = _rex_cast_var($value, $types, $default, 'found', false); // $default und 'found' ab REDAXO 4.2
		$value = strtolower($types) == 'string' ? trim($value) : $value;
	}
	
	return $values;
}

function sly_requestArray($name, $types, $default = array())
{
	return isset($_POST[$name]) ?
		sly_postArray($name, $types, $default) :
		isset($_GET[$name]) ? sly_getArray($name, $types, $default) : $default;
}

function sly_isEmpty($var)
{
	return empty($var);
}

function sly_startsWith($haystack, $needle)
{
	return strlen($needle) <= strlen($haystack) && substr($haystack, 0, strlen($needle)) == $needle;
}

function sly_html($string)
{
	return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Schlüsselbasiertes Mergen
 *
 * Gibt es hierfür eine PHP-interne Alternative?
 *
 * @param  array $array1  das erste Array
 * @param  array $array2  das zweite Array
 * @return array          das Array mit den Werten aus beiden Arrays
 */
function sly_merge($array1, $array2)
{
	$result = $array1;
	foreach ($array2 as $key => $value) {
		if (!in_array($key, array_keys($result),true)) $result[$key] = $value;
	}
	return $result;
}

/**
 * Hilfsfunktion: Ersetzen von Werten in Array
 *
 * Sucht in einem Array nach Elementen und ersetzt jedes
 * Vorkommen durch einen neuen Wert.
 *
 * @param  array $array        das Such-Array
 * @param  mixed $needle       der zu suchende Wert
 * @param  mixed $replacement  der Ersetzungswert
 * @return array               das resultierende Array
 */
function sly_arrayReplace($array, $needle, $replacement)
{
	$i = array_search($needle, $array);
	if ($i === false) return $array;
	$array[$i] = $replacement;
	return sly_arrayReplace($array, $needle, $replacement);
}

/**
 * Hilfsfunktion: Löschen von Werten aus einem Array
 *
 * Sucht in einem Array nach Elementen und löscht jedes
 * Vorkommen.
 *
 * @param  array $array   das Such-Array
 * @param  mixed $needle  der zu suchende Wert
 * @return array          das resultierende Array
 */
function sly_arrayDelete($array, $needle)
{
	$i = array_search($needle, $array);
	if ($i === false) return $array;
	unset($array[$i]);
	return sly_arrayDelete($array, $needle);
}

/**
 * Hilfsfunktion: Anwenden eines Prädikats auf ein Array
 *
 * Gibt true zurück, wenn das Prädikat auf mindestens ein
 * Element des Arrays zutrifft.
 *
 * @param  string $predicate  das Prädikat (Funktionsname als String)
 * @param  array  $array      das Such-Array
 * @return bool               true, wenn das Prädikat mindestens 1x zutrifft
 */
function sly_arrayAny($predicate, $array)
{
	foreach ($array as $element) if ($predicate($element)) return true;
	return false;
}

/**
 * Hilfsfunktion: Anwenden eines Prädikats auf ein Array
 *
 * Gibt true zurück, wenn das Prädikat auf mindestens einen
 * Schlüssel des Arrays zutrifft.
 *
 * @param  string $predicate  das Prädikat (Funktionsname als String)
 * @param  array  $array      das Such-Array
 * @return bool               true, wenn das Prädikat mindestens 1x zutrifft
 */
function sly_arrayAnyKey($predicate, $array)
{
	return sly_arrayAny($predicate, array_keys($array));
}

/**
 * Macht aus einem Skalar ein Array
 *
 * @param  mixed $element  das Element
 * @return array           leeres Array für $element = null, einelementiges
 *                         Array für $element = Skalar, sonst direkt $element
 */
function sly_makeArray($element)
{
	if ($element === null)  return array();
	if (is_array($element)) return $element;
	return array($element);
}

/**
 * Text übersetzen
 *
 * @param  string $index  der zu übersetzende Begriff
 * @return string         die Übersetzung
 */
function t($index)
{
	global $REX;
	
	if ($REX['REDAXO']) {
		global $I18N;
		return $I18N->msg($index);
	}
	else {
		if (class_exists('WV9_Language')) {
			$wv9 = WV9_Language::getInstance();
			return $wv9->translate($index);
		}
	}

	return $index;
}

/**
 * Text übersetzen und auf HTML vorbereiten
 *
 * @param  string $index  der zu übersetzende Begriff
 * @return string         die Übersetzung, direkt mit htmlspecialchars() behandelt
 */
function ht($index)
{
	return sly_html(t($index));
}

function sly_ini_get($key, $default = null) {
	if (empty($key)) return $default;

	$res = trim(ini_get($key));

	// interpret numeric values
	if (preg_match('#(^[0-9]+)([ptgmk])$#i', $res, $matches)) {
		$last = strtolower($matches[2]);
		$res  = strtolower($matches[1]);

		switch ($last) {
			case 'p': $res *= 1024;
			case 't': $res *= 1024;
			case 'g': $res *= 1024;
			case 'm': $res *= 1024;
			case 'k': $res *= 1024;
		}
	}

	// interpret boolean values
	switch (strtolower($res)) {
		case 'on':
		case 'yes':
		case 'true':
			$res = true;
			break;
		case 'off':
		case 'no':
		case 'false':
			$res = false;
			break;
	}

	return $res;
}