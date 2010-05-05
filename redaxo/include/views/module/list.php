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

include 'top.php';

$list = rex_list::factory('SELECT id, name FROM '.$REX['TABLE_PREFIX'].'module ORDER BY name');
$list->setCaption($I18N->msg('module_caption'));
$list->addTableAttribute('summary', $I18N->msg('module_summary'));
$list->addTableColumnGroup(array(40, 40, '*', 153));

$tdIcon = '<span class="rex-i-element rex-i-module"><span class="rex-i-element-text">###name###</span></span>';
$thIcon = '<a class="rex-i-element rex-i-module-add" href="'. $list->getUrl(array('func' => 'add')) .'"><span class="rex-i-element-text">'.$I18N->msg('create_module').'</span></a>';
$list->addColumn($thIcon, $tdIcon, 0, array('<th class="rex-icon">###VALUE###</th>','<td class="rex-icon">###VALUE###</td>'));
$list->setColumnParams($thIcon, array('func' => 'edit', 'modul_id' => '###id###'));

$list->setColumnLabel('id', 'ID');
$list->setColumnLayout('id', array('<th class="rex-small">###VALUE###</th>','<td class="rex-small">###VALUE###</td>'));

$list->setColumnLabel('name', $I18N->msg('module_description'));
$list->setColumnParams('name', array('func' => 'edit', 'modul_id' => '###id###'));

$list->addColumn($I18N->msg('module_functions'), $I18N->msg('delete_module'));
$list->setColumnParams($I18N->msg('module_functions'), array('func' => 'delete', 'modul_id' => '###id###'));
$list->addLinkAttribute($I18N->msg('module_functions'), 'onclick', 'return confirm(\''.$I18N->msg('delete').' ?\')');

$list->setNoRowsMessage($I18N->msg('modules_not_found'));

print $list->show();