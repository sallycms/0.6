<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Sonstige Funktionen
 *
 * @package redaxo4
 */

/**
 * Funktion zum Anlegen eines Sprache-Objekts
 *
 * @param $locale Locale der Sprache
 * @param $searchpath Pfad zum Ordner indem die Sprachdatei gesucht werden soll
 * @param $setlocale true, wenn die locale für die Umgebung gesetzt werden soll, sonst false
 * @return unknown_type
 */
function rex_create_lang($locale = 'de_de', $searchpath = '', $setlocale = true)
{
	global $REX;

	$_searchpath = $searchpath;

	if (empty($searchpath)) {
		$searchpath = sly_Util_Directory::join(SLY_INCLUDE_PATH, 'lang');
	}

	$lang_object = new sly_I18N($locale, $searchpath);

	if (empty($_searchpath)) {
		$REX['LOCALES'] = $lang_object->getLocales($searchpath);
	}

	if ($setlocale) {
		sly_set_locale($lang_object);
	}

	return $lang_object;
}

function sly_set_locale(sly_I18N $i18n) {
	$locales = array();

	foreach (explode(',', $i18n->msg('setlocale')) as $locale) {
		$locales[] = $locale.'.UTF-8';
		$locales[] = $locale.'.UTF8';
		$locales[] = $locale.'.utf-8';
		$locales[] = $locale.'.utf8';
		$locales[] = $locale;
	}

	setlocale(LC_ALL, $locales);
}



/**
 * Returns the truncated $string
 *
 * @param  string $string  Searchstring
 * @param  string $start   Suffix to search for
 * @return string
 */
function truncate($string, $length = 80, $etc = '...', $breakWords = false)
{
	if ($length == 0) {
		return '';
	}

	if (strlen($string) > $length) {
		$length -= strlen($etc);

		if (!$breakWords) {
			$string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length + 1));
		}

		return substr($string, 0, $length).$etc;
	}

	return $string;
}

/**
 * Berechnet aus einem relativen Pfad einen absoluten
 */
function rex_absPath($rel_path, $rel_to_current = false)
{
	$stack = array();

	// Pfad relativ zum aktuellen Verzeichnis?
	// z.b. ../../files

	if ($rel_to_current) {
		$path  = realpath('.');
		$stack = explode(DIRECTORY_SEPARATOR, $path);
	}

	foreach (explode('/', $rel_path) as $dir) {
		// Aktuelles Verzeichnis, oder Ordner ohne Namen
		if ($dir == '.' || $dir == '') continue;

		// Zum Parent
		if ($dir == '..') {
			array_pop($stack);
		}
		// Normaler Ordner
		else {
			array_push($stack, $dir);
		}
	}

	return implode('/', $stack);
}

/**
 * Prüfen ob ein/e Datei/Ordner beschreibbar ist
 *
 * @access public
 * @param string $item Datei oder Verzeichnis
 * @return mixed true bei Erfolg, sonst Fehlermeldung
 */
function rex_is_writable($item)
{
	return _rex_is_writable_info(_rex_is_writable($item), $item);
}

function _rex_is_writable_info($is_writable, $item = '')
{
	global $I18N;

	$state = true;
	$key   = '';

	switch ($is_writable) {
		case 1:
			$key = 'setup_012';
			break;

		case 2:
			$key = 'setup_014';
			break;

		case 3:
			$key = 'setup_015';
			break;
	}

	if (!empty($key)) {
		$file = '';

		if (!empty($item)) {
			$file = '<strong>'.$item.'</strong>';
		}

		$state = $I18N->msg($key, '<span class="rex-error">', '</span>', rex_absPath($file));
	}

	return $state;
}

function _rex_is_writable($item)
{
	$status = 0;
	$level  = error_reporting(0);

	if (is_dir($item)) {
		if (!is_writable($item . '/.')) {
			$status = 1;
		}
	}
	elseif (is_file($item)) {
		if (!is_writable($item)) {
			$status = 2;
		}
	}
	else {
		$status = 3;
	}

	error_reporting($level);
	return $status;
}

function rex_getAttributes($name, $content, $default = null)
{
	$prop = unserialize($content);
	return isset($prop[$name]) ? $prop[$name] : $default;
}

function rex_setAttributes($name,$value,$content)
{
	$prop = unserialize($content);
	$prop[$name] = $value;
	return serialize($prop);
}

function array_insert($array, $index, $value)
{
	// In PHP5 akzeptiert array_merge nur Arrays. Deshalb hier $value als Array verpacken
	return array_merge(array_slice($array, 0, $index), array($value), array_slice($array, $index));
}

function rex_message($message, $cssClass, $sorroundTag)
{
	$return = '<div class="rex-message"><'.$sorroundTag.' class="'.$cssClass.'">';

	if ($sorroundTag != 'p') $return .= '<p>';
	$return .= '<span>'. $message .'</span>';
	if ($sorroundTag != 'p') $return .= '</p>';

	$return .= '</'.$sorroundTag.'></div>';
	return $return;
}

function rex_info($message, $cssClass = null, $sorroundTag = null)
{
	if (!$cssClass)    $cssClass    = 'rex-info';
	if (!$sorroundTag) $sorroundTag = 'div';
	return rex_message($message, $cssClass, $sorroundTag);
}

function rex_warning($message, $cssClass = null, $sorroundTag = null)
{
	if (!$cssClass)    $cssClass    = 'rex-warning';
	if (!$sorroundTag) $sorroundTag = 'div';
	return rex_message($message, $cssClass, $sorroundTag);
}

function rex_info_block($message, $cssClass = null, $sorroundTag = null)
{
	if (!$cssClass)    $cssClass    = 'rex-info-block';
	if (!$sorroundTag) $sorroundTag = 'div';
	return rex_message_block($message, $cssClass, $sorroundTag);
}

function rex_warning_block($message, $cssClass = null, $sorroundTag = null)
{
	if (!$cssClass)    $cssClass    = 'rex-warning-block';
	if (!$sorroundTag) $sorroundTag = 'div';
	return rex_message_block($message, $cssClass, $sorroundTag);
}

function rex_message_block($message, $cssClass, $sorroundTag)
{
	$return[] = '<div class="rex-message-block"><'.$sorroundTag.' class="'.$cssClass.'">';
	$return[] = '<div class="rex-message-content">';
	$return[] = $message;
	$return[] = '</div>';
	$return[] = '</'.$sorroundTag.'></div>';

	return implode('', $return);
}

/**
 *
 * @deprecated use sly_ini_get
 * @param <type> $key
 * @return <type>
 *
 */
function rex_ini_get($key)
{
	return sly_ini_get($key);
}

/**
 * Übersetzt den text $text, falls dieser mit dem prefix "translate:" beginnt.
 *
 * @param  string $text          der zu übersetzende Text
 * @param  mixed  $I18N_Catalog
 * @param  bool   $as_html       wenn true, wird das Ergebnis durch htmlspecialchars() behandelt
 * @return string                der übersetzte Wert
 */
function rex_translate($text, $I18N_Catalog = null, $as_html = true)
{
	if(!$I18N_Catalog) {
		global $REX, $I18N;

		if (!$I18N) {
			$I18N = rex_create_lang($REX['LANG']);
		}

		if (!$I18N) {
			trigger_error('Unable to create language "'.$REX['LANG'].'"', E_USER_ERROR);
		}

		return rex_translate($text, $I18N, $as_html);
	}

	$transKey = 'translate:';

	if (startsWith($text, $transKey)) {
		$text = $I18N_Catalog->msg(substr($text, strlen($transKey)));
	}

	if ($as_html) {
		return htmlspecialchars($text);
	}

	return $text;
}

/**
 * Leitet auf einen anderen Artikel weiter
 */
function rex_redirect($article_id, $clang = '', $params = array())
{
	while(ob_get_level()) ob_end_clean();
	header('Location: '.rex_getUrl($article_id, $clang, $params, '&'));
	exit();
}

/**
 * Trennt einen String an Leerzeichen auf.
 * Dabei wird beachtet, dass Strings in " zusammengehören
 */
function rex_split_string($string)
{
	$spacer = '@@@REX_SPACER@@@';
	$result = array();

	// TODO: mehrfachspaces hintereinander durch einfachen ersetzen
	$string = ' '.trim($string).' ';

	// Strings mit Quotes heraussuchen
	$pattern = '!(["\'])(.*)\\1!U';
	preg_match_all($pattern, $string, $matches);
	$quoted = isset($matches[2]) ? $matches[2] : array();

	// Strings mit Quotes maskieren
	$string = preg_replace($pattern, $spacer, $string);

	// ----------- z.b. 4 "av c" 'de f' ghi
	if (strpos($string, '=') === false) {
		$parts = explode(' ', $string);
		foreach ($parts as $part) {
			if (empty($part)) continue;

			if ($part == $spacer) {
				$result[] = array_shift($quoted);
			}
			else {
				$result[] = $part;
			}
		}
	}
	// ------------ z.b. a=4 b="av c" y='de f' z=ghi
	else {
		$parts = explode(' ', $string);
		foreach ($parts as $part) {
			if (empty($part)) continue;

			$variable = explode('=', $part);

			if (empty($variable[0]) || empty($variable[1])) {
				continue;
			}

			$var_name  = $variable[0];
			$var_value = $variable[1];

			if ($var_value == $spacer) {
				$var_value = array_shift($quoted);
			}

			$result[$var_name] = $var_value;
		}
	}

	return $result;
}

function rex_put_file_contents($path, $content)
{
	global $REX;

	$writtenBytes = file_put_contents($path, $content);
	@chmod($path, $REX['FILEPERM']);

	return $writtenBytes;
}

/**
 * @deprecated  nur zur REDAXO-Kompatibilität enthalten
 */
function rex_get_file_contents($path)
{
	return file_get_contents($path);
}

/**
 * @deprecated wir machen sowas nicht!
 */
function rex_replace_dynamic_contents($path, $content)
{
	if ($fcontent = file_get_contents($path)) {
		$content = "// --- DYN\n".trim($content)."\n// --- /DYN";
		$fcontent = preg_replace("#//.---.DYN.*//.---./DYN#s", $content, $fcontent);
		return rex_put_file_contents($path, $fcontent);
	}

	return false;
}

/**
 * Allgemeine Funktion die eine Datenbankspalte fortlaufend durchnummeriert.
 * Dies ist z.B. nützlich beim Umgang mit einer Prioritäts-Spalte
 *
 * @deprecated  extrem rechenaufwändig (SQL-Aufwand: n+1)
 */
function rex_organize_priorities($tableName, $priorColumnName, $whereCondition = '', $orderBy = '', $id_field = 'id')
{
	$update = new rex_sql();
	$select = new rex_sql();

	$qry = 'SELECT '.$id_field.' FROM '.$tableName;

	if (!empty($whereCondition)) {
		$qry .= ' WHERE '.$whereCondition;
	}

	if (!empty($orderBy)) {
		$qry .= ' ORDER BY '.$orderBy;
	}

	$select->setQuery($qry);
	$gu = rex_sql::getInstance();
	for ($i = 1; $i <= $select->getRows(); ++$i) {
		$gu->setQuery('UPDATE '.$tableName.' SET '.$priorColumnName.' = '.$i.' WHERE '.$id_field.' = '.$select->getValue($id_field));
		$select->next();
	}

	$select = null;
	$update = null;
	unset($select, $update);
}

function rex_lang_is_utf8()
{
	// In SallyCMS all backend locales are UTF-8.
	return true;
}

function rex_is_multilingual()
{
	return !rex_is_monolingual();
}

function rex_is_monolingual()
{
	global $REX;
	return count($REX['CLANG']) == 1;
}

function rex_is_backend()
{
	global $REX;
	return $REX['REDAXO'] ? true : false;
}

function rex_is_frontend()
{
	return !rex_is_backend();
}
/**
 * @deprecated
 * @return int clangId
 */
function rex_cur_clang()
{
	return (int) sly_Core::getCurrentClang();
}

function rex_get_clang($clang = false, $default = -1)
{
	global $REX;

	if ($clang === false) {
		$clang = $default;
	}

	if (!isset($REX['CLANG'][$clang])) {
		$clang = sly_Core::getCurrentClang();
	}

	return (int) $clang;
}

/**
 * @deprecated use sly_Util_String::isInteger($value) instead
 * @param <type> $value
 * @return <type>
 */
function rex_is_int($value)
{
	return sly_Util_String::isInteger($value);
}

/**
 * Returns true if $string starts with $start
 *
 * @param $string String Searchstring
 * @param $start String Prefix to search for
 * @author Markus Staab <staab@public-4u.de>
 */
if (!function_exists('startsWith')) {
	function startsWith($string, $start)
	{
		return sly_Util_String::startsWith($string, $start);
	}
}

/**
 * Returns true if $string ends with $end
 *
 * @deprecated
 * @param $string String Searchstring
 * @param $start String Suffix to search for
 * @author Markus Staab <staab@public-4u.de>
 */
if (!function_exists('endsWith')) {
	function endsWith($string, $end)
	{
		return sly_Util_String::endsWith($string, $end);
	}
}

// ------------------------------------- Allgemeine PHP Funktionen

function rex_highlight_string($string, $return = false)
{
	$s = '<p class="rex-code">'.highlight_string($string, true).'</p>';

	if ($return) {
		return $s;
	}

	print $s;
}

function rex_highlight_file($filename, $return = false)
{
	$s = '<p class="rex-code">'.highlight_file($filename, true).'</p>';

	if ($return) {
		return $s;
	}

	print $s;
}

function rex_exception(Exception $e)
{
	return rex_warning('Error in '.$e->getFile().' Line: '.$e->getLine().'<br />'.$e->getMessage());
}

/**
 * Somewhat naive way to determine if an array is a hash.
 * @deprecated check sly_Util_Array::isAssoc() instead
 */
function is_hash($array)
{
	return is_array($array) && sly_Util_Array::anyKey('is_string', $array);
}

// http://snippets.dzone.com/posts/show/4660
function array_flatten(array $array)
{
	$i = 0;
	$n = count($array);

	while ($i < $n) {
		if (is_array($array[$i])) {
			array_splice($array, $i, 1, $array[$i]);
		}
		else {
			++$i;
		}
	}

	return $array;
}
