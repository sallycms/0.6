<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @author zozi
 */
class sly_Helper_Content {

	// ----- ADD Slice
	public static function printAddSliceForm($prior, $module, $articleId, $clang, $slot) {
		$moduleService = sly_Service_Factory::getModuleService();

		if (!$moduleService->exists($module)) {
			$slice_content = rex_warning(t('module_doesnt_exist'));
		} else {
			$moduleContent = $moduleService->getContent($moduleService->getInputFilename($module));
			ob_start();
			?>

			<div class="sly-form" id="addslice">
				<form action="index.php#slice<?= $prior ?>" method="post" enctype="multipart/form-data">
					<div>
						<input type="hidden" name="page" value="content" />
						<input type="hidden" name="func" value="addArticleSlice" />
						<input type="hidden" name="article_id" value="<?= $articleId ?>" />
						<input type="hidden" name="clang" value="<?= $clang ?>" />
						<input type="hidden" name="slot" value="<?= $slot ?>" />
						<input type="hidden" name="module" value="<?= sly_html($module) ?>" />
						<input type="hidden" name="prior" value="<?= $prior ?>" />
					</div>
					<fieldset class="rex-form-col-1">
						<legend><?= t('add_block') ?>: <?= sly_html($moduleService->getTitle($module)) ?></legend>
						<div class="rex-form-wrapper">
							<div class="sly-contentpage-slice-input">
								<? eval('?>' . self::replaceObjectVars(-1, $moduleContent)); ?>
							</div>
							<div class="rex-form-row">
								<p class="rex-form-submit">
									<input type="submit" name="btn_save" value="<?= t('add_block') ?>" />
								</p>
							</div>
						</div>
					</fieldset>
				</form>
			</div>

			<?
			self::focusFirstElement();

			$slice_content = ob_get_clean();
			$slice_content = self::replaceCommonVars($slice_content, $articleId, $clang);
		}

		print $slice_content;
	}

	// ----- EDIT Slice
	/* public static function printEditSliceForm(OOArticleSlice $articleSlice) {
	  ob_start();
	  ?>
	  <a name="editslice"></a>

	  <div class="rex-form rex-form-content-editmode-edit-slice">
	  <form enctype="multipart/form-data" action="index.php#slice<?= $articleSlice->getId() ?>" method="post" id="REX_FORM">
	  <fieldset class="rex-form-col-1">
	  <legend><span><?= t('edit_block') ?></span></legend>
	  <div class="rex-form-row">
	  <input type="hidden" name="article_id" value="<?= $articleSlice->getArticleId() ?>" />
	  <input type="hidden" name="page" value="content" />
	  <input type="hidden" name="mode" value="edit" />
	  <input type="hidden" name="slice_id" value="<?= $articleSlice->getId() ?>" />
	  <input type="hidden" name="slot" value="<?= $articleSlice->getSlot() ?>" />
	  <input type="hidden" name="function" value="edit" />
	  <input type="hidden" name="save" value="1" />
	  <input type="hidden" name="update" value="0" />
	  <input type="hidden" name="clang" value="<?= $articleSlice->getClang() ?>" />

	  <div class="sly-contentpage-slice-input">
	  <? eval('?>' . $articleSlice->getInput()); ?>
	  </div>
	  </div>
	  </fieldset>

	  <fieldset class="rex-form-col-2">
	  <div class="rex-form-wrapper">
	  <div class="rex-form-row">
	  <p class="rex-form-submit">
	  <input class="rex-form-submit" type="submit" value="<?= t('save_block') ?>" name="btn_save" />
	  <input class="rex-form-submit rex-form-submit-2" type="submit" value="<?= t('update_block') ?>" name="btn_update" />
	  </p>
	  </div>
	  </div>
	  </fieldset>
	  </form>
	  </div>
	  <?
	  self::focusFirstElement();


	  // ----- PRE VIEW ACTION [EDIT]
	  $REX_ACTION = array();

	  // nach klick auf den Übernehmen button,
	  // die POST werte übernehmen

	  if (rex_var::isEditEvent()) {
	  foreach (sly_Core::getVarTypes() as $obj) {
	  $REX_ACTION = $obj->getACRequestValues($REX_ACTION);
	  }
	  }

	  // Sonst die Werte aus der DB holen
	  // (1. Aufruf via Editieren Link)
	  else {
	  foreach (sly_Core::getVarTypes() as $obj) {
	  $REX_ACTION = $obj->getACDatabaseValues($REX_ACTION, $articleSlice->getSliceId());
	  }
	  }

	  $modebit = 2; // pre-action and edit

	  $moduleService = sly_Service_Factory::getModuleService();
	  $moduleService = sly_Service_Factory::getService('Module');
	  $actionService = sly_Service_Factory::getService('Action');
	  $actions = $moduleService->getActions($module);
	  $actions = isset($actions['preview']) ? sly_makeArray($actions['preview']) : array();

	  foreach ($actions as $actionName) {
	  $action = $actionService->getContent($actionName, 'preview');

	  // Variablen ersetzen
	  foreach (sly_Core::getVarTypes() as $obj) {
	  $iaction = $obj->getACOutput($REX_ACTION, $action);
	  }

	  eval('?>' . $action);

	  // Speichern (falls nätig)

	  foreach (sly_Core::getVarTypes() as $obj) {
	  $obj->setACValues($articleSlice->getSliceId(), $REX_ACTION);
	  }
	  }

	  // ----- / PRE VIEW ACTION


	  $slice_content = ob_get_clean();
	  $slice_content = sly_Helper_Content::triggerSliceShowEP($slice_content, $articleSlice, 'edit');

	  print $slice_content;
	  } */

	/* private static function triggerSliceShowEP($content, OOArticleSlice $articleSlice, $func) {
	  return sly_Core::dispatcher()->filter('SLY_SLICE_SHOW', $content, array(
	  'article_id' => $articleSlice->getArticleId(),
	  'clang' => $articleSlice->getClang(),
	  'slot' => $articleSlice->getSlot(),
	  'module' => $articleSlice->getModuleName(),
	  'slice_id' => $articleSlice->getSliceId(),
	  'function' => $func,
	  'function_slice_id' => $articleSlice->getId()
	  ));
	  } */

	/**
	 * Perform REX_VAR replacements
	 *
	 * @param  int    $slice_id  the slice's ID
	 * @param  string $content   current slice content
	 * @return string            parsed content
	 */
	private static function replaceObjectVars($slice_id, $content) {
		foreach (sly_Core::getVarTypes() as $idx => $var) {
			$tmp = $var->getBEInput($slice_id, $content);
			if ($tmp !== null) $content = $tmp;
		}

		return $content;
	}

	/**
	 * artikelweite globale Variablen werden ersetzt
	 */
	private static function replaceCommonVars($content, $articleId, $clang) {
		$user    = sly_Util_User::getCurrentUser();
		$article = sly_Util_Article::findById($articleId);

		if (!empty($user)) {
			$user_id    = $user->getId();
			$user_login = $user->getLogin();
		}
		else {
			$user_id    = '';
			$user_login = '';
		}

		$search = array(
			'REX_ARTICLE_ID',
			'REX_CATEGORY_ID',
			'REX_CLANG_ID',
			'REX_TEMPLATE_NAME',
			'REX_USER_ID',
			'REX_USER_LOGIN'
		);

		$replace = array(
			$articleId,
			$article->getCategoryId(),
			$clang,
			$article->getTemplateName(),
			$user_id,
			$user_login
		);

		return str_replace($search, $replace, $content);
	}

	private static function focusFirstElement() {
		$layout = sly_Core::getLayout();
		$layout->addJavaScript('jQuery(function($) { $(":input:visible:enabled:not([readonly]):first", $("form#REX_FORM")).focus(); });');
	}

	public static function metaFormAddButtonBar($form, $label, $name) {
		$button = new sly_Form_Input_Button('submit', $name, $label);
		$button->setAttribute('onclick', 'return confirm(\''.$label.'?\')');
		$form->add(new sly_Form_ButtonBar(array('submit' => $button)));
	}
}
