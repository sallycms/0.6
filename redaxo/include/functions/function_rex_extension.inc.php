<?php

/**
 * Funktionen zur Registrierung von Schnittstellen (EXTENSION_POINTS)
 * @package redaxo4
 * @version svn:$Id$
 */

/**
 * Definiert einen Extension Point
 *
 * Wird kein Subject (null) übergeben, findet immer ein Read-Only-Zugriff statt.
 *
 * @deprecated  see sly_Event_Dispatcher::notify() or sly_Event_Dispatcher::filter()
 *
 * @param string $extensionPoint  Name des ExtensionPoints
 * @param mixed  $subject         Objekt/Variable die beeinflusst werden soll
 * @param array  $params          Parameter für die Callback-Funktion
 */
function rex_register_extension_point($extensionPoint, $subject = null, $params = array (), $read_only = false)
{
	$dispatcher = sly_Core::dispatcher();
	$read_only |= $subject === null;
	
	if ($read_only) {
		$dispatcher->notify($extensionPoint, $subject, $params);
		return $subject;
	}
	else {
		$params = sly_makeArray($params);
		$params['extension_point'] = $extensionPoint; // REDAXO compatibility (sly_Event_Dispatcher adds 'event')
		return $dispatcher->filter($extensionPoint, $subject, $params);
	}
}

/**
 * Definiert eine Callback-Funktion, die an dem Extension Point $extension aufgerufen wird
 *
 * @deprecated  see sly_Event_Dispatcher::register()
 *
 * @param string $extension  Name des ExtensionPoints
 * @param mixed  $function   Name der Callback-Funktion
 * @param array  $params     Array von zusätzlichen Parametern
 */
function rex_register_extension($extensionPoint, $callable, $params = array())
{
	$dispatcher = sly_Core::dispatcher();
	$dispatcher->register($extensionPoint, $callable, $params);
}

/**
 * Prüft ob eine extension für den angegebenen Extension Point definiert ist
 *
 * @deprecated  see sly_Event_Dispatcher::hasListeners()
 *
 * @param string $extensionPoint  Name des ExtensionPoints
 */
function rex_extension_is_registered($extensionPoint)
{
	$dispatcher = sly_Core::dispatcher();
	return $dispatcher->hasListeners($extensionPoint);
}

/**
 * Gibt ein Array mit Namen von Extensions zurück, die am angegebenen Extension
 * Point definiert wurden
 *
 * @deprecated  see sly_Event_Dispatcher::getListeners()
 *
 * @param string $extensionPoint  Name des ExtensionPoints
 */
function rex_get_registered_extensions($extensionPoint)
{
	$dispatcher = sly_Core::dispatcher();
	return $dispatcher->getListeners($extensionPoint);
}

/**
 * Aufruf einer Funtion (Class-Member oder statische Funktion)
 *
 * @deprecated  bietet keinen wirklich Mehrwert
 *
 * @param string $function  Name der Callback-Funktion
 * @param array  $params    Parameter für die Funktion
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
