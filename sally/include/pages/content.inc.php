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

/*
// TODOS:
// - alles vereinfachen
// - <? ?> $ Problematik bei REX_ACTION
*/

unset ($REX_ACTION);

$category_id = sly_request('category_id', 'rex-category-id');
$article_id  = sly_request('article_id',  'rex-article-id');
$clang       = sly_request('clang',       'rex-clang-id', $REX['START_CLANG_ID']);
$slice_id    = sly_request('slice_id',    'rex-slice-id', '');
$function    = sly_request('function',    'string');
$slot        = sly_request('slot',        'string');

$article_revision = 0;
$slice_revision   = 0;
$warning          = '';
$global_warning   = '';
$info             = '';
$global_info      = '';

require SLY_INCLUDE_PATH.'/functions/function_rex_content.inc.php';

//$article = new rex_sql();
//$article->setQuery('SELECT startpage, name, re_id, template FROM #_article a WHERE a.id = '.$article_id.' AND clang = '.$clang, '#_');
$OOArt       = OOArticle::getArticleById($article_id, $clang);

if (!is_null($OOArt)) {

	$templateService = sly_Service_Factory::getTemplateService();
	$moduleService   = sly_Service_Factory::getModuleService();

	// Artikel wurde gefunden - Kategorie holen
	$category_id = $OOArt->getCategoryId();

	// Kategoriepfad und -rechte

	require SLY_INCLUDE_PATH.'/functions/function_rex_category.inc.php';
	// $KATout kommt aus dem include
	// $KATPERM

	if ($REX['PAGE'] == 'content' && $article_id > 0) {
		$KATout .= '<p>';

		if ($OOArt->isStartPage()) {
			$KATout .= t('start_article').' : ';
		}
		else {
			$KATout .= t('article').' : ';
		}

		$catname = str_replace(' ', '&nbsp;', sly_html($OOArt->getName()));

		$KATout .= '<a href="index.php?page=content&amp;article_id='.$article_id.'&amp;mode=edit&amp;clang='.$clang.'">'.$catname.'</a>';
		$KATout .= '</p>';
	}

	// Titel anzeigen

	rex_title(t('content'), $KATout);

	// Request Parameter

	$mode     = sly_request('mode', 'string', 'edit');
	$function = sly_request('function', 'string');
	$warning  = sly_request('warning', 'string');
	$info     = sly_request('info', 'string');


	// Sprachenblock

	$sprachen_add = '&amp;mode='.$mode.'&amp;category_id='.$category_id.'&amp;article_id='.$article_id;
	require SLY_INCLUDE_PATH.'/functions/function_rex_languages.inc.php';

	// EXTENSION POINT

	print rex_register_extension_point('PAGE_CONTENT_HEADER', '', array(
		'article_id'       => $article_id,
		'clang'            => $clang,
		'function'         => $function,
		'mode'             => $mode,
		'slice_id'         => $slice_id,
		'page'             => 'content',
		'slot'             => $slot,
		'ctype'            => $slot, // REDAXO-Kompatibilität
		'category_id'      => $category_id,
		'article_revision' => &$article_revision,
		'slice_revision'   => &$slice_revision
	));

	// Rechte prüfen
	$templateName = $OOArt->getTemplateName();

	/*if (empty($templateName)) {
		print rex_warning(t('content_select_template'));
	}
	else*/if (!($KATPERM || $REX['USER']->hasPerm('article['.$article_id.']'))) {
		// keine Rechte
		print rex_warning(t('no_rights_to_edit'));
	}
	else {
		$hasTemplate = !empty($templateName);

		// validate slot

		if ($hasTemplate && !$templateService->hasSlot($templateName, $slot)) {
			$slot = $templateService->getFirstSlot($templateName);
		}

		$curSlots = $hasTemplate ? $templateService->getSlots($templateName) : false;

		// Slice add/edit/delete

		if ($hasTemplate && $slot !== null && sly_request('save', 'boolean') && in_array($function, array('add', 'edit', 'delete'))) {
			// check module

			if ($function == 'edit' || $function == 'delete') {
				$module = rex_slice_module_exists($slice_id, $clang);
			}
			else { // add
				$module = sly_post('module', 'string');
			}

			if (!$moduleService->exists($module)) {
				$global_warning = t('module_not_found');
				$slice_id       = '';
				$function       = '';
			}
			else {
				// Rechte am Modul
				if (!$templateService->hasModule($templateName, $module, $slot)) {
					$global_warning = t('no_rights_to_this_function');
					$slice_id       = '';
					$function       = '';
				}
				elseif (!($REX['USER']->isAdmin() || $REX['USER']->hasPerm('module['.$module.']') || $REX['USER']->hasPerm('module[0]'))) {
					$global_warning = t('no_rights_to_this_function');
					$slice_id       = '';
					$function       = '';
				}
				else {
					// Daten einlesen

					$REX_ACTION         = array();
					$REX_ACTION['SAVE'] = true;

					foreach (sly_Core::getVarTypes() as $idx => $obj) {
						$REX_ACTION = $obj->getACRequestValues($REX_ACTION);
					}

					// ----- PRE SAVE ACTION [ADD/EDIT/DELETE]

					list($action_message, $REX_ACTION) = rex_execPreSaveAction($module, $function, $REX_ACTION);

					// Statusspeicherung für die rex_article Klasse

					$REX['ACTION'] = $REX_ACTION;

					// Werte werden aus den REX_ACTIONS übernommen wenn SAVE=true

					if (!$REX_ACTION['SAVE']) {
						// DONT SAVE/UPDATE SLICE
						if (!empty($action_message)) {
							$warning = $action_message;
						}
						elseif ($function == 'delete') {
							$warning = t('slice_deleted_error');
						}
						else {
							$warning = t('slice_saved_error');
						}
					}
					else {
						// SAVE / UPDATE SLICE

						if ($function == 'add' || $function == 'edit') {
							$newsql = new rex_sql();
							$newsql->setTable('article_slice', true);

							if ($function == 'edit') {
								$ooslice = OOArticleSlice::getArticleSliceById($slice_id);
								$realslice = sly_Service_Factory::getSliceService()->findById($ooslice->getSliceId());
								$realslice->flushValues();
								unset($ooslice);
								$newsql->setWhere('id = '.$slice_id);
								$newsql->setValue('slice_id', $realslice->getId());
							}
							elseif ($function == 'add') {
								$prior = rex_post('prior', 'int');
								$realslice = sly_Service_Factory::getSliceService()->create(array('module' => $module));

								$newsql->setValue('slice_id',   $realslice->getId());
								$newsql->setValue('prior',      $prior);
								$newsql->setValue('article_id', $article_id);
								$newsql->setValue('module',     $module);
								$newsql->setValue('clang',      $clang);
								$newsql->setValue('slot',       $slot);
								$newsql->setValue('revision',   $slice_revision);
							}

							// ****************** SPEICHERN FALLS NÖTIG
							foreach (sly_Core::getVarTypes() as $obj) {
								$obj->setACValues($realslice->getId(), $REX_ACTION, true, false);
							}

							if ($function == 'edit') {
								$newsql->addGlobalUpdateFields();

								if ($newsql->update()) {
									$info = $action_message.t('block_updated');
								}
								else {
									$warning = $action_message.$newsql->getError();
								}
							}
							elseif ($function == 'add') {
								$newsql->addGlobalUpdateFields();
								$newsql->addGlobalCreateFields();

								if ($newsql->insert()) {
									$last_id = $newsql->getLastId();

									$query   =
										'UPDATE #_article_slice '.
										'SET prior = prior + 1 WHERE
											article_id = '.$article_id.' AND
											clang = '.$clang.' AND
											slot = "'.$slot.'" AND
											prior >= '.$prior.' AND
											id != '.$last_id;

									if ($newsql->setQuery($query, '#_')) {
										$info     = $action_message.t('block_added');
									}

									$function = '';
								}
								else {
									$global_warning = $action_message.$newsql->getError();
								}
							}

							$newsql = null;
						}
						else {
							// make delete

							if (rex_deleteSlice($slice_id)) {
								$global_info = t('block_deleted');
							}
							else {
								$global_warning = t('block_not_deleted');
							}
						}
						// ----- / SAVE SLICE

						// Artikel neu generieren

						$update = new rex_sql();
						$update->setTable('article', true);
						$update->setWhere('id = '.$article_id.' AND clang = '.$clang);
						$update->addGlobalUpdateFields();
						$update->update();
						$update = null;

						//rex_deleteCacheArticleContent($article_id, $clang);
						rex_deleteCacheSliceContent($slice_id);

						// POST SAVE ACTION [ADD/EDIT/DELETE]

						$info .= rex_execPostSaveAction($module, $function, $REX_ACTION);

						// Update Button wurde gedrückt?

						if (rex_post('btn_save', 'string')) {
							$function = '';
						}
					}
				}
			}

			// Flush slice cache
			sly_Core::cache()->flush(OOArticleSlice::CACHE_NS);
		}

		// END: Slice add/edit/delete
		if ($mode == 'meta') {
			// START: ARTICLE2STARTARTICLE

			if (sly_post('article2startpage', 'string')) {
				if ($REX['USER']->isAdmin() || $REX['USER']->hasPerm('article2startpage[]')) {
					if (rex_article2startpage($article_id)) {
						$info = t('content_tostartarticle_ok');
						while (ob_get_level()) ob_end_clean();
						header('Location: index.php?page=content&mode=meta&clang='.$clang.'&slot='.$slot.'&article_id='.$article_id.'&info='.urlencode($info));
						exit;
					}
					else {
						$warning = t('content_tostartarticle_failed');
					}
				}
			}

			// END: ARTICLE2STARTARTICLE
			// START: COPY LANG CONTENT

			if (sly_post('copycontent', 'string')) {
				if ($REX['USER']->isAdmin() || $REX['USER']->hasPerm('copyContent[]')) {
					$clang_a = sly_post('clang_a', 'rex-clang-id');
					$clang_b = sly_post('clang_b', 'rex-clang-id');

					if (rex_copyContent($article_id, $article_id, $clang_a, $clang_b)) {
						$info = t('content_contentcopy');
					}
					else {
						$warning = t('content_errorcopy');
					}
				}
			}

			// END: COPY LANG CONTENT
			// START: MOVE ARTICLE

			if (sly_post('movearticle', 'string') && $category_id != $article_id) {
				$category_id_new = sly_post('category_id_new', 'rex-category-id');

				if ($REX['USER']->isAdmin() || ($REX['USER']->hasPerm('moveArticle[]') && ($REX['USER']->hasPerm('csw[0]') || $REX['USER']->hasPerm('csw['.$category_id_new.']')))) {
					if (rex_moveArticle($article_id, $category_id, $category_id_new)) {
						$info = t('content_articlemoved');
						while (ob_get_level()) ob_end_clean();
						header('Location: index.php?page=content&article_id='.$article_id.'&mode=meta&clang='.$clang.'&slot='.$slot.'&info='.urlencode($info));
						exit;
					}
					else {
						$warning = t('content_errormovearticle');
					}
				}
				else {
					$warning = t('no_rights_to_this_function');
				}
			}

			// END: MOVE ARTICLE
			// START: COPY ARTICLE

			if (sly_post('copyarticle', 'string')) {
				$category_copy_id_new = sly_post('category_copy_id_new', 'rex-category-id');

				if ($REX['USER']->isAdmin() || ($REX['USER']->hasPerm('copyArticle[]') && ($REX['USER']->hasPerm('csw[0]') || $REX['USER']->hasPerm('csw['.$category_copy_id_new.']')))) {
					if (($new_id = rex_copyArticle($article_id, $category_copy_id_new)) !== false) {
						$info = t('content_articlecopied');
						while (ob_get_level()) ob_end_clean();
						header('Location: index.php?page=content&article_id='.$new_id.'&mode=meta&clang='.$clang.'&slot='.$slot.'&info='.urlencode($info));
						exit;
					}
					else {
						$warning = t('content_errorcopyarticle');
					}
				}
				else {
					$warning = t('no_rights_to_this_function');
				}
			}

			// END: COPY ARTICLE
			// START: MOVE CATEGORY

			if (sly_post('movecategory', 'string')) {
				$category_id_new = sly_post('category_id_new', 'rex-category-id');

				if ($REX['USER']->isAdmin() || ($REX['USER']->hasPerm('moveCategory[]') && (($REX['USER']->hasPerm('csw[0]') || $REX['USER']->hasPerm('csw['.$category_id.']')) && ($REX['USER']->hasPerm('csw[0]') || $REX['USER']->hasPerm('csw['.$category_id_new.']'))))) {
					if ($category_id != $category_id_new && rex_moveCategory($category_id, $category_id_new)) {
						$info = t('category_moved');
						while (ob_get_level()) ob_end_clean();
						header('Location: index.php?page=content&article_id='.$category_id.'&mode=meta&clang='.$clang.'&slot='.$slot.'&info='.urlencode($info));
						exit;
					}
					else {
						$warning = t('content_error_movecategory');
					}
				}
				else {
					$warning = t('no_rights_to_this_function');
				}
			}

			// END: MOVE CATEGORY
			// START: SAVE METADATA

			if (sly_post('savemeta', 'string')) {
				$meta_article_name = sly_post('meta_article_name', 'string');

				$meta_sql = new rex_sql();
				$meta_sql->setTable('article', true);
				$meta_sql->setWhere('id = '.$article_id.' AND clang = '.$clang);
				$meta_sql->setValue('name', $meta_article_name);
				$meta_sql->addGlobalUpdateFields();

				if ($meta_sql->update()) {
					$info     = t('metadata_updated');
					$meta_sql = null;

					rex_deleteCacheArticle($article_id, $clang);
				}
				else {
					$meta_sql = null;
					$warning  = $meta_sql->getError();
				}

				$info = rex_register_extension_point('ART_META_UPDATED', $info, array(
					'id'    => $article_id,
					'clang' => $clang,
				));
			}

			// END: SAVE METADATA
		}
		// START: CONTENT HEAD MENUE

		$numSlots = $hasTemplate ? count($curSlots) : 0;
		$slotMenu = '';

		if ($numSlots > 0) {
			$listElements = array(t($numSlots > 1 ? 'content_types' : 'content_type').' : ');

			foreach ($curSlots as $tmpSlot) {
				$class = ($tmpSlot == $slot && $mode == 'edit') ? ' class="rex-active"' : '';
				$slotTitle = rex_translate($templateService->getSlotTitle($templateName, $tmpSlot));
				$listElements[] = '<a href="index.php?page=content&amp;article_id='.$article_id.'&amp;clang='.$clang.'&amp;slot='.$tmpSlot.'&amp;mode=edit"'.$class.''.rex_tabindex().'>'.$slotTitle.'</a>';
			}

			$listElements = rex_register_extension_point('PAGE_CONTENT_CTYPE_MENU', $listElements, array(
				'article_id' => $article_id,
				'clang'      => $clang,
				'function'   => $function,
				'mode'       => $mode,
				'slice_id'   => $slice_id
			));
			$listElements = rex_register_extension_point('PAGE_CONTENT_SLOT_MENU', $listElements, array(
				'article_id' => $article_id,
				'clang'      => $clang,
				'function'   => $function,
				'mode'       => $mode,
				'slice_id'   => $slice_id
			));

			$slotMenu  .= '<ul id="rex-navi-slots">';

			foreach ($listElements as $idx => $listElement) {
				$class = '';

				if ($idx == 1) { // das erste Element ist nur Beschriftung -> überspringen
					$class = ' class="rex-navi-first"';
				}

				$slotMenu .= '<li'.$class.'>'.$listElement.'</li>';
			}

			$slotMenu .= '</ul>';
		}

		$menu         = $slotMenu;
		$listElements = array();
		$baseURL      = 'index.php?page=content&amp;article_id='.$article_id.'&amp;clang='.$clang.'&amp;slot='.$slot;

		if ($mode == 'edit') {
			$listElements[] = '<a href="'.$baseURL.'&amp;mode=edit" class="rex-active">'.t('edit_mode').'</a>';
			$listElements[] = '<a href="'.$baseURL.'&amp;mode=meta">'.t('metadata').'</a>';
		}
		else {
			$listElements[] = '<a href="'.$baseURL.'&amp;mode=edit">'.t('edit_mode').'</a>';
			$listElements[] = '<a href="'.$baseURL.'&amp;mode=meta" class="rex-active">'.t('metadata').'</a>';
		}

		$listElements[] = '<a href="../'.$REX['FRONTEND_FILE'].'?article_id='.$article_id.'&amp;clang='.$clang.'" onclick="window.open(this.href); return false;" '.rex_tabindex().'>'.t('show').'</a>';

		$listElements = rex_register_extension_point('PAGE_CONTENT_MENU', $listElements, array(
			'article_id' => $article_id,
			'clang'      => $clang,
			'function'   => $function,
			'mode'       => $mode,
			'slice_id'   => $slice_id
		));

		$menu .= '<ul class="rex-navi-content">';

		foreach ($listElements as $idx => $element) {
			$class = $idx == 0 ? ' class="rex-navi-first"' : '';
			$menu .= '<li'.$class.'>'.$element.'</li>';
		}

		$menu .= '</ul>';

		// END: CONTENT HEAD MENUE
		// START: AUSGABE

		print '
<!-- *** OUTPUT OF ARTICLE-CONTENT - START *** -->
<div class="rex-content-header">
	<div class="rex-content-header-2">
		'.$menu.'
		<div class="rex-clearer"></div>
	</div>
</div>
		';

		// Meldungen

		if (!empty($global_warning)) print rex_warning($global_warning);
		if (!empty($global_info))    print rex_info($global_info);

		if ($mode != 'edit') {
			if (!empty($warning)) print rex_warning($warning);
			if (!empty($info))    print rex_info($info);
		}

		print '
<div class="rex-content-body">
	<div class="rex-content-body-2">
	';

		if ($mode == 'edit') {
			// START: Slice move up/down

			if ($hasTemplate && ($function == 'moveup' || $function == 'movedown')) {
				if ($REX['USER']->isAdmin() || $REX['USER']->hasPerm('moveSlice[]')) {
					// Modul und Rechte vorhanden?

					$module = rex_slice_module_exists($slice_id, $clang);

					if ($module == -1) {
						// MODUL IST NICHT VORHANDEN
						$warning  = t('module_not_found');
						$slice_id = '';
						$function = '';
					}
					else {
						// RECHTE AM MODUL ?
						if ($REX['USER']->isAdmin() || $REX['USER']->hasPerm('module['.$module.']') || $REX['USER']->hasPerm('module[0]')) {
							list($success, $message) = rex_moveSlice($slice_id, $clang, $function);

							if ($success) {
								$info = $message;
							}
							else {
								$warning = $message;
							}
						}
						else {
							$warning = t('no_rights_to_this_function');
						}
					}

					// Flush slice cache
					sly_Core::cache()->flush(OOArticleSlice::CACHE_NS);
				}
				else {
					$warning = t('no_rights_to_this_function');
				}
			}
			// END: Slice move up/down

			$params = array(
				'article_id' => $article_id,
				'clang'      => $clang,
				'slot'       => $slot
			);

			if (!$hasTemplate || $slot === null) {
				sly_Core::dispatcher()->notify('SLY_CONTENT_SLICE_PAGE', null, $params);
				if (!$hasTemplate) print rex_warning(t('content_select_template'));
				else print rex_info(t('content_no_slots'));
			}
			else {
				// START: MODULE EDITIEREN/ADDEN ETC.

				$CONT = new rex_article();
				$CONT->getContentAsQuery();
				$CONT->info = $info;
				$CONT->warning = $warning;
				$CONT->template = $templateName;
				$CONT->setArticleId($article_id);
				$CONT->setSliceId($slice_id);
				$CONT->setMode($mode);
				$CONT->setCLang($clang);
				$CONT->setEval(true);
				$CONT->setSliceRevision($slice_revision);
				$CONT->setFunction($function);

				sly_Core::dispatcher()->notify('SLY_CONTENT_SLICE_PAGE', $CONT, $params);

				print '<div class="rex-content-editmode">';
				print $CONT->getArticle($slot);
				print '</div>';

				// END: MODULE EDITIEREN/ADDEN ETC.
			}
		}
		elseif ($mode == 'meta') {
			// START: META VIEW

			$params = array('id' => $article_id, 'clang' => $clang, 'article' => $OOArt);
			$form   = new sly_Form('index.php', 'POST', t('general'), '', 'REX_FORM');

			/////////////////////////////////////////////////////////////////
			// init form

			$form->setEncType('multipart/form-data');
			$form->addHiddenValue('page',       'content');
			$form->addHiddenValue('article_id', $article_id);
			$form->addHiddenValue('mode',       'meta');
			$form->addHiddenValue('save',       1);
			$form->addHiddenValue('clang',      $clang);
			$form->addHiddenValue('slot',       $slot);
			$form->setSubmitButton(null);
			$form->setResetButton(null);

			/////////////////////////////////////////////////////////////////
			// article name / metadata

			$name = new sly_Form_Input_Text('meta_article_name', t('name_description'), $OOArt->getValue('name'), 'rex-form-meta-article-name');
			$form->add($name);

			$form = sly_Core::dispatcher()->filter('SLY_ART_META_FORM', $form, $params);

			$button = new sly_Form_Input_Button('submit', 'savemeta', t('update_metadata'));
			$form->add(new sly_Form_ButtonBar(array('submit' => $button)));

			$form = sly_Core::dispatcher()->filter('SLY_ART_META_FORM_FIELDSET', $form, $params);

			/////////////////////////////////////////////////////////////////
			// misc

			function addButtonBar($form, $label, $name) {
				$button = new sly_Form_Input_Button('submit', $name, $label);
				$button->setAttribute('onclick', 'return confirm(\''.$label.'?\')');
				$form->add(new sly_Form_ButtonBar(array('submit' => $button)));
			}

			if ($REX['USER']->isAdmin() || $REX['USER']->hasPerm('article2startpage[]') || $REX['USER']->hasPerm('moveArticle[]') || $REX['USER']->hasPerm('copyArticle[]') || ($REX['USER']->hasPerm('copyContent[]') && count($REX['CLANG']) > 1)) {
				// ZUM STARTARTIKEL MACHEN

				if ($REX['USER']->isAdmin() || $REX['USER']->hasPerm('article2startpage[]')) {
					$form->beginFieldset(t('content_startarticle'));

					if ($OOArt->getValue('startpage') == 0 && $OOArt->getValue('re_id') == 0) {
						$form->add(new sly_Form_Text('', t('content_nottostartarticle')));
					}
					else if ($OOArt->getValue('startpage') == 1) {
						$form->add(new sly_Form_Text('', t('content_isstartarticle')));
					}
					else {
						addButtonBar($form, t('content_tostartarticle'), 'article2startpage');
					}
				}

				// INHALTE KOPIEREN

				if (($REX['USER']->isAdmin() || $REX['USER']->hasPerm('copyContent[]')) && count($REX['CLANG']) > 1) {
					$lang_a = new sly_Form_Select_DropDown('clang_a', t('content_contentoflang'), sly_request('clang_a', 'rex-clang-id', null), $REX['CLANG'], 'clang_a');
					$lang_a->setSize(1);
					$lang_a->setAttribute('tabindex', rex_tabindex(false));

					$lang_b = new sly_Form_Select_DropDown('clang_b', t('content_to'), sly_request('clang_b', 'rex-clang-id', null), $REX['CLANG'], 'clang_b');
					$lang_b->setSize(1);
					$lang_b->setAttribute('tabindex', rex_tabindex(false));

					$form->beginFieldset(t('content_submitcopycontent'), null, 2);
					$form->addRow(array($lang_a, $lang_b));

					addButtonBar($form, t('content_submitcopycontent'), 'copycontent');
				}

				// ARTIKEL VERSCHIEBEN

				if ($OOArt->getValue('startpage') == 0 && ($REX['USER']->isAdmin() || $REX['USER']->hasPerm('moveArticle[]'))) {
					$select = sly_Form_Helper::getCategorySelect('category_id_new', false, false, null, $REX['USER']);
					$select->setAttribute('value', $category_id);
					$select->setLabel(t('move_article'));

					$form->beginFieldset(t('content_submitmovearticle'));
					$form->add($select);

					addButtonBar($form, t('content_submitmovearticle'), 'movearticle');
				}

				// ARTIKEL KOPIEREN

				if ($REX['USER']->isAdmin() || $REX['USER']->hasPerm('copyArticle[]')) {
					$select = sly_Form_Helper::getCategorySelect('category_copy_id_new', false, false, null, $REX['USER']);
					$select->setAttribute('value', $category_id);
					$select->setLabel(t('copy_article'));

					$form->beginFieldset(t('content_submitcopyarticle'));
					$form->add($select);

					addButtonBar($form, t('content_submitcopyarticle'), 'copyarticle');
				}

				// KATEGORIE/STARTARTIKEL VERSCHIEBEN

				if ($OOArt->getValue('startpage') == 1 && ($REX['USER']->isAdmin() || $REX['USER']->hasPerm('moveCategory[]'))) {
					$select = sly_Form_Helper::getCategorySelect('category_id_new', false, false, null, $REX['USER']);
					$select->setAttribute('value', $category_id);
					$select->setLabel(t('move_category'));

					$form->beginFieldset(t('content_submitmovecategory'));
					$form->add($select);

					addButtonBar($form, t('content_submitmovecategory'), 'movecategory');
				}
			}
			// SONSTIGES ENDE

			$form->render();

			// END: META VIEW
		}

		print '
	</div>
</div>
<!-- *** OUTPUT OF ARTICLE-CONTENT - END *** -->
';
		// END: AUSGABE
	}
}
