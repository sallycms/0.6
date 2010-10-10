<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * REX_TEMPLATE[2]
 *
 * @ingroup redaxo
 */
class rex_var_template extends rex_var
{
  // --------------------------------- Output

  function getBEOutput(& $sql, $content)
  {
    return $this->matchTemplate($content);
  }

  function getTemplate($content)
  {
    return $this->matchTemplate($content);
  }

  /**
   * Wert für die Ausgabe
   */
  function matchTemplate($content)
  {
    $var = 'REX_TEMPLATE';
    $matches = $this->getVarParams($content, $var);

    foreach ($matches as $match)
    {
      list ($param_str, $args) = $match;
      list ($template_id, $args) = $this->extractArg('id', $args, 0);

	  $tplService = sly_Service_Factory::getTemplateService();

      if(!empty($template_id) && $tplService->exists($template_id))
      {
        $varname = '$__rex_tpl'.$template_id;
        $tpl     = "<?php\n\$tplService = sly_Service_Factory::getTemplateService();";

        if (isset($args['callback'])) {
        	$tpl .= "\n".'$args[\'subject\'] = file_get_contents($tplService->getContent(\'$template_id\'));';
        	$tpl .= "\n".'eval(\'?>\'.rex_call_func(unserialize("'.serialize($args['callback']).'", $args));';
        }
        else {
        	$prefix = isset($args['prefix']) ? "\n".'eval("'.addslashes($args['prefix']).'")' : '';
        	$suffix = isset($args['suffix']) ? "\n".'eval("'.addslashes($args['suffix']).'")' : '';

        	$tpl .= $prefix;

        	$exists   = $tplService->exists($template_id);

        	if (isset($args['instead']) && $exists) { // Bescheuertes Verhalten von REDAXO beibehalten.
        		$tpl .= "\n".'eval("'.addslashes($args['instead']).'");';
        	}
        	elseif (isset($args['ifempty']) && !$exists) {
        		$tpl .= "\n".'eval("'.addslashes($args['ifempty']).'");';
        	}
        	else {
        		$tpl .= "\n".'$tplService->includeFile(\''.$template_id.'\');';
        	}

        	$tpl .= $suffix;
        }

        $tpl .= "\n".'?>';

	    $content = str_replace($var.'['.$param_str.']', $tpl, $content);
      }
    }

    return $content;
  }
}