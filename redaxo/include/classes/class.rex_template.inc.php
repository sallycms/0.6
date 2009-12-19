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

  function __construct($template_id = 0)
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
      $user_id = '.'.$REX['USER']->getValue('user_id');
    }
    else {
      $user_id = '';
    }
    
    return self::getTemplatesDir().'/'.$template_id.$user_id.'.template';
  }

  public static function getTemplatesDir()
  {
    global $REX;
    return $REX['INCLUDE_PATH'].'/generated/templates';
  }

  public function getTemplate()
  {
  	$file = $this->getFile();
  	if (!$file) return false;
  	return rex_get_file_contents($file);
  }

  public function generate()
  {
    global $REX;
    if ($this->getId() < 1) return false;

    include_once ($REX['INCLUDE_PATH'].'/functions/function_rex_generate.inc.php');
    return rex_generateTemplate($this->getId());
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
		$template_modules = rex_getAttributes('modules', $template_attributes, array ());
    
		if (!isset($template_modules[$ctype]['all']) || $template_modules[$ctype]['all'] == 1)
			return true;
		
		if (in_array($module_id, $template_modules[$ctype]))
			return true;
		
	  return false;
	}
}
