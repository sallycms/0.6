<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Funktionen zur Registrierung von Schnittstellen (EXTENSION_POINTS)
 *
 * @package redaxo4
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
function rex_register_extension_point($extensionPoint, $subject = null, $params = array (), $read_only = false) {
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
function rex_register_extension($extensionPoint, $callable, $params = array()) {
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
function rex_extension_is_registered($extensionPoint) {
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
function rex_get_registered_extensions($extensionPoint) {
	$dispatcher = sly_Core::dispatcher();
	return $dispatcher->getListeners($extensionPoint);
}
