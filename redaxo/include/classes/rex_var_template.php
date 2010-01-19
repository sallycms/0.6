<?php

/**
 * REX_TEMPLATE[2]
 *
 * @package redaxo4
 * @version svn:$Id$
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
      
      if($template_id > 0)
      {
        $varname = '$__rex_tpl'.$template_id;
        $tpl     = "<?php\n$varname = new rex_template($template_id);";
        
        if (isset($args['callback'])) {
        	$tpl .= "\n".'$args[\'subject\'] = file_get_contents('.$varname.'->getFile());';
        	$tpl .= "\n".'eval(\'?>\'.rex_call_func(unserialize("'.serialize($args['callback']).'", $args));';
        }
        else {
        	$prefix = isset($args['prefix']) ? "\n".'eval("'.addslashes($args['prefix']).'")' : '';
        	$suffix = isset($args['suffix']) ? "\n".'eval("'.addslashes($args['suffix']).'")' : '';
        	
        	$tpl .= $prefix;
        	
        	$filename = rex_template::getFilePath($template_id);
        	$exists   = file_exists($filename) && filesize($filename) > 0;
        	
        	if (isset($args['instead']) && $exists) { // Bescheuertes Verhalten von REDAXO beibehalten.
        		$tpl .= "\n".'eval("'.addslashes($args['instead']).'");';
        	}
        	elseif (isset($args['ifempty']) && !$exists) {
        		$tpl .= "\n".'eval("'.addslashes($args['ifempty']).'");';
        	}
        	else {
        		$tpl .= "\n".'include '.$varname.'->getFile();';
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