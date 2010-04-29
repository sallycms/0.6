<?php
/*
 * Copyright (c) 2009, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

function sly_get($name, $type, $default = '')
{
	$value = rex_get($name, $type, $default);
	$value = strtolower($type) == 'string' ? trim(stripslashes($value)) : $value;
	return $value;
}

function sly_post($name, $type, $default = '')
{
	$value = rex_post($name, $type, $default);
	$value = strtolower($type) == 'string' ? trim(stripslashes($value)) : $value;
	return $value;
}

function sly_request($name, $type, $default = '')
{
	$value = rex_request($name, $type, $default);
	$value = strtolower($type) == 'string' ? trim(stripslashes($value)) : $value;
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
		
		$value = _rex_cast_var($value, $types, $default, 'found'); // $default und 'found' ab REDAXO 4.2
		$value = strtolower($type) == 'string' ? trim(stripslashes($value)) : $value;
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
		
		$value = _rex_cast_var($value, $types, $default, 'found'); // $default und 'found' ab REDAXO 4.2
		$value = strtolower($types) == 'string' ? trim(stripslashes($value)) : $value;
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
