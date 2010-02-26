<?php
/*
 * Copyright (c) 2009, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

?>

<div class="rex-form rex-form-module-editmode">
	<form action="index.php" method="post">

<?php 
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
          	<td><a href="index.php?page=module&amp;modul_id='.$modul_id.'&amp;func=delete_action&amp;function=edit&amp;iaction_id='.$iaction_id.'" onclick="return confirm(\''.$I18N->msg('delete').' ?\')">'.$I18N->msg('action_delete').'</a></td>
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

?>
		<?= $actions ?>
		<fieldset class="rex-form-col-1">
          <legend><?= $I18N->msg('action_add') ?></legend>
      		<div class="rex-form-wrapper">
      			<input type="hidden" name="page" value="module" />
      			<input type="hidden" name="modul_id" value="<?= $modul_id ?>" />
				<input type="hidden" name="func" value="add_action" />
				<input type="hidden" name="save" value="1" />
						
						<div class="rex-form-row">
							<p class="rex-form-col-a rex-form-select">
								<label for="action_id"><?= $I18N->msg('action') ?></label>
								<?= $gaa_sel->get() ?>
					  	</p>
					  </div>
					  
						<div class="rex-form-row">
					  	<p class="rex-form-col-a rex-form-submit">
								<input class="rex-form-submit" type="submit" value="<?= $I18N->msg('action_add') ?>" name="add_action" />
					  	</p>
					  </div>
				  </div>
        </fieldset>
<?php 
      }
?>
</form></div>