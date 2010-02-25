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
		global $REX;
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
		
			//TODO: add_action im edit mode 
			
			if(!$goon){
				$this->listModules();
				return true;
			}
		}
		
		$params['func'] = rex_request('func', 'string', '');
		$params['id'] = $module->getId();
		$params['name'] = $module->getName();
		$params['input'] = $module->getInput();
		$params['output'] = $module->getOutput();
		
		$this->render('views/module/edit.php', $params);
	}
	
	protected function delete(){
		//TODO: implement
		$module_id = rex_request('modul_id', 'int', null);
		if(!$module_id){
			// TODO: I18N
			print rex_warning('Modul existiert nicht.');
		}
		
		
		$this->listModules();
	}
	
}
