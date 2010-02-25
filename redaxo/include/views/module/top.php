<?php

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
