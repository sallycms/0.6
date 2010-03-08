<?php


/**
 * REX_VALUE[1],
 * REX_HTML_VALUE[1],
 * REX_PHP_VALUE[1],
 * REX_PHP,
 * REX_HTML,
 * REX_IS_VALUE
 *
 * @package redaxo4
 * @version svn:$Id$
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

	function getACDatabaseValues($REX_ACTION, & $sql)
	{
		$slice_id = $this->getValue($sql, 'slice_id');
		$values = Service_Factory::getService('SliceValue')->find(array('slice_id' => $slice_id, 'type' => 'REX_VALUE'));

		foreach($values as $value)
		{
			$REX_ACTION['REX_VALUE'][$value->getFinder()] = $value->getValue();
		}
		$REX_ACTION['REX_PHP'] = Service_Factory::getService('SliceValue')->findBySliceTypeFinder($slice_id, 'REX_PHP', '');
		$REX_ACTION['REX_HTML'] = Service_Factory::getService('SliceValue')->findBySliceTypeFinder($slice_id, 'REX_HTML', '');

		return $REX_ACTION;
	}

	function setACValues(& $sql, $REX_ACTION, $escape = false, $prependTableName = true)
	{
		global $REX;

		$slice_id = $sql->getValue('slice_id');
		$slice = Service_Factory::getService('Slice')->findById($slice_id);
		if(isset($REX_ACTION['REX_VALUE'])){
			foreach($REX_ACTION['REX_VALUE'] as $key => $value){
				$slice->addValue('REX_VALUE', $key, $value);
			}
		}
		$slice->addValue('REX_PHP', '', $REX_ACTION['REX_PHP']);
		$slice->addValue('REX_HTML', '', $REX_ACTION['REX_HTML']);
	}

	// --------------------------------- Output

	function getBEOutput(& $sql, $content)
	{
		$content = $this->getOutput($sql, $content, true);

		$slice_id = $this->getValue($sql, 'slice_id');
		$php_content = Service_Factory::getService('SliceValue')->findBySliceTypeFinder($slice_id, 'REX_PHP', '');
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

	function getBEInput(& $sql, $content)
	{
		$content = $this->getOutput($sql, $content);
		$slice_id = $this->getValue($sql, 'slice_id');
		$php_content = Service_Factory::getService('SliceValue')->findBySliceTypeFinder($slice_id, 'REX_PHP', '');
		if($php_content){
			$php_content = $php_content->getValue();
			if(!$php_content){
				$php_content = '';
			}
		}
		$content = str_replace('REX_PHP', htmlspecialchars($php_content,ENT_QUOTES, 'UTF-8'), $content);
		return $content;
	}

	function getFEOutput(& $sql, $content)
	{
		$content = $this->getOutput($sql, $content, true);
		$slice_id = $this->getValue($sql, 'slice_id');
		$php_content = Service_Factory::getService('SliceValue')->findBySliceTypeFinder($slice_id, 'REX_PHP', '');
		if($php_content){
			$php_content = $php_content->getValue();
			if(!$php_content){
				$php_content = '';
			}
		}
		$content = str_replace('REX_PHP', $php_content, $content);
		return $content;
	}

	function getOutput(& $sql, $content, $nl2br = false)
	{
		$content = $this->matchValue($sql, $content, $nl2br);
		$content = $this->matchHtmlValue($sql, $content);
		$content = $this->matchIsValue($sql, $content);
		$content = $this->matchPhpValue($sql, $content);

		$slice_id = $this->getValue($sql, 'slice_id');
		$html_content = Service_Factory::getService('SliceValue')->findBySliceTypeFinder($slice_id, 'REX_HTML', '');
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
	private function _matchValue(& $sql, $content, $var, $escape = false, $nl2br = false, $stripPHP = false, $booleanize = false)
	{
		$matches = $this->getVarParams($content, $var);


		foreach ($matches as $match)
		{
			list ($param_str, $args) = $match;
			list ($id, $args) = $this->extractArg('id', $args, 0);

			$slice_id = $this->getValue($sql, 'slice_id');
			$replace = Service_Factory::getService('SliceValue')->findBySliceTypeFinder($slice_id, 'REX_VALUE', $id);

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

	function matchValue(& $sql, $content, $nl2br = false)
	{
		return $this->_matchValue($sql, $content, 'REX_VALUE', true, $nl2br);
	}

	private function matchHtmlValue(& $sql, $content)
	{
		return $this->_matchValue($sql, $content, 'REX_HTML_VALUE', false, false, true);
	}

	private function matchPhpValue(& $sql, $content)
	{
		return $this->_matchValue($sql, $content, 'REX_PHP_VALUE', false, false, false);
	}

	private function matchIsValue(& $sql, $content)
	{
		return $this->_matchValue($sql, $content, 'REX_IS_VALUE', false, false, false, true);
	}
}