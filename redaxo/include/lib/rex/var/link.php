<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * REX_LINK_BUTTON,
 * REX_LINK,
 * REX_LINK_ID,
 * REX_LINKLIST_BUTTON,
 * REX_LINKLIST
 *
 * @package redaxo4
 */
class rex_var_link extends rex_var
{
	// --------------------------------- Actions

	function getACRequestValues($REX_ACTION)
	{
		$link     = rex_request('LINK', 'array');
		foreach($link as $key => $value)
		{
			$REX_ACTION['REX_LINK'][$key] = $value;
		}

		$linklist = rex_request('LINKLIST', 'array');
		foreach($linklist as $key => $value)
		{
			$REX_ACTION['REX_LINKLIST'][$key] = $value;
		}

		return $REX_ACTION;
	}

	function getACDatabaseValues($REX_ACTION, $slice_id)
	{

		$values = sly_Service_Factory::getService('SliceValue')->find(array('slice_id' => $slice_id, 'type' => 'REX_LINK'));
		foreach($values as $value)
		{
			$REX_ACTION['REX_LINK'][$value->getFinder()] = $value->getValue();
		}

		$values = sly_Service_Factory::getService('SliceValue')->find(array('slice_id' => $slice_id, 'type' => 'REX_LINKLIST'));
		foreach($values as $value)
		{
			$REX_ACTION['REX_LINKLIST'][$value->getFinder()] = $value->getValue();
		}

		return $REX_ACTION;

	}

	function setACValues($slice_id, $REX_ACTION, $escape = false, $prependTableName = true)
	{

		//global $REX;

		//$slice_id = $sql->getValue('slice_id');
		$slice = sly_Service_Factory::getService('Slice')->findById($slice_id);
		if(isset($REX_ACTION['REX_LINK'])){
			foreach($REX_ACTION['REX_LINK'] as $key => $value){
				$slice->addValue('REX_LINK', $key, $value);
			}
		}
		if(isset($REX_ACTION['REX_LINKLIST'])){
			foreach($REX_ACTION['REX_LINKLIST'] as $key => $value){
				$slice->addValue('REX_LINKLIST', $key, $value);
			}
		}
	}

	// --------------------------------- Output

	function getBEOutput($slice_id, $content)
	{
		return $this->getOutput($slice_id, $content);
	}

	function getBEInput($slice_id, $content)
	{
		$content = $this->getOutput($slice_id, $content);
		$content = $this->matchLinkButton($slice_id, $content);
		$content = $this->matchLinkListButton($slice_id, $content);

		return $content;
	}

	function getOutput($slice_id, $content)
	{
		$content = $this->matchLinkList($slice_id, $content);
		$content = $this->matchLink($slice_id, $content);
		$content = $this->matchLinkId($slice_id, $content);

		return $content;
	}

	/**
	 * @see rex_var::handleDefaultParam
	 */
	function handleDefaultParam($varname, $args, $name, $value)
	{
		switch($name)
		{
			case '1' :
			case 'category' :
				$args['category'] = (int) $value;
				break;
		}
		return parent::handleDefaultParam($varname, $args, $name, $value);
	}

	/**
	 * Button für die Eingabe
	 */
	function matchLinkButton($slice_id, $content)
	{
		global $REX;

		$def_category = '';
		$article_id = rex_request('article_id', 'int');
		if($article_id != 0)
		{
			$art = OOArticle::getArticleById($article_id);
			$def_category = $art->getCategoryId();
		}

		$var = 'REX_LINK_BUTTON';
		$matches = $this->getVarParams($content, $var);
		foreach ($matches as $match)
		{
			list ($param_str, $args) = $match;
			list ($id, $args) = $this->extractArg('id', $args, 0);

			$value = sly_Service_Factory::getService('SliceValue')->findBySliceTypeFinder($slice_id, 'REX_LINK', $id);
			if($value){
				$value = $value->getValue();
			}else{
				$value = '';
			}

			// Wenn vom Programmierer keine Kategorie vorgegeben wurde,
			// die Linkmap mit der aktuellen Kategorie öffnen
			list ($category, $args) = $this->extractArg('category', $args, $def_category);

			$replace = $this->getLinkButton($id, $value, $category, $args);
			$replace = $this->handleGlobalWidgetParams($var, $args, $replace);
			$content = str_replace($var . '[' . $param_str . ']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Button für die Eingabe
	 */
	function matchLinkListButton($slice_id, $content)
	{
		$var = 'REX_LINKLIST_BUTTON';
		$matches = $this->getVarParams($content, $var);
		foreach ($matches as $match)
		{
			list ($param_str, $args) = $match;
			list ($id, $args) = $this->extractArg('id', $args, 0);

			list ($category, $args) = $this->extractArg('category', $args, 0);

			$value = sly_Service_Factory::getService('SliceValue')->findBySliceTypeFinder($slice_id, 'REX_LINKLIST', $id);
			if($value){
				$value = $value->getValue();
			}else{
				$value = '';
			}

			$replace = $this->getLinklistButton($id, $value, $category);
			$replace = $this->handleGlobalWidgetParams($var, $args, $replace);
			$content = str_replace($var . '[' . $param_str . ']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	function matchLink($slice_id, $content)
	{
		$var = 'REX_LINK';
		$matches = $this->getVarParams($content, $var);
		foreach ($matches as $match)
		{
			list ($param_str, $args) = $match;
			list ($id, $args) = $this->extractArg('id', $args, 0);

			$value = sly_Service_Factory::getService('SliceValue')->findBySliceTypeFinder($slice_id, 'REX_LINK', $id);
			if($value){
				$value = $value->getValue();
			}else{
				$value = '';
			}
			$replace = '';
			if ($value != ""){
				$replace = rex_getUrl($value);
			}

			$replace = $this->handleGlobalVarParams($var, $args, $replace);
			$content = str_replace($var . '[' . $param_str . ']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	function matchLinkId($slice_id, $content)
	{
		$var = 'REX_LINK_ID';
		$matches = $this->getVarParams($content, $var);
		foreach ($matches as $match)
		{
			list ($param_str, $args) = $match;
			list ($id, $args) = $this->extractArg('id', $args, 0);

			$value = sly_Service_Factory::getService('SliceValue')->findBySliceTypeFinder($slice_id, 'REX_LINK', $id);
			if($value){
				$value = $value->getValue();
			}else{
				$value = '';
			}

			$replace = $this->handleGlobalVarParams($var, $args, $value);
			$content = str_replace($var . '[' . $param_str . ']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	function matchLinkList($slice_id, $content)
	{
		$var = 'REX_LINKLIST';
		$matches = $this->getVarParams($content, $var);
		foreach ($matches as $match)
		{
			list ($param_str, $args) = $match;
			list ($id, $args) = $this->extractArg('id', $args, 0);

			$value = sly_Service_Factory::getService('SliceValue')->findBySliceTypeFinder($slice_id, 'REX_LINKLIST', $id);
			if($value){
				$value = $value->getValue();
			}else{
				$value = '';
			}

			$replace = $this->handleGlobalVarParams($var, $args, $value);
			$content = str_replace($var . '[' . $param_str . ']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Gibt das Button Template zurück
	 */
	function getLinkButton($id, $article_id, $category = '')
	{
		global $REX, $I18N;

		$art_name = '';
		$clang = '';
		$art = OOArticle :: getArticleById($article_id);

		// Falls ein Artikel vorausgew�hlt ist, dessen Namen anzeigen und beim �ffnen der Linkmap dessen Kategorie anzeigen
		if (OOArticle :: isValid($art))
		{
			$art_name = $art->getName();
			$category = $art->getCategoryId();
		}

		$open_params = '&clang=' . sly_Core::getCurrentClang();
		if ($category != '')
		$open_params .= '&category_id=' . $category;

		$media = '
	<div class="rex-widget">
		<div class="rex-widget-link">
      <p class="rex-widget-field">
  			<input type="hidden" name="LINK[' . $id . ']" id="LINK_' . $id . '" value="'. $article_id .'" />
  			<input type="text" size="30" name="LINK_NAME[' . $id . ']" value="' . $art_name . '" id="LINK_' . $id . '_NAME" readonly="readonly" />
		  </p>
      <p class="rex-widget-icons">
       	<a href="#" class="rex-icon-file-open" onclick="openLinkMap(\'LINK_' . $id . '\', \'' . $open_params . '\');return false;"'. rex_tabindex() .'><img src="media/file_open.gif" width="16" height="16" alt="'. $I18N->msg('var_link_open') .'" title="'. $I18N->msg('var_link_open') .'" /></a>
 	  		<a href="#" class="rex-icon-file-delete" onclick="deleteREXLink(' . $id . ');return false;"'. rex_tabindex() .'><img src="media/file_del.gif" width="16" height="16" title="'. $I18N->msg('var_link_delete') .'" alt="'. $I18N->msg('var_link_delete') .'" /></a>
 		  </p>
 		</div>
 	</div>
 	<div class="rex-clearer"></div>';

		return $media;
	}

	/**
	 * Gibt das ListButton Template zurück
	 */
	function getLinklistButton($id, $value, $category = '')
	{
		global $REX, $I18N;

		$open_params = '&clang=' . sly_Core::getCurrentClang();
		if ($category != '')
		$open_params .= '&category_id=' . $category;

		$options = '';
		$linklistarray = explode(',', $value);
		if (is_array($linklistarray))
		{
			foreach ($linklistarray as $link)
			{
				if ($link != '')
				{
					$article = OOArticle::getArticleById($link);
					$options .= '<option value="' . $link . '">' . $article->getName() . '</option>';
				}
			}
		}

		$link = '
  <div class="rex-widget">
    <div class="rex-widget-linklist">
      <input type="hidden" name="LINKLIST['. $id .']" id="REX_LINKLIST_'. $id .'" value="'. $value .'" />
      <p class="rex-widget-field">
        <select name="LINKLIST_SELECT[' . $id . ']" id="REX_LINKLIST_SELECT_' . $id . '" size="8"'. rex_tabindex() .'>
          ' . $options . '
        </select>
      </p>
      <p class="rex-widget-icons">
        <a href="#" class="rex-icon-file-top" onclick="moveREXLinklist(' . $id . ',\'top\');return false;"'. rex_tabindex() .'><img src="media/file_top.gif" width="16" height="16" title="'. $I18N->msg('var_linklist_move_top') .'" alt="'. $I18N->msg('var_linklist_move_top') .'" /></a>
        <a href="#" class="rex-icon-file-open" onclick="openREXLinklist(' . $id . ', \'' . $open_params . '\');return false;"'. rex_tabindex() .'><img src="media/file_open.gif" width="16" height="16" title="'. $I18N->msg('var_link_open') .'" alt="'. $I18N->msg('var_link_open') .'" /></a><br />
        <a href="#" class="rex-icon-file-up" onclick="moveREXLinklist(' . $id . ',\'up\');return false;"'. rex_tabindex() .'><img src="media/file_up.gif" width="16" height="16" title="'. $I18N->msg('var_linklist_move_up') .'" alt="'. $I18N->msg('var_linklist_move_up') .'" /></a>
   		  <a href="#" class="rex-icon-file-delete" onclick="deleteREXLinklist(' . $id . ');return false;"'. rex_tabindex() .'><img src="media/file_del.gif" width="16" height="16" title="'. $I18N->msg('var_link_delete') .'" alt="'. $I18N->msg('var_link_delete') .'" /></a><br />
        <a href="#" class="rex-icon-file-down" onclick="moveREXLinklist(' . $id . ',\'down\');return false;"'. rex_tabindex() .'><img src="media/file_down.gif" width="16" height="16" title="'. $I18N->msg('var_linklist_move_down') .'" alt="'. $I18N->msg('var_linklist_move_down') .'" /></a><br />
        <a href="#" class="rex-icon-file-bottom" onclick="moveREXLinklist(' . $id . ',\'bottom\');return false;"'. rex_tabindex() .'><img src="media/file_bottom.gif" width="16" height="16" title="'. $I18N->msg('var_linklist_move_bottom') .'" alt="'. $I18N->msg('var_linklist_move_bottom') .'" /></a>
      </p>
    </div>
  </div>
 	<div class="rex-clearer"></div>
    ';

		return $link;
	}
}