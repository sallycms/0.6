<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * REX_VALUE[1],
 * REX_HTML_VALUE[1],
 * REX_PHP_VALUE[1],
 * REX_PHP,
 * REX_HTML,
 * REX_IS_VALUE
 *
 * @package redaxo4
 */
class rex_var_value extends rex_var
{
	// --------------------------------- Actions

	function getACRequestValues($REX_ACTION)
	{
		$values = rex_request('VALUE', 'array');
		foreach($values as $key => $value)
		{
			//TODO: wenn irgendwann rex_sql und damit der mquotes mist in rente ist das stripslashes wieder entfernen.
			$REX_ACTION['REX_VALUE'][$key] = stripslashes($value);
		}
		$REX_ACTION['REX_PHP'] = stripslashes(rex_request('INPUT_PHP', 'string'));
		$REX_ACTION['REX_HTML'] = self::stripPHP(stripslashes(rex_request('INPUT_HTML', 'string')));

		return $REX_ACTION;
	}

	function getACDatabaseValues($REX_ACTION, $slice_id)
	{
		$values = sly_Service_Factory::getService('SliceValue')->find(array('slice_id' => $slice_id, 'type' => 'REX_VALUE'));

		foreach($values as $value)
		{
			$REX_ACTION['REX_VALUE'][$value->getFinder()] = $value->getValue();
		}
		$REX_ACTION['REX_PHP'] = sly_Service_Factory::getService('SliceValue')->findBySliceTypeFinder($slice_id, 'REX_PHP', '');
		$REX_ACTION['REX_HTML'] = sly_Service_Factory::getService('SliceValue')->findBySliceTypeFinder($slice_id, 'REX_HTML', '');

		return $REX_ACTION;
	}

	function setACValues($slice_id, $REX_ACTION, $escape = false, $prependTableName = true)
	{
		//global $REX;

		//$slice_id = $sql->getValue('slice_id');
		$slice = sly_Service_Factory::getService('Slice')->findById($slice_id);
		if(isset($REX_ACTION['REX_VALUE'])){
			foreach($REX_ACTION['REX_VALUE'] as $key => $value){
				$slice->addValue('REX_VALUE', $key, $value);
			}
		}
		$slice->addValue('REX_PHP', '', $REX_ACTION['REX_PHP']);
		$slice->addValue('REX_HTML', '', $REX_ACTION['REX_HTML']);
	}

	// --------------------------------- Output

	function getBEOutput($slice_id, $content)
	{
		$content = $this->getOutput($slice_id, $content, true);

		$php_content = sly_Service_Factory::getService('SliceValue')->findBySliceTypeFinder($slice_id, 'REX_PHP', '');
		if($php_content){
			$php_content->getValue();
			if($php_content){
				$php_content = '';
			}
		}
		$php_content = rex_highlight_string($php_content, true);
		$content = str_replace('REX_PHP', self::stripPHP($php_content), $content);
		return $content;
	}

	function getBEInput($slice_id, $content)
	{
		$content = $this->getOutput($slice_id, $content);
		$php_content = sly_Service_Factory::getService('SliceValue')->findBySliceTypeFinder($slice_id, 'REX_PHP', '');
		if($php_content){
			$php_content = $php_content->getValue();
			if(!$php_content){
				$php_content = '';
			}
		}
		$content = str_replace('REX_PHP', htmlspecialchars($php_content,ENT_QUOTES, 'UTF-8'), $content);
		return $content;
	}

	function getFEOutput($slice_id, $content)
	{
		$content = $this->getOutput($slice_id, $content, true);
		$php_content = sly_Service_Factory::getService('SliceValue')->findBySliceTypeFinder($slice_id, 'REX_PHP', '');
		if($php_content){
			$php_content = $php_content->getValue();
			if(!$php_content){
				$php_content = '';
			}
		}
		$content = str_replace('REX_PHP', $php_content, $content);
		return $content;
	}

	function getOutput($slice_id, $content, $nl2br = false)
	{
		$content = $this->matchValue($slice_id, $content, $nl2br);
		$content = $this->matchHtmlValue($slice_id, $content);
		$content = $this->matchIsValue($slice_id, $content);
		$content = $this->matchPhpValue($slice_id, $content);

		$html_content = sly_Service_Factory::getService('SliceValue')->findBySliceTypeFinder($slice_id, 'REX_HTML', '');
		if($html_content){
			$html_content = $html_content->getValue();
			if(!$html_content){
				$html_content = '';
			}
		}else{
			$html_content = '';
		}
		$content = str_replace('REX_HTML', $html_content, $content);
		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	private function _matchValue($slice_id, $content, $var, $escape = false, $nl2br = false, $stripPHP = false, $booleanize = false)
	{
		$matches = $this->getVarParams($content, $var);


		foreach ($matches as $match)
		{
			list ($param_str, $args) = $match;
			list ($id, $args) = $this->extractArg('id', $args, 0);

			$replace = sly_Service_Factory::getService('SliceValue')->findBySliceTypeFinder($slice_id, 'REX_VALUE', $id);

			if($replace){
				$replace = $replace->getValue();
			}else{
				$replace = '';
			}

			if ($booleanize)
			{
				$replace = empty($replace);
			}
			else
			{
				if ($escape)
				{
					$replace = htmlspecialchars($replace,ENT_QUOTES);
				}

				if ($nl2br)
				{
					$replace = nl2br($replace);
				}

				if ($stripPHP)
				{
					$replace = self::stripPHP($replace);
				}

				$replace = $this->handleGlobalVarParams($var, $args, $replace);
				$content = str_replace($var . '[' . $param_str . ']', $replace, $content);
			}
		}


		return $content;
	}

	function matchValue($slice_id, $content, $nl2br = false)
	{
		return $this->_matchValue($slice_id, $content, 'REX_VALUE', true, $nl2br);
	}

	private function matchHtmlValue($slice_id, $content)
	{
		return $this->_matchValue($slice_id, $content, 'REX_HTML_VALUE', false, false, true);
	}

	private function matchPhpValue($slice_id, $content)
	{
		return $this->_matchValue($slice_id, $content, 'REX_PHP_VALUE', false, false, false);
	}

	private function matchIsValue($slice_id, $content)
	{
		return $this->_matchValue($slice_id, $content, 'REX_IS_VALUE', false, false, false, true);
	}
}