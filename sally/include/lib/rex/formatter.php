<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Klasse zur Formatierung von Strings
 *
 * @ingroup redaxo
 */
abstract class rex_formatter
{
	/**
	 * Formatiert den String <code>$value</code>
	 *
	 * @param $value zu formatierender String
	 * @param $format_type Formatierungstype
	 * @param $format Format
	 *
	 * Unterst�tzte Formatierugen:
	 *
	 * - <Formatierungstype>
	 *    + <Format>
	 *
	 * - sprintf
	 *    + siehe www.php.net/sprintf
	 * - date
	 *    + siehe www.php.net/date
	 * - strftime
	 *    + dateformat
	 *    + datetime
	 *    + siehe www.php.net/strftime
	 * - number
	 *    + siehe www.php.net/number_format
	 *    + array( <Kommastelle>, <Dezimal Trennzeichen>, <Tausender Trennzeichen>)
	 * - email
	 *    + array( 'attr' => <Linkattribute>, 'params' => <Linkparameter>,
	 * - url
	 *    + array( 'attr' => <Linkattribute>, 'params' => <Linkparameter>,
	 * - truncate
	 *    + array( 'length' => <String-Laenge>, 'etc' => <ETC Zeichen>, 'break_words' => <true/false>,
	 * - nl2br
	 *    + siehe www.php.net/nl2br
	 * - rexmedia
	 *    + formatiert ein Medium via OOMedia
	 * - custom
	 *    + formatiert den Wert anhand einer Benutzer definierten Callback Funktion
	 */
	public static function format($value, $format_type, $format)
	{
		// Stringformatierung mit sprintf()
		if ($format_type == 'sprintf') {
			$value = rex_formatter::_formatSprintf($value, $format);
		}
		// Datumsformatierung mit date()
		elseif ($format_type == 'date') {
			$value = rex_formatter::_formatDate($value, $format);
		}
		// Datumsformatierung mit strftime()
		elseif ($format_type == 'strftime') {
			$value = rex_formatter::_formatStrftime($value, $format);
		}
		// Zahlenformatierung mit number_format()
		elseif ($format_type == 'number') {
			$value = rex_formatter::_formatNumber($value, $format);
		}
		// Email-Mailto Linkformatierung
		elseif ($format_type == 'email') {
			$value = rex_formatter::_formatEmail($value, $format);
		}
		// URL-Formatierung
		elseif ($format_type == 'url') {
			$value = rex_formatter::_formatUrl($value, $format);
		}
		// String auf eine eine Länge abschneiden
		elseif ($format_type == 'truncate') {
			$value = rex_formatter::_formatTruncate($value, $format);
		}
		// Newlines zu <br />
		elseif ($format_type == 'nl2br') {
			$value = rex_formatter::_formatNl2br($value, $format);
		}
		// REDAXO Medienpool files darstellen
		elseif ($format_type == 'rexmedia' && $value != '') {
			$value = rex_formatter::_formatRexMedia($value, $format);
		}
		// Artikel Id-Clang Id mit rex_getUrl() darstellen
		elseif ($format_type == 'rexurl' && $value != '') {
			$value = rex_formatter::_formatRexUrl($value, $format);
		}
		// Benutzerdefinierte Callback-Funktion
		elseif ($format_type == 'custom') {
			$value = rex_formatter::_formatCustom($value, $format);
		}

		return $value;
	}

	private static function _formatSprintf($value, $format)
	{
		if (empty($format)) $format = '%s';
		return sprintf($format, $value);
	}

	private static function _formatDate($value, $format)
	{
		if (empty($format)) $format = 'd.m.Y';
		return date($format, $value);
	}

	private static function _formatStrftime($value, $format)
	{
		global $I18N;

		if (empty($value)) return '';
		if (!is_object($I18N)) $I18N = rex_create_lang();

		if ($format == '' || $format == 'date') {
			// Default REX-Dateformat
			$format = $I18N->msg('dateformat');
		}
		elseif ($format == 'datetime') {
			// Default REX-Datetimeformat
			$format = $I18N->msg('datetimeformat');
		}

		return strftime($format, $value);
	}

	private static function _formatNumber($value, $format)
	{
		if (!is_array($format)) $format = array();

		if (empty($format[0])) $format[0] = 2;   // Kommastellen
		if (empty($format[1])) $format[1] = ','; // Dezimal Trennzeichen
		if (empty($format[2])) $format[2] = ' '; // Tausender Trennzeichen

		return number_format($value, $format[0], $format[1], $format[2]);
	}

	private static function _formatEmail($value, $format)
	{
		if (!is_array($format)) $format = array();

		// Linkattribute
		if (empty ($format['attr'])) {
			$format['attr'] = '';
		}

		// Linkparameter (z.b. subject=Hallo Sir)
		if (empty($format['params'])) {
			$format['params'] = '';
		}
		elseif ($format['params'][0] != '?') {
			$format['params'] = '?'.$format['params'];
		}

		// URL-Formatierung
		return '<a href="mailto:'.$value.$format['params'].'"'.$format['attr'].'>'.$value.'</a>';
	}

	private static function _formatUrl($value, $format)
	{
		if (empty($value)) return '';
		if (!is_array($format)) $format = array();

		// Linkattribute
		if (empty($format['attr'])) {
			$format['attr'] = '';
		}

		// Linkparameter (z.b. subject=Hallo Sir)
		if (empty ($format['params'])) {
			$format['params'] = '';
		}
		elseif ($format['params'][0] != '?') {
			$format['params'] = '?'.$format['params'];
		}

		// Protokoll
		if (!preg_match('@((ht|f)tps?|telnet|redaxo|sally)://@', $value)) {
			$value = 'http://'.$value;
		}

		return '<a href="'.$value.$format['params'].'"'.$format['attr'].'>'.$value.'</a>';
	}

	private static function _formatTruncate($value, $format)
	{
		if (!is_array($format)) $format = array();

		if (empty($format['length']))      $format['length']      = 80;
		if (empty($format['etc']))         $format['etc']         = '...';
		if (empty($format['break_words'])) $format['break_words'] = false;

		return truncate($value, $format['length'], $format['etc'], $format['break_words']);
	}

	private static function _formatNl2br($value, $format)
	{
		return nl2br($value);
	}

	private static function _formatCustom($value, $format)
	{
		if (!is_callable($format)) {
			if(!is_callable($format[0])) {
				trigger_error('Unable to find callable '.$format[0].' for custom format!');
			}

			$params['subject'] = $value;

			if (is_array($format[1])) {
				$params = array_merge($format[1], $params);
			}
			else {
				$params['params'] = $format[1];
			}

			// $format ist in der Form
			// array(Name des Callables, Weitere Parameter)
			return call_user_func($format[0], $params);
		}

		return call_user_func($format, $value);
	}

	private static function _formatRexMedia($value, $format)
	{
		if (!is_array($format)) $format = array('params' => array());
		$params = $format['params'];

		// Resize aktivieren, falls nicht anders übergeben

		if (empty($params['resize'])) {
			$params['resize'] = true;
		}

		$media = OOMedia::getMediaByName($value);

		// Bilder als Thumbnail
		if ($media->isImage()) {
			$value = $media->toImage($params);
		}
		// Sonstige mit Mime-Icons
		else {
			$value = $media->toIcon();
		}

		return $value;
	}

	private static function _formatRexUrl($value, $format)
	{
		if (empty($value)) return '';
		if (!is_array($format)) $format = array();

		// Format, in dem die Werte gespeichert sind

		if (empty($format['format'])) {
			// default: <article-id>-<clang-id>
			$format['format'] = '%i-%i';
		}

		$hits = sscanf($value, $format['format'], $value, $format['clang']);

		if ($hits == 1 && empty($format['clang'])) {
			$format['clang'] = false;
		}

		// Linkparameter (z.b. subject=Hallo Sir)
		if (empty ($format['params'])) {
			$format['params'] = '';
		}
		elseif ($format['params'][0] != '?') {
			$format['params'] = '?'.$format['params'];
		}

		// divider

		if (empty($format['divider'])) {
			$format['divider'] = '&amp;';
		}

		$name = 'NoName';
		$art  = OOArticle::getArticleById($value, $format['clang']);

		if ($art) {
			$name = $art->getName();
		}

		return '<a href="'.rex_getUrl($value, $format['clang'], $name, $format['params'], $format['divider']).'"'.$format['attr'].'>'.$value.'</a>';
	}
}
