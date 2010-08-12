<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * @package redaxo4
 */

function rex_linkmap_url($local = array(), $globals = array())
{
	return '?'.http_build_query(array_merge($globals, $local), '', '&amp;');
}

function rex_linkmap_backlink($id, $name)
{
	return 'javascript:insertLink(\'sally://'.$id.'\',\''.addslashes($name).'\');';
}

function rex_linkmap_format_label($OOobject)
{
	global $REX, $I18N;

	$label = $OOobject->getName();

	if (trim($label) == '') {
		$label = '&nbsp;';
	}

	if ($REX['USER']->hasPerm('advancedMode[]')) {
		$label .= ' ['.$OOobject->getId().']';
	}

	if (OOArticle::isValid($OOobject) && !$OOobject->hasTemplate()) {
		$label .= ' ['.$I18N->msg('lmap_has_no_template').']';
	}

	return $label;
}

function rex_linkmap_format_li($OOobject, $current_category_id, $globalParams, $liAttr = '', $linkAttr = '')
{
	$liAttr   .= $OOobject->getId() == $current_category_id ? ' id="rex-linkmap-active"' : '';
	$linkAttr .= ' class="'.($OOobject->isOnline() ? 'rex-online' : 'rex-offine').'"';

	if (strpos($linkAttr, ' href=') === false) {
		$linkAttr .= ' href="'.rex_linkmap_url(array('category_id' => $OOobject->getId()), $globalParams).'"';
	}

	$label = rex_linkmap_format_label($OOobject);

	return '<li'.$liAttr.'><a'.$linkAttr.'>'.htmlspecialchars($label).'</a>';
}

function rex_linkmap_tree($tree, $category_id, $children, $globalParams)
{
	$ul = '';
	if (is_array($children)) {
		$li        = '';
		$ulclasses = '';

		if (count($children) == 1) {
			$ulclasses = 'rex-children-one';
		}

		foreach ($children as $cat) {
			$cat_children = $cat->getChildren();
			$cat_id       = $cat->getId();
			$liclasses    = '';
			$linkclasses  = '';
			$sub_li       = '';

			if (!empty($cat_children)) {
				$liclasses   = 'rex-children ';
				$linkclasses = 'rex-linkmap-is-not-empty ';
			}

			if (next($children) == null) {
				$liclasses .= 'rex-children-last ';
			}

			$linkclasses .= $cat->isOnline() ? 'rex-online ' : 'rex-offline ';

			if (is_array($tree) && in_array($cat_id,$tree)) {
				$sub_li       = rex_linkmap_tree($tree, $cat_id, $cat_children, $globalParams);
				$liclasses   .= 'rex-active ';
				$linkclasses .= 'rex-active ';
			}

			if (!empty($liclasses)) {
				$liclasses = ' class="'.rtrim($liclasses).'"';
			}

			if (!empty($linkclasses)) {
				$linkclasses = ' class="'.rtrim($linkclasses).'"';
			}

			$label = rex_linkmap_format_label($cat);

			$li .= '<li'.$liclasses.'>';
			$li .= '<a'.$linkclasses.' href="'.rex_linkmap_url(array('category_id' => $cat_id), $globalParams).'">'.htmlspecialchars($label).'</a>';
			$li .= $sub_li;
			$li .= '</li>'."\n";
		}

		if (!empty($ulclasses)) {
			$ulclasses = ' class="'.rtrim($ulclasses).'"';
		}

		if (!empty($li)) {
			$ul = "<ul>\n$li</ul>\n";
		}
	}

	return $ul;
}
