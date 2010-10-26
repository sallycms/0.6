<?php

/**
 * Image-Resize Addon
 *
 * @author office[at]vscope[dot]at Wolfgang Hutteger
 * @author markus.staab[at]redaxo[dot]de Markus Staab
 * @author jan.kristinus[at]yakmara[dot]de Jan Kristinus
 *
 * @package redaxo4
 */

$func        = sly_request('func', 'string');
$service     = sly_Service_Factory::getService('AddOn');

if ($func == 'update') {
	$max_cachefiles   = sly_request('max_cachefiles', 'int');
	$max_filters      = sly_request('max_filters', 'int');
	$max_resizekb     = sly_request('max_resizekb', 'int');
	$max_resizepixel  = sly_request('max_resizepixel', 'int');
	$jpg_quality      = min(abs(sly_request('jpg_quality', 'int')), 100);
	$upscalingAllowed = (bool) sly_request('upscaling_allowed', 'string');

	$service->setProperty('image_resize', 'max_cachefiles', $max_cachefiles);
	$service->setProperty('image_resize', 'max_filters', $max_filters);
	$service->setProperty('image_resize', 'max_resizekb', $max_resizekb);
	$service->setProperty('image_resize', 'max_resizepixel', $max_resizepixel);
	$service->setProperty('image_resize', 'jpg_quality', $jpg_quality);
	$service->setProperty('image_resize', 'upscaling_allowed', $upscalingAllowed);
}



?>
<div class="rex-addon-output">
	<h2 class="rex-hl2"><?= $I18N->msg('iresize_subpage_config') ?></h2>

	<div class="rex-area">
		<div class="rex-form">
			<form action="index.php" method="post">
				<fieldset class="rex-form-col-1">
					<div class="rex-form-wrapper">
						<input type="hidden" name="page" value="image_resize" />
						<input type="hidden" name="subpage" value="settings" />
						<input type="hidden" name="func" value="update" />

						<div class="rex-form-row rex-form-element-v2">
							<p class="rex-form-text">
								<label for="max_cachefiles"><?= $I18N->msg('iresize_max_cache_files') ?></label>
								<input class="rex-form-text" type="text" id="max_cachefiles" name="max_cachefiles" value="<?= sly_html($service->getProperty('image_resize', 'max_cachefiles')) ?>" />
							</p>
						</div>

						<div class="rex-form-row rex-form-element-v2">
							<p class="rex-form-text">
								<label for="max_filters"><?= $I18N->msg('iresize_max_filters') ?></label>
								<input class="rex-form-text" type="text" id="max_filters" name="max_filters" value="<?= sly_html($service->getProperty('image_resize', 'max_filters')) ?>" />
							</p>
						</div>

						<div class="rex-form-row rex-form-element-v2">
							<p class="rex-form-text">
								<label for="max_resizekb"><?= $I18N->msg('iresize_max_resizekb') ?></label>
								<input class="rex-form-text" type="text" id="max_resizekb" name="max_resizekb" value="<?= sly_html($service->getProperty('image_resize', 'max_resizekb')) ?>" />
							</p>
						</div>

						<div class="rex-form-row rex-form-element-v2">
							<p class="rex-form-text">
								<label for="max_resizepixel"><?= $I18N->msg('iresize_max_resizepx') ?></label>
								<input class="rex-form-text" type="text" id="max_resizepixel" name="max_resizepixel" value="<?= sly_html($service->getProperty('image_resize', 'max_resizepixel')) ?>" />
							</p>
						</div>

						<div class="rex-form-row rex-form-element-v2">
							<p class="rex-form-text">
								<label for="jpg_quality"><?= $I18N->msg('iresize_jpg_quality') ?> [0-100]</label>
								<input class="rex-form-text" type="text" id="jpg_quality" name="jpg_quality" value="<?= sly_html($service->getProperty('image_resize', 'jpg_quality')) ?>" />
							</p>
						</div>

						<div class="rex-form-row rex-form-element-v2">
							<p class="rex-form-text">
								<label><?= $I18N->msg('iresize_upscaling_allowed') ?></label>
								<input type="radio" id="upscaling_allowed_yes" name="upscaling_allowed" value="1"<?= ($service->getProperty('image_resize', 'upscaling_allowed')) ? ' checked="checked"' : '' ?> style="width:auto;" />
								<label for="upscaling_allowed_yes" style="display:inline; float:none; padding-right: 10px;"><?= $I18N->msg('iresize_upscaling_allowed_yes') ?></label>
								<input type="radio" id="upscaling_allowed_no" name="upscaling_allowed" value="0"<?= (!$service->getProperty('image_resize', 'upscaling_allowed')) ? ' checked="checked"' : '' ?> style="width:auto;" />
								<label for="upscaling_allowed_no" style="display:inline; float:none;"><?= $I18N->msg('iresize_upscaling_allowed_no') ?></label>
							</p>
						</div>

						<div class="rex-form-row rex-form-element-v2">
							<p class="rex-form-submit">
								<input type="submit" class="rex-form-submit" name="sendit" value="<?= $I18N->msg('update') ?>" />
							</p>
						</div>
					</div>
				</fieldset>
			</form>
		</div>
	</div>
</div>
