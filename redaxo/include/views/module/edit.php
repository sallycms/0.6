<?php

include 'top.php';

if ($func == 'edit'){
	$legend = $I18N->msg('module_edit').' [ID='.$id.']';
}else{
	$legend = $I18N->msg('create_module');
}

$btn_update = '';
if ($func == 'edit'){
	$btn_update = '<input type="submit" class="rex-form-submit rex-form-submit-2" name="goon" value="'.$I18N->msg("save_module_and_continue").'"'. rex_accesskey($I18N->msg('save_module_and_continue'), $REX['ACKEY']['APPLY']) .' />';
	
}

?>
<div class="rex-form rex-form-module-editmode">
	<form action="index.php" method="post">
		<fieldset class="rex-form-col-1"><legend><?= $legend ?></legend>
		<div class="rex-form-wrapper">
			<input type="hidden" name="page" value="module" />
			<input type="hidden" name="func" value="<?= $func ?>" />
			<input type="hidden" name="save" value="1" />
			<input type="hidden" name="category_id" value="0" />
			<?php if($func == 'edit'): ?>
			<input type="hidden" name="modul_id" value="<?= $id ?>" />
			<?php endif; ?>
			<div class="rex-form-row">
				<p class="rex-form-col-a rex-form-text">
					<label for="mname"><?= $I18N->msg("module_name") ?></label>
					<input class="rex-form-text" type="text" size="10" id="mname" name="mname" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" />
				</p>
			</div>
			<div class="rex-form-row">
				<p class="rex-form-col-a rex-form-textarea">
					<label for="eingabe"><?= $I18N->msg("input") ?></label>
					<textarea class="rex-form-textarea" cols="50" rows="6" name="input" id="input"><?= htmlspecialchars($input, ENT_QUOTES, 'UTF-8') ?></textarea>
				</p>
			</div>
			<div class="rex-form-row">
				<p class="rex-form-col-a rex-form-textarea">
					<label for="ausgabe"><?= $I18N->msg("output") ?></label>
					<textarea class="rex-form-textarea" cols="50" rows="6" name="output" id="output"><?= htmlspecialchars($output, ENT_QUOTES, 'UTF-8') ?></textarea>
				</p>
			</div>
			<div class="rex-clearer"></div>
		</div>
	</fieldset>
	<fieldset class="rex-form-col-1">
		<div class="rex-form-wrapper">
			<div class="rex-form-row">
				<p class="rex-form-col-a rex-form-submit">
					<input class="rex-form-submit" type="submit" 
						value="<?= $I18N->msg("save_module_and_quit") ?>"<?=  rex_accesskey($I18N->msg('save_module_and_quit'),
$REX['ACKEY']['SAVE']) ?> /> <?= $btn_update ?>
				</p>
</div>
</div>
</fieldset>

</form></div>