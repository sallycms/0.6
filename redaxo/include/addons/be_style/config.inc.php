<?php

/**
 * Backendstyle Addon
 * 
 * @author jan.kristinus[at]redaxo[dot]de Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 * 
 * @author markus[dot]staab[at]redaxo[dot]de Markus Staab
 * @author <a href="http://www.redaxo.de">www.redaxo.de</a>
 *
 * @package redaxo4
 * @version svn:$Id$
 */

if (!$REX["REDAXO"]) return;

require_once $REX['INCLUDE_PATH'].'/addons/be_style/extensions/function_extensions.inc.php';
  
rex_register_extension('PAGE_HEADER', 'rex_be_style_css_add');
rex_register_extension('ADDONS_INCLUDED', 'rex_be_add_page');
