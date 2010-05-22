<?php

class sly_Controller_Module_Actions extends sly_Controller_Sally
{
	protected $func = '';
	
	public function init()
	{
		global $I18N;
		
		rex_title(t('modules').': '.t('actions'), array(
			array('',        t('modules')),
			array('actions', t('actions'))
		));

		$layout = sly_Core::getLayout();
		$layout->appendToTitle(t('modules').': '.t('actions'));

		print '<div class="sly-content">';
	}
	
	public function teardown()
	{
		print '</div>';
	}

	public function index()
	{
		$this->listActions();
		return true;
	}

	public function add()
	{
		global $I18N;

		$save  = sly_post('save', 'boolean', false);
		$user  = sly_Core::config()->get('USER');
		$login = $user->getValue('login');
		$now   = time();
		
		if ($save) {
			$action = array(
				'name'       => sly_post('name', 'string', ''),
				'createuser' => $login,
				'updateuser' => $login,
				'createdate' => $now,
				'updatedate' => $now,
				'revision'   => 0
			);
			
			// Stati
			
			$modes = array('preview', 'presave', 'postsave');
			
			foreach ($modes as $mode) {
				$action[$mode] = sly_post($mode.'_action', 'string', '');
				
				$stati  = sly_postArray($mode.'_status', 'int', array());
				$status = 0;
				foreach ($stati as $stat) $status |= $stat;
				$action[$mode.'mode'] = $status;
			}

			$service = sly_Service_Factory::getService('Action');
			$service->create($action);
			
			print rex_info($I18N->msg('action_added'));
			$this->listActions();
			return true;
		}

		$this->func = 'add';
		$this->render('views/module/actions/edit.phtml', array('action' => null));
		return true;
	}
	
	public function edit()
	{
		global $I18N;
			
		$action = $this->getAction();
		
		if ($action === null) {
			$this->listActions();
			return false;
		}
		
		$save = sly_post('save', 'boolean', false);
		
		if ($save) {
			$action->setName(sly_post('name', 'string', ''));
			
			// Stati
			
			$modes = array('preview', 'presave', 'postsave');
			
			foreach ($modes as $mode) {
				// setXY(sly_post(xy))
				call_user_func_array(array($action, 'set'.ucfirst($mode)), array(sly_post($mode.'_action', 'string', '')));
				
				$stati  = sly_postArray($mode.'_status', 'int', array());
				$status = 0;
				foreach ($stati as $stat) $status |= $stat;
				
				// setXYMode($status)
				call_user_func_array(array($action, 'set'.ucfirst($mode).'Mode'), array($status));
			}
			
			$service = sly_Service_Factory::getService('Action');
			$action  = $service->save($action);
			
			print rex_info($I18N->msg('action_updated'));
			$goon = sly_post('goon', 'boolean', false);

			if (!$goon) {
				$this->listActions();
				return true;
			}
		}

		$params     = array('action' => $action);
		$this->func = 'edit';
		$this->render('views/module/actions/edit.phtml', $params);
		return true;
	}
	
	public function delete()
	{
		global $REX, $I18N;
		
		$action = $this->getAction();
		
		if ($action === null) {
			$this->listActions();
			return false;
		}
		
		$service = sly_Service_Factory::getService('Action');
		$modules = $service->findModules($action);
		
		if (!empty($modules)) {
			$errormsg = array();
			
			foreach ($modules as $moduleID => $module) {
				$errormsg[] = '<li><a href="index.php?page=module&amp;function=edit&amp;id='.$moduleID.'">'.sly_html($module->getName()).' [ID = '.$moduleID.']</a></li>';
			}

			$actionName = sly_html($action->getName());
			$warning    = '<ul>'.implode("\n", $errormsg).'</ul>';
			
			print rex_warning($I18N->msg('action_cannot_be_deleted', $actionName).$warning);
			return false;
		}
		
		$action->delete();
		print rex_info($I18N->msg('action_deleted'));
		
		$this->listActions();
		return true;
	}

	public function checkPermission()
	{
		global $REX;
		return isset($REX['USER']) && $REX['USER']->isAdmin();
	}

	protected function listActions()
	{
		$service = sly_Service_Factory::getService('Action');
		$actions = $service->find(null, null, 'name', null, null);
		$this->render('views/module/actions/list.phtml', array('actions' => $actions));
	}
	
	protected function getAction()
	{
		global $I18N;
		
		$actionID = sly_request('id', 'int', 0);
		$service  = sly_Service_Factory::getService('Action');
		$action   = $service->findById($actionID);
		
		if (!$actionID || $action === null) {
			print rex_warning($I18N->msg('action_not_exists'));
			return null;
		}
		
		return $action;
	}
}
