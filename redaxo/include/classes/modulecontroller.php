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

	protected function index(){
		$this->listModules();
	}

	protected function checkPermission(){
		global $REX;
		return isset($REX['USER']) && $REX['USER']->isAdmin();
	}

	protected function listModules(){
		$params = array();
		$this->render('views/module/list.php', $params);
	}

	protected function add(){
		global $REX,$I18N;
		$params = array('name' => '', 'input' => '', 'output' => '');

		$save = rex_post('save', 'bool', false);
		if($save){
			$mod['name'] = rex_post('mname', 'string', '');
			$mod['eingabe'] = rex_post('input', 'string', '');
			$mod['ausgabe'] = rex_post('output', 'string', '');
			$mod['category_id'] = rex_post('category_id', 'string', '');
			$mod['createuser'] = $REX['USER']->getValue('login');
			$mod['updateuser'] = $REX['USER']->getValue('login');
			$mod['createdate'] = time();
			$mod['updatedate'] = time();
			$mod['attributes'] = '';
			$mod['revision'] = 0;

			$service = Service_Factory::getService('Module');
			$service->create($mod);
			print rex_info($I18N->msg('module_added'));
			$this->listModules();
			return true;
		}

		$params = array();
		$params['func'] = 'add';
		
		$this->render('views/module/edit.php', $params);
	}

	protected function edit(){
		global $I18N;
			
		$module_id = rex_request('modul_id', 'int', null);
		if(!$module_id){
			// TODO: I18N
			print rex_warning('Modul existiert nicht.');
			$this->listModules();
		}
		$params = array();

		$service = Service_Factory::getService('Module');
		$module = $service->findById($module_id);

		$save = rex_post('save', 'bool', false);
		if($save){
			$module->setName(rex_post('mname', 'string', ''));
			$module->setInput(rex_post('input', 'string', ''));
			$module->setOutput(rex_post('output', 'string', ''));
			$module = $service->save($module);

			print rex_info($I18N->msg('module_updated').' | '.$I18N->msg('articel_updated'));

			$goon = rex_post('goon', 'bool', false);

			if(!$goon){
				$this->listModules();
				return true;
			}
		}

		$params['func'] = 'edit';
		$params['id'] = $module->getId();
		$params['name'] = $module->getName();
		$params['input'] = $module->getInput();
		$params['output'] = $module->getOutput();

		$this->render('views/module/edit.php', $params);

		$params['modul_id'] = $module->getId();

		$this->render('views/module/module_action.php', $params);
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

		$service = Service_Factory::getService('Module');
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


		$service = Service_Factory::getService('Module');
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

	protected function delete(){
		global $REX, $I18N;

		$modul_id	= rex_get('modul_id', 'rex-module-id');
		if(!$modul_id){
			// TODO: I18N
			print rex_warning('Modul existiert nicht.');
				
		}else{

			$del = new rex_sql;
			$del->setQuery("SELECT ".$REX['TABLE_PREFIX']."article_slice.article_id, ".$REX['TABLE_PREFIX']."article_slice.clang, ".$REX['TABLE_PREFIX']."article_slice.ctype, ".$REX['TABLE_PREFIX']."module.name FROM ".$REX['TABLE_PREFIX']."article_slice
      		LEFT JOIN ".$REX['TABLE_PREFIX']."module ON ".$REX['TABLE_PREFIX']."article_slice.modultyp_id=".$REX['TABLE_PREFIX']."module.id
      		WHERE ".$REX['TABLE_PREFIX']."article_slice.modultyp_id='$modul_id' GROUP BY ".$REX['TABLE_PREFIX']."article_slice.article_id");

			if ($del->getRows() > 0)
			{
				$module_in_use_message = '';
				$modulname = htmlspecialchars($del->getValue($REX['TABLE_PREFIX']."module.name"));
				for ($i=0; $i<$del->getRows(); $i++)
				{
					$aid = $del->getValue($REX['TABLE_PREFIX']."article_slice.article_id");
					$clang_id = $del->getValue($REX['TABLE_PREFIX']."article_slice.clang");
					$ctype = $del->getValue($REX['TABLE_PREFIX']."article_slice.ctype");
					$OOArt = OOArticle::getArticleById($aid, $clang_id);

					$label = $OOArt->getName() .' ['. $aid .']';
					if(count($REX['CLANG']) > 1)
					$label = '('. rex_translate($REX['CLANG'][$clang_id]) .') '. $label;

					$module_in_use_message .= '<li><a href="index.php?page=content&amp;article_id='. $aid .'&clang='. $clang_id .'&ctype='. $ctype .'">'. htmlspecialchars($label) .'</a></li>';
					$del->next();
				}

				if($module_in_use_message != '')
				{
					$warning_block = '<ul>' . $module_in_use_message . '</ul>';
				}

				print rex_warning($I18N->msg("module_cannot_be_deleted",$modulname).$warning_block);
			} else
			{
				$service = Service_Factory::getService('Module');
				$service->delete(array('id' => $modul_id));
				$del->setQuery("DELETE FROM ".$REX['TABLE_PREFIX']."module_action WHERE module_id='$modul_id'");

				print rex_info($I18N->msg("module_deleted"));
			}
		}
		$this->listModules();
	}

}
