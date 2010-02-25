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
<?php 
    if ($func == 'edit')
    {
      // Im Edit Mode Aktionen bearbeiten

      $gaa = new rex_sql;
      $gaa->setQuery("SELECT * FROM ".$REX['TABLE_PREFIX']."action ORDER BY name");

      if ($gaa->getRows()>0)
      {
        $gma = new rex_sql;
        $gma->setQuery("SELECT * FROM ".$REX['TABLE_PREFIX']."module_action, ".$REX['TABLE_PREFIX']."action WHERE ".$REX['TABLE_PREFIX']."module_action.action_id=".$REX['TABLE_PREFIX']."action.id and ".$REX['TABLE_PREFIX']."module_action.module_id='$modul_id'");
				
				$add_header = '';
				$add_col = '';
				if ($REX['USER']->hasPerm('advancedMode[]'))
				{
					$add_header = '<th class="rex-small">'.$I18N->msg('header_id').'</th>';
					$add_col = '<col width="40" />';
				}
				
        $actions = '';
        for ($i=0; $i<$gma->getRows(); $i++)
        {
          $iaction_id = $gma->getValue($REX['TABLE_PREFIX'].'module_action.id');
          $action_id = $gma->getValue($REX['TABLE_PREFIX'].'module_action.action_id');
          $action_edit_url = 'index.php?page=module&amp;subpage=actions&amp;action_id='.$action_id.'&amp;function=edit';
          $action_name = rex_translate($gma->getValue('name'));

          $actions .= '<tr>
          	<td class="rex-icon"><a class="rex-i-element rex-i-action" href="'. $action_edit_url .'"><span class="rex-i-element-text">' . htmlspecialchars($action_name) . '</span></a></td>';
          	
					if ($REX['USER']->hasPerm('advancedMode[]'))
					{
             $actions .= '<td class="rex-small">' . $gma->getValue("id") . '</td>';
          }
          	
          $actions .= '<td><a href="'. $action_edit_url .'">'. $action_name .'</a></td>
          	<td><a href="index.php?page=module&amp;modul_id='.$modul_id.'&amp;function_action=delete&amp;function=edit&amp;iaction_id='.$iaction_id.'" onclick="return confirm(\''.$I18N->msg('delete').' ?\')">'.$I18N->msg('action_delete').'</a></td>
          </tr>';

          $gma->next();
        }

        if($actions !='')
        {
          $actions = '
  					<table class="rex-table" summary="'.$I18N->msg('actions_added_summary').'">
  						<caption>'.$I18N->msg('actions_added_caption').'</caption>
    					<colgroup>
      				<col width="40" />
      				'.$add_col.'
      				<col width="*" />
      				<col width="153" />
    					</colgroup>
    					<thead>
      					<tr>
        					<th class="rex-icon">&nbsp;</th>
        					'.$add_header.'
        					<th>' . $I18N->msg('action_name') . '</th>
        					<th>' . $I18N->msg('action_functions') . '</th>
      					</tr>
    					</thead>
    				<tbody>
              '. $actions .'
            </tbody>
            </table>
          ';
        }

        $gaa_sel = new rex_select();
        $gaa_sel->setName('action_id');
        $gaa_sel->setId('action_id');
        $gaa_sel->setSize(1);
        $gaa_sel->setStyle('class="rex-form-select"');

        for ($i=0; $i<$gaa->getRows(); $i++)
        {
          $gaa_sel->addOption(rex_translate($gaa->getValue('name'), null, false),$gaa->getValue('id'));
          $gaa->next();
        }

        echo
        $actions .'
				<fieldset class="rex-form-col-1">
          <legend>'.$I18N->msg('action_add').'</legend>
      		<div class="rex-form-wrapper">
						
						<div class="rex-form-row">
							<p class="rex-form-col-a rex-form-select">
								<label for="action_id">'.$I18N->msg('action').'</label>
								'.$gaa_sel->get().'
					  	</p>
					  </div>
					  
						<div class="rex-form-row">
					  	<p class="rex-form-col-a rex-form-submit">
								<input class="rex-form-submit" type="submit" value="'.$I18N->msg('action_add').'" name="add_action" />
					  	</p>
					  </div>
				  </div>
        </fieldset>';
      }
    }


?>
</form></div>