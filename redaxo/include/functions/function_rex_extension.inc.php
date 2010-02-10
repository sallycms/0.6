<?php

/**
 * Funktionen zur Registrierung von Schnittstellen (EXTENSION_POINTS)
 * @package redaxo4
 * @version svn:$Id$
 */

/**
 * Definiert einen Extension Point
 *
 * @param string $extensionPoint  Name des ExtensionPoints
 * @param mixed  $subject         Objekt/Variable die beeinflusst werden soll
 * @param array  $params          Parameter für die Callback-Funktion
 */
function rex_register_extension_point($extensionPoint, $subject = '', $params = array (), $read_only = false)
{
	global $REX;
	$result = $subject;

	if (!is_array($params)) {
		$params = array();
	}

	// Name des EP als Parameter mit übergeben
	$params['extension_point'] = $extensionPoint;

	if (isset($REX['EXTENSIONS'][$extensionPoint]) && is_array($REX['EXTENSIONS'][$extensionPoint])) {
		$params['subject'] = $subject;
		
		foreach ($REX['EXTENSIONS'][$extensionPoint] as $ext) {
			$func        = $ext[0];
			$localParams = array_merge($params, $ext[1]);
			$temp        = rex_call_func($func, $localParams);
			
			// Rückgabewert nur auswerten wenn auch einer vorhanden ist
			// damit $params['subject'] nicht verfälscht wird
			// null ist default Rückgabewert, falls kein RETURN in einer Funktion ist
			
			if (!$read_only && $temp !== null) {
				$result = $temp;
				$params['subject'] = $result;
			}
		}
	}
	
	return $result;
}

/**
 * Definiert eine Callback-Funktion, die an dem Extension Point $extension aufgerufen wird
 *
 * @param string $extension  Name des ExtensionPoints
 * @param mixed  $function   Name der Callback-Funktion
 * @param array  $params     Array von zus�tzlichen Parametern
 */
function rex_register_extension($extensionPoint, $callable, $params = array())
{
	global $REX;

	if (!is_array($params)) {
		$params = array();
	}
	
	$REX['EXTENSIONS'][$extensionPoint][] = array($callable, $params);
}

/**
 * Prüft ob eine extension für den angegebenen Extension Point definiert ist
 *
 * @param string $extensionPoint  Name des ExtensionPoints
 */
function rex_extension_is_registered($extensionPoint)
{
	global $REX;
	return !empty($REX['EXTENSIONS'][$extensionPoint]);
}

/**
 * Gibt ein Array mit Namen von Extensions zur�ck, die am angegebenen Extension Point definiert wurden
 *
 * @param string $extensionPoint  Name des ExtensionPoints
 */
function rex_get_registered_extensions($extensionPoint)
{
	global $REX;
	
	if (rex_extension_is_registered($extensionPoint)) {
		return $REX['EXTENSIONS'][$extensionPoint][0];
	}
	
	return array();
}

/**
 * Aufruf einer Funtion (Class-Member oder statische Funktion)
 *
 * @param string $function  Name der Callback-Funktion
 * @param array  $params    Parameter f�r die Funktion
 *
 * @example
 *   rex_call_func( 'myFunction', array( 'Param1' => 'ab', 'Param2' => 12))
 * @example
 *   rex_call_func( 'myClass::myMethod', array( 'Param1' => 'ab', 'Param2' => 12))
 * @example
 *   rex_call_func( array('myClass', 'myMethod'), array( 'Param1' => 'ab', 'Param2' => 12))
 * @example
 *   $myObject = new myObject();
 *   rex_call_func( array($myObject, 'myMethod'), array( 'Param1' => 'ab', 'Param2' => 12))
 */
function rex_call_func($function, $params, $parseParamsAsArray = true)
{
	if (!is_callable($function)) {
		trigger_error('rexCallFunc: Using of an unexpected function var "'.$function.'"!');
	}

	if ($parseParamsAsArray === true) {
		// Alle Parameter als ein Array übergeben
		// $function($params);
		return call_user_func($function, $params);
	}
	
	// Jeder index im Array ist ein Parameter
	// $function($params[0], $params[1], $params[2],...);
	return call_user_func_array($function, $params);
}
