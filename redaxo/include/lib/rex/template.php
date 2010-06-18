<?php

/**
 * Template Objekt.
 * Zuständig für die Verarbeitung eines Templates
 *
 * @package redaxo4
 * @version svn:$Id$
 */

class rex_template
{
	protected $id;

	public function __construct($template_id = 0)
	{
		$this->setId($template_id);
	}

	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = (int) $id;
	}

	public function getFile()
	{
		$file = self::getFilePath($this->getId());
			
		if (!$file) {
			return false;
		}
			
		if (!file_exists($file)&& !$this->generate()) {
			trigger_error('Unable to generate rexTemplate with ID '.$this->getId(), E_USER_ERROR);
		}
			
		return $file;
	}

	public static function getFilePath($template_id)
	{
		global $REX;

		if ($template_id < 1) {
			return false;
		}

		$user_id = null;

		if (isset($REX['USER'])) {
			$user_id = '.'.$REX['USER']->getValue('id');
		}
		else {
			$user_id = '';
		}

		return self::getTemplatesDir().'/'.$template_id.$user_id.'.template';
	}

	public static function getTemplatesDir()
	{
		global $REX;
		return $REX['DYNFOLDER'].'/internal/sally/templates';
	}

	public function getTemplate()
	{
		$file = $this->getFile();
		if (!$file) return false;
		return file_get_contents($file);
	}

	public function generate()
	{
		global $REX;
		if ($this->getId() < 1) return false;

		$sql = rex_sql::getInstance();
		$qry = 'SELECT * FROM '. $REX['TABLE_PREFIX']  .'template WHERE id = '.$this->getId();
		$sql->setQuery($qry);

		if($sql->getRows() == 1)
		{
			$templatesDir = self::getTemplatesDir();
			$templateFile = self::getFilePath($this->getId());

			$content = $sql->getValue('content');
			foreach(sly_Core::getVarTypes() as $idx => $var)
			{
				$content = $var->getTemplate($content);
			}
			
			if(rex_put_file_contents($templateFile, $content) !== FALSE)
			{
				return TRUE;
			}
			else
			{
				trigger_error('Unable to generate template '. $this->getId() .'!', E_USER_ERROR);

				if(!is_writable()){
					trigger_error('directory "'. $templatesDir .'" is not writable!', E_USER_ERROR);
				}
			}
		}
		else
		{
			trigger_error('Template with id "'. $this->getId() .'" does not exist!', E_USER_ERROR);
		}
		return FALSE;
	}

	public function deleteCache()
	{
		global $REX;
		if ($this->id < 1) return false;

		$file = self::getFilePath($this->getId());
		return @unlink($file);
	}

	public static function hasModule($template_attributes, $ctype, $module_id)
	{
		$template_modules = rex_getAttributes('modules', $template_attributes, array());

		if (!isset($template_modules[$ctype]['all']) || $template_modules[$ctype]['all'] == 1)
			return true;

		if (in_array($module_id, $template_modules[$ctype]))
			return true;

		return false;
	}
}
