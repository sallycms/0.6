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
			<a name="addslice"></a>

			<div class="rex-form rex-form-content-editmode-add-slice">
				<form action="index.php#slice<?= $prior ?>" method="post" id="REX_FORM" enctype="multipart/form-data">
					<fieldset class="rex-form-col-1">
						<legend><span><?= t('add_block') ?></span></legend>
						<div class="rex-content-editmode-module-name">
							<input type="hidden" name="article_id" value="<?= $articleId ?>" />
							<input type="hidden" name="page" value="content" />
							<input type="hidden" name="mode" value="edit" />
							<input type="hidden" name="prior" value="<?= $prior ?>" />
							<input type="hidden" name="function" value="add" />
							<input type="hidden" name="module" value="<?= sly_html($module) ?>" />
							<input type="hidden" name="save" value="1" />
							<input type="hidden" name="clang" value="<?= $clang ?>" />
							<input type="hidden" name="slot" value="<?= $slot ?>" />

							<h3><span><?= sly_html($moduleService->getTitle($module)) ?></span></h3>
						</div>

						<div class="rex-form-row">
							<div class="sly-contentpage-slice-input">
								<? eval('?>' . self::replaceObjectVars(-1, $moduleContent)); ?>
							</div>
						</div>
					</fieldset>

					<fieldset class="rex-form-col-1">
						<div class="rex-form-wrapper">
							<div class="rex-form-row">
								<p class="rex-form-submit">
									<input class="rex-form-submit" type="submit" name="btn_save" value="<?= t('add_block') ?>" />
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
	public static function printEditSliceForm(OOArticleSlice $articleSlice) {
		global $REX;

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

		/*
		  Das bleibt hier stehen bis actions wieder implementiert sind
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
		 */

		$slice_content = ob_get_clean();
		$slice_content = sly_Helper_Content::triggerSliceShowEP($slice_content, $articleSlice, 'edit');

		print $slice_content;
	}

	private static function triggerSliceShowEP($content, OOArticleSlice $articleSlice, $func) {
		return sly_Core::dispatcher()->filter('SLICE_SHOW', $content, array(
			'article_id' => $articleSlice->getArticleId(),
			'clang' => $articleSlice->getClang(),
			'ctype' => $articleSlice->getSlot(),
			'slot' => $articleSlice->getSlot(),
			'module' => $articleSlice->getModuleName(),
			'slice_id' => $articleSlice->getSliceId(),
			'function' => $func,
			'function_slice_id' => $articleSlice->getId()
		));
	}

	/**
	 * REX_VAR-Ersetzungen
	 */
	private static function replaceObjectVars($slice_id, $content) {
		global $REX;

		foreach (sly_Core::getVarTypes() as $idx => $var) {
			if (isset($REX['ACTION']['SAVE']) && $REX['ACTION']['SAVE'] === false) {
				// Wenn der aktuelle Slice nicht gespeichert werden soll
				// (via Action wurde das Nicht-Speichern-Flag gesetzt)
				// Dann die Werte manuell aus dem Post übernehmen
				// und anschließend die Werte wieder zurücksetzen,
				// damit die nächsten Slices wieder die Werte aus der DB verwenden
				$var->setACValues($slice_id, $REX['ACTION']);
				$tmp = $var->getBEInput($slice_id, $content);
			} else {
				// Slice normal parsen
				$tmp = $var->getBEInput($slice_id, $content);
			}

			if ($tmp !== null) {
				$content = $tmp;
			}
		}

		return $content;
	}

	/**
	 * artikelweite globale Variablen werden ersetzt
	 */
	private static function replaceCommonVars($content, $articleId, $clang) {
		static $user_id = null;
		static $user_login = null;

		// UserId gibt's nur im Backend

		if ($user_id === null) {
			$user = sly_Util_User::getCurrentUser();

			if (!empty($user)) {
				$user_id = $user->getId();
				$user_login = $user->getLogin();
			} else {
				$user_id = '';
				$user_login = '';
			}
		}

		static $search = array(
	'REX_ARTICLE_ID',
	'REX_CATEGORY_ID',
	'REX_CLANG_ID',
	'REX_TEMPLATE_NAME',
	'REX_USER_ID',
	'REX_USER_LOGIN'
		);

		$article = sly_Util_Article::findById($articleId);
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
		$button->setAttribute('onclick', 'return confirm(\'' . $label . '?\')');
		$form->add(new sly_Form_ButtonBar(array('submit' => $button)));
	}

}
