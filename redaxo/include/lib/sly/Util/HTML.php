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

class sly_Util_HTML
{
	public static function buildAttributeString($attributes)
	{
		$attributes = array_filter($attributes, array(__CLASS__, 'isAttribute'));

		foreach ($attributes as $key => &$value) {
			$value = strtolower($key).'="'.sly_html(trim($value)).'"';
		}

		return implode(' ', $attributes);
	}

	public static function startJavaScript()
	{
		ob_start();
		print "<script type=\"text/javascript\">\n// <![CDATA[\n";
	}

	public static function endJavaScript()
	{
		print "\n// ]]>\n</script>";
		print ob_get_clean();
	}

	public static function printJavaScript($content)
	{
		self::startJavaScript();
		print $content;
		self::endJavaScript();
	}

	public static function startOnDOMReady()
	{
		self::startJavaScript();
		print 'jQuery(function($) { ';
	}

	public static function endOnDOMReady()
	{
		print ' });';
		self::endJavaScript();
	}

	public static function onDOMReady($content)
	{
		self::startOnDOMReady();
		print $content;
		self::endOnDOMReady();
	}

	public static function isAttribute($value)
	{
		return $value !== false && strlen($value) > 0;
	}

	public static function concatValues(&$value, $key)
	{
		$value = strtolower(trim($key)).'="'.sly_html(trim($value)).'"';
	}
}
