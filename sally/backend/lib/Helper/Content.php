<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
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
	public static function printAddSliceForm($module, $position, $articleId, $clang, $slot) {
		$moduleService = sly_Service_Factory::getModuleService();

		if (!$moduleService->exists($module)) {
			$slice_content = sly_Helper_Message::warn(ht('module_not_found', $module));
		}
		else {
			try {
				ob_start();
				?>
				<div class="sly-form sly-slice-form" id="addslice">
					<form action="index.php#slice<?php echo $position ?>" id="slice<?php echo $position ?>" method="post" enctype="multipart/form-data">
						<div>
							<input type="hidden" name="page" value="content" />
							<input type="hidden" name="func" value="addArticleSlice" />
							<input type="hidden" name="article_id" value="<?php echo $articleId ?>" />
							<input type="hidden" name="clang" value="<?php echo $clang ?>" />
							<input type="hidden" name="slot" value="<?php echo $slot ?>" />
							<input type="hidden" name="module" value="<?php echo sly_html($module) ?>" />
							<input type="hidden" name="pos" value="<?php echo $position ?>" />
						</div>
						<fieldset class="sly-form-col-1">
							<legend><?php echo t('add_slice') ?>: <?php echo sly_translate($moduleService->getTitle($module), true) ?></legend>
							<div class="sly-form-wrapper">
								<div class="sly-contentpage-slice-input">
									<?php
									$renderer = new sly_Slice_Renderer($module);
									print $renderer->renderInput('slicevalue');
									?>
								</div>
								<div class="sly-form-row">
									<div class="sly-form-buttonbar">
										<input class="sly-form-submit" type="submit" name="btn_save" value="<?php echo t('add_slice') ?>" />
									</div>
								</div>
							</div>
						</fieldset>
					</form>
				</div>

				<?php
				self::focusFirstElement();

				sly_Core::dispatcher()->notify('SLY_SLICE_POSTVIEW_ADD', array(), array(
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

	public static function printEditSliceForm(sly_Model_ArticleSlice $articleSlice, $values = array()) {
		$moduleService = sly_Service_Factory::getModuleService();
		$moduleTitle   = $moduleService->getTitle($articleSlice->getModule());

		try {
			ob_start();
			?>
			<div class="sly-form sly-slice-form" id="editslice">
				<form action="index.php#slice<?php echo $articleSlice->getPosition() ?>" method="post" enctype="multipart/form-data">
					<div>
						<input type="hidden" name="page" value="content" />
						<input type="hidden" name="func" value="editArticleSlice" />
						<input type="hidden" name="article_id" value="<?php echo $articleSlice->getArticleId() ?>" />
						<input type="hidden" name="clang" value="<?php echo $articleSlice->getClang() ?>" />
						<input type="hidden" name="slice_id" value="<?php echo $articleSlice->getId() ?>" />
						<input type="hidden" name="slot" value="<?php echo $articleSlice->getSlot() ?>" />
						<input type="hidden" name="pos" value="<?php echo $articleSlice->getPosition() ?>" />
					</div>
					<fieldset class="sly-form-col-1">
						<legend><?php echo t('edit_slice') ?>: <?php echo sly_translate($moduleTitle, true) ?></legend>
						<div class="sly-form-wrapper">
							<div class="sly-contentpage-slice-input">
								<?php
									$renderer = new sly_Slice_Renderer($articleSlice->getModule(), $values);
									print $renderer->renderInput('slicevalue');
								?>
							</div>
							<div class="sly-form-row">
								<div class="sly-form-buttonbar">
									<input class="sly-form-submit" type="submit" value="<?php echo t('save') ?>" name="btn_save" />
									<input class="sly-form-submit" type="submit" value="<?php echo t('apply') ?>" name="btn_update" />
								</div>
							</div>
						</div>
					</fieldset>
				</form>
			</div>
			<?php
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
