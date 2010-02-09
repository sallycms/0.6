<?php

// Magic Quotes hinzufügen, falls sie deaktiviert sind.
// Dies ist aus Kompatibilitätsgründen zu REDAXO leider notwendig.

if (!get_magic_quotes_gpc()) {
	function addslashes_ref(&$value) {
		$value = addslashes($value);
	}
	
	array_walk_recursive($_GET,     'addslashes_ref');
	array_walk_recursive($_POST,    'addslashes_ref');
	array_walk_recursive($_COOKIE,  'addslashes_ref');
	array_walk_recursive($_REQUEST, 'addslashes_ref');
}

// Register Globals entfernen.
// Seit REDAXO 4.2 werden Globals nicht mehr benötigt, daher
// sollte das keine Kompatibilitätsprobleme verursachen.

if (ini_get('register_globals')) {
	$superglobals = array('_GET', '_POST', '_REQUEST', '_ENV', '_FILES', '_SESSION', '_COOKIE', '_SERVER');
	$keys         = array_keys($GLOBALS);
	
	foreach ($keys as $key) {
		if (!in_array($key, $superglobals) && $key != 'GLOBALS') {
			unset($$key);
		}
	}
	
	unset($superglobals, $key, $keys);
}

// So, jetzt haben wir eine saubere Grundlage für unsere Aufgaben.
