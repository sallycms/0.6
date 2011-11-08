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
 * @author zozi@webvariants.de
 */
class sly_Helper_Content {

	// ----- ADD Slice
	public static function printAddSliceForm($prior, $module, $articleId, $clang, $slot, $values = array()) {
		$moduleService = sly_Service_Factory::getModuleService();

		if (!$moduleService->exists($module)) {
			$slice_content = sly_Helper_Message::warn(t('module_doesnt_exist'));
		}
		else {
			$moduleContent = $moduleService->getContent($moduleService->getInputFilename($module));

			try {
				ob_start();
				?>
				<div class="sly-form sly-slice-form" id="addslice">
					<form action="index.php#slice<?= $prior ?>" id="slice<?= $prior ?>" method="post" enctype="multipart/form-data">
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
									<?php eval('?>'.self::replaceObjectVars($values, $moduleContent)); ?>
								</div>
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

				sly_Core::dispatcher()->notify('SLY_SLICE_POSTVIEW_ADD', $values, array(
					'module'     => $module,
					'article_id' => $articleId,
					'clang'      => $clang,
					'slot'       => $slot
				));

				$slice_content = ob_get_clean();
			}
			catch (Exception $e) {
				ob_end_clean();
				throw $e;
			}
		}

		print $slice_content;
	}

	public static function printEditSliceForm(OOArticleSlice $articleSlice, $values = array()) {
		$moduleService = sly_Service_Factory::getModuleService();

		try {
			ob_start();
			?>
			<div class="sly-form sly-slice-form" id="editslice">
				<form enctype="multipart/form-data" action="index.php#slice<?= $articleSlice->getPrior() ?>" method="post" id="REX_FORM">
					<div>
						<input type="hidden" name="page" value="content" />
						<input type="hidden" name="func" value="editArticleSlice" />
						<input type="hidden" name="article_id" value="<?= $articleSlice->getArticleId() ?>" />
						<input type="hidden" name="clang" value="<?= $articleSlice->getClang() ?>" />
						<input type="hidden" name="slice_id" value="<?= $articleSlice->getId() ?>" />
						<input type="hidden" name="slot" value="<?= $articleSlice->getSlot() ?>" />
						<input type="hidden" name="prior" value="<?= $articleSlice->getPrior() ?>" />
					</div>
					<fieldset class="rex-form-col-1">
						<legend><?= t('edit_block') ?>: <?= sly_html($moduleService->getTitle($articleSlice->getModule())) ?></legend>
						<div class="rex-form-wrapper">
							<div class="sly-contentpage-slice-input">
								<?php eval('?>'.self::replaceObjectVars($values, $articleSlice->getInput())); ?>
							</div>
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

			sly_Core::dispatcher()->notify('SLY_SLICE_POSTVIEW_EDIT', $values, array(
				'module'     => $articleSlice->getModule(),
				'article_id' => $articleSlice->getArticleId(),
				'clang'      => $articleSlice->getClang(),
				'slot'       => $articleSlice->getSlot(),
				'slice'      => $articleSlice
			));

			$slice_content = ob_get_clean();
		}
		catch (Exception $e) {
			ob_end_clean();
			throw $e;
		}

		print $slice_content;
	}

	/**
	 * Perform REX_VAR replacements
	 *
	 * @param  int    $slice_id  the slice's ID
	 * @param  string $content   current slice content
	 * @return string            parsed content
	 */
	private static function replaceObjectVars($data, $content) {
		foreach (sly_Core::getVarTypes() as $idx => $var) {
			$content = $var->getBEInput($data, $content);
		}
		return $content;
	}

	private static function focusFirstElement() {
		$layout = sly_Core::getLayout();
		$layout->addJavaScript('jQuery(function($) { $("#addslice, #editslice").find(":input:visible:enabled:not([readonly]):first").focus(); });');
	}

	public static function metaFormAddButtonBar($form, $label, $name) {
		$button = new sly_Form_Input_Button('submit', $name, $label);
		$button->setAttribute('onclick', 'return confirm(\''.$label.'?\')');
		$form->add(new sly_Form_ButtonBar(array('submit' => $button)));
	}
}
