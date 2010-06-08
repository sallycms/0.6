<?php

/**
 * REDAXO Default-Theme
 * 
 * @author Design
 * @author ralph.zumkeller[at]yakamara[dot]de Ralph Zumkeller
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 * 
 * @author Umsetzung
 * @author thomas[dot]blum[at]redaxo[dot]de Thomas Blum
 * @author <a href="http://www.blumbeet.com">www.blumbeet.com</a>
 *
 * @package redaxo4
 * @version svn:$Id$
 */

if($REX["REDAXO"])
{
  require_once(dirname(__FILE__). '/extensions/extension_cssadd.inc.php');
  
  rex_register_extension('PAGE_HEADER', 'rex_be_style_agk_skin_css_add');
}