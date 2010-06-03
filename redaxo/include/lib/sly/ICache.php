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
 * Dieses Interface kann von Addons implementiert werden, um Redaxo eine
 * spezifische Cache-Implementierung zu geben. Für besondere Systemumgebungen
 * können speziell angepasste Cache-Implementierungen sinnvoll sein.
 *
 * Das Cache-Addon muss seine ICache Implementierung über
 * sly_Core::getInstance()->setCache($cache);
 * registrieren.
 */
interface sly_ICache {

	/**
	 * Setzt einen Wert in den Redaxo Cache
	 *
	 * @param String  $namespace    Der Namespace des abgeleten Wertes
	 * @param String  $key          Eindeutiger Identifier für den Cacheeintrag
	 * @param mixed   $value        Wert, der gespeichert werden soll
	 */
	public function set($namespace, $key, $value);

	/**
	 * Holt einen Wert zu einem Schlüssel aus dem Cache
	 *
	 * @param String  $namespace    Der Namespace des abgeleten Wertes
	 * @param String  $key      	Eindeutiger Identifier unter dem der Cacheeintrag erwartet wird
	 * @param mixed   $default  	Defaultwert, der bei einem Cache-Miss zurückgegeben wird
	 * @return mixed  				Den gewünschten Wert oder NULL
	 */
	public function get($namespace, $key, $default);

	/**
	 * Leert den Cache
	 */
	public function flush();

	/**
	 * Löscht einen Eintrag aus dem Cache
	 *
	 * @param String  $namespace    Der Namespace des abgeleten Wertes
	 * @param String  $key  		Eindeutiger Identifier unter dem der Cacheeintrag erwartet wird
	 */
	public function delete($namespace, $key);

}