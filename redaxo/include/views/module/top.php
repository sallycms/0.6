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

$subpage = rex_request('subpage', 'string');

switch ($subpage)
{
  case 'actions' :
    {
      $title = $I18N->msg('modules').': '.$I18N->msg('actions');
      break;
    }
  default :
    {
      $title = $I18N->msg('modules');
      break;
    }
}

rex_title($title, array (array ('', $I18N->msg('modules')), array ('actions', $I18N->msg('actions'))));
