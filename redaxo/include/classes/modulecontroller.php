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

class ModuleController extends Controller {

	protected function checkPermission(){
		global $REX;
		return isset($REX['USER']) && $REX['USER']->isAdmin();
	}
	
	protected function add_action(){
		global $REX, $I18N;

		$modul_id	= rex_request('modul_id', 'rex-module-id');

		$save = rex_post('save', 'bool', false);
		if($save){
			$action_id	= rex_request('action_id', 'rex-action-id');

			$action = new rex_sql();
			$action->setTable($REX['TABLE_PREFIX'].'module_action');
			$action->setValue('module_id', $modul_id);
			$action->setValue('action_id', $action_id);

			if($action->insert())
			{
				print rex_info($I18N->msg('action_taken'));
			}
			else
			{
				print rex_warning($action->getError());
			}
		}

		$service = sly_Service_Factory::getService('Module');
		$module = $service->findById($modul_id);

		$params['func'] = 'edit';
		$params['id'] = $module->getId();
		$params['name'] = $module->getName();
		$params['input'] = $module->getInput();
		$params['output'] = $module->getOutput();

		$this->render('views/module/edit.php', $params);

		$params = array('modul_id' => $modul_id);

		$this->render('views/module/module_action.php', $params);
	}

	protected function delete_action(){
		global $REX, $I18N;
		$modul_id	= rex_get('modul_id', 'rex-module-id');
		$iaction_id	= rex_request('iaction_id', 'rex-action-id');

		$action = new rex_sql();
		$action->setTable($REX['TABLE_PREFIX'].'module_action');
		$action->setWhere('id='. $iaction_id . ' LIMIT 1');

		print rex_info($action->delete($I18N->msg('action_deleted_from_modul')));


		$service = sly_Service_Factory::getService('Module');
		$module = $service->findById($modul_id);

		$params['func'] = 'edit';
		$params['id'] = $module->getId();
		$params['name'] = $module->getName();
		$params['input'] = $module->getInput();
		$params['output'] = $module->getOutput();

		$this->render('views/module/edit.php', $params);

		$params = array('modul_id' => $modul_id);

		$this->render('views/module/module_action.php', $params);
	}
}
