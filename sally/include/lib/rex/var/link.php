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
 * @ingroup redaxo
 */
class rex_var_link extends rex_var {
	// --------------------------------- Actions

	public function getACRequestValues($REX_ACTION) {
		foreach (array('LINK', 'LINKLIST') as $type) {
			$link = sly_request($type, 'array');
			$type = 'REX_'.$type;

			foreach ($link as $key => $value) {
				$REX_ACTION[$type][$key] = $value;
			}
		}

		return $REX_ACTION;
	}

	public function getACDatabaseValues($REX_ACTION, $slice_id) {
		$service = sly_Service_Factory::getService('SliceValue');

		foreach (array('REX_LINK', 'REX_LINKLIST') as $type) {
			$values = $service->find(array('slice_id' => $slice_id, 'type' => $type));

			foreach ($values as $value) {
				$REX_ACTION[$type][$value->getFinder()] = $value->getValue();
			}
		}

		return $REX_ACTION;
	}

	public function setACValues($slice_id, $REX_ACTION, $escape = false, $prependTableName = true) {
		$slice = sly_Service_Factory::getService('Slice')->findById($slice_id);

		foreach (array('REX_LINK', 'REX_LINKLIST') as $type) {
			if (isset($REX_ACTION[$type])) {
				foreach ($REX_ACTION[$type] as $key => $value){
					$slice->addValue($type, $key, $value);
				}
			}
		}
	}

	// --------------------------------- Output

	public function getBEOutput($slice_id, $content) {
		return $this->getOutput($slice_id, $content);
	}

	public function getBEInput($slice_id, $content) {
		$content = $this->getOutput($slice_id, $content);
		$content = $this->matchLinkButton($slice_id, $content);
		$content = $this->matchLinkListButton($slice_id, $content);

		return $content;
	}

	public function getOutput($slice_id, $content) {
		$content = $this->matchLinkList($slice_id, $content);
		$content = $this->matchLink($slice_id, $content);
		$content = $this->matchLinkId($slice_id, $content);

		return $content;
	}

	protected function handleDefaultParam($varname, $args, $name, $value) {
		switch ($name) {
			case '1':
			case 'category':
				$args['category'] = (int) $value;
		}

		return parent::handleDefaultParam($varname, $args, $name, $value);
	}

	/**
	 * Button für die Eingabe
	 */
	public function matchLinkButton($slice_id, $content) {
		$def_category = '';
		$article_id   = sly_request('article_id', 'int');

		if ($article_id != 0) {
			$art          = OOArticle::getArticleById($article_id);
			$def_category = $art->getCategoryId();
		}

		$var     = 'REX_LINK_BUTTON';
		$matches = $this->getVarParams($content, $var);
		$service = sly_Service_Factory::getService('SliceValue');

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);

			$value = $service->findBySliceTypeFinder($slice_id, 'REX_LINK', $id);
			$value = $value ? $value->getValue() : '';

			// Wenn vom Programmierer keine Kategorie vorgegeben wurde,
			// die Linkmap mit der aktuellen Kategorie öffnen
			list ($category, $args) = $this->extractArg('category', $args, $def_category);

			$replace = $this->getLinkButton($id, $value, $category, $args);
			$replace = $this->handleGlobalWidgetParams($var, $args, $replace);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Button für die Eingabe
	 */
	public function matchLinkListButton($slice_id, $content) {
		$var     = 'REX_LINKLIST_BUTTON';
		$matches = $this->getVarParams($content, $var);
		$service = sly_Service_Factory::getService('SliceValue');

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);
			list ($category, $args)  = $this->extractArg('category', $args, 0);

			$value = $service->findBySliceTypeFinder($slice_id, 'REX_LINKLIST', $id);
			$value = $value ? $value->getValue() : '';

			$replace = $this->getLinklistButton($id, $value, $category);
			$replace = $this->handleGlobalWidgetParams($var, $args, $replace);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	public function matchLink($slice_id, $content) {
		$var     = 'REX_LINK';
		$matches = $this->getVarParams($content, $var);
		$service = sly_Service_Factory::getService('SliceValue');

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);

			$value = $service->findBySliceTypeFinder($slice_id, 'REX_LINK', $id);
			$value = $value ? $value->getValue() : '';

			$replace = $value != '' ? rex_getUrl($value) : '';
			$replace = $this->handleGlobalVarParams($var, $args, $replace);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	public function matchLinkId($slice_id, $content) {
		$var     = 'REX_LINK_ID';
		$matches = $this->getVarParams($content, $var);
		$service = sly_Service_Factory::getService('SliceValue');

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);

			$value = $service->findBySliceTypeFinder($slice_id, 'REX_LINK', $id);
			$value = $value ? $value->getValue() : '';

			$replace = $this->handleGlobalVarParams($var, $args, $value);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Wert für die Ausgabe
	 */
	public function matchLinkList($slice_id, $content) {
		$var     = 'REX_LINKLIST';
		$matches = $this->getVarParams($content, $var);
		$service = sly_Service_Factory::getService('SliceValue');

		foreach ($matches as $match) {
			list ($param_str, $args) = $match;
			list ($id, $args)        = $this->extractArg('id', $args, 0);

			$value = $service->findBySliceTypeFinder($slice_id, 'REX_LINKLIST', $id);
			$value = $value ? $value->getValue() : '';

			$replace = $this->handleGlobalVarParams($var, $args, $value);
			$content = str_replace($var.'['.$param_str.']', $replace, $content);
		}

		return $content;
	}

	/**
	 * Gibt das Button Template zurück
	 */
	public function getLinkButton($id, $article_id, $category = '') {
		$art_name = '';
		$clang    = '';
		$art      = OOArticle::getArticleById($article_id);

		// Falls ein Artikel vorausgewählt ist, dessen Namen anzeigen und beim Öffnen der Linkmap dessen Kategorie anzeigen

		if (OOArticle::isValid($art)) {
			$art_name = $art->getName();
			$category = $art->getCategoryId();
		}

		$open_params = '&clang='.sly_Core::getCurrentClang();
		if ($category != '') $open_params .= '&category_id='.$category;

		$media = '
	<div class="rex-widget">
		<div class="rex-widget-link">
			<p class="rex-widget-field">
				<input type="hidden" name="LINK['.$id.']" id="LINK_'.$id.'" value="'.$article_id.'" />
				<input type="text" size="30" name="LINK_NAME['.$id.']" value="'.$art_name.'" id="LINK_'.$id.'_NAME" readonly="readonly" />
			</p>
			<p class="rex-widget-icons">
				<a href="#" class="rex-icon-file-open" onclick="openLinkMap(\'LINK_'.$id.'\', \''.$open_params.'\');return false;"><img src="media/file_open.gif" width="16" height="16" alt="'.t('var_link_open').'" title="'.t('var_link_open').'" /></a>
				<a href="#" class="rex-icon-file-delete" onclick="deleteREXLink('.$id.');return false;"><img src="media/file_del.gif" width="16" height="16" title="'.t('var_link_delete').'" alt="'.t('var_link_delete').'" /></a>
			</p>
		</div>
	</div>
	<div class="rex-clearer"></div>';

		return $media;
	}

	/**
	 * Gibt das ListButton Template zurück
	 */
	public function getLinklistButton($id, $value, $category = '') {
		$open_params = '&clang='.sly_Core::getCurrentClang();
		if ($category != '') $open_params .= '&category_id='.$category;

		$options       = '';
		$linklistarray = explode(',', $value);

		if (is_array($linklistarray)) {
			foreach ($linklistarray as $link) {
				if ($link != '') {
					$article  = OOArticle::getArticleById($link);
					$options .= '<option value="'.$link.'">'.$article->getName().'</option>';
				}
			}
		}

		$link = '
	<div class="rex-widget">
		<div class="rex-widget-linklist">
			<input type="hidden" name="LINKLIST['.$id.']" id="REX_LINKLIST_'.$id.'" value="'.$value.'" />
			<p class="rex-widget-field">
				<select name="LINKLIST_SELECT['.$id.']" id="REX_LINKLIST_SELECT_'.$id.'" size="8">
				'.$options.'
				</select>
			</p>
			<p class="rex-widget-icons">
				<a href="#" class="rex-icon-file-top" onclick="moveREXLinklist('.$id.',\'top\');return false;"><img src="media/file_top.gif" width="16" height="16" title="'.t('var_linklist_move_top').'" alt="'.t('var_linklist_move_top').'" /></a>
				<a href="#" class="rex-icon-file-open" onclick="openREXLinklist('.$id.', \''.$open_params.'\');return false;"><img src="media/file_open.gif" width="16" height="16" title="'.t('var_link_open').'" alt="'.t('var_link_open').'" /></a><br />
				<a href="#" class="rex-icon-file-up" onclick="moveREXLinklist('.$id.',\'up\');return false;"><img src="media/file_up.gif" width="16" height="16" title="'.t('var_linklist_move_up').'" alt="'.t('var_linklist_move_up').'" /></a>
				<a href="#" class="rex-icon-file-delete" onclick="deleteREXLinklist('.$id.');return false;"><img src="media/file_del.gif" width="16" height="16" title="'.t('var_link_delete').'" alt="'.t('var_link_delete').'" /></a><br />
				<a href="#" class="rex-icon-file-down" onclick="moveREXLinklist('.$id.',\'down\');return false;"><img src="media/file_down.gif" width="16" height="16" title="'.t('var_linklist_move_down').'" alt="'.t('var_linklist_move_down').'" /></a><br />
				<a href="#" class="rex-icon-file-bottom" onclick="moveREXLinklist('.$id.',\'bottom\');return false;"><img src="media/file_bottom.gif" width="16" height="16" title="'.t('var_linklist_move_bottom').'" alt="'.t('var_linklist_move_bottom').'" /></a>
			</p>
		</div>
	</div>
	<div class="rex-clearer"></div> ';

		return $link;
	}
}