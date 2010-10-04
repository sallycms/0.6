<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Module extends sly_Controller_Sally
{
	protected $func = '';

	public function init()
	{
		rex_title(t('modules'), array(
			array('',        t('modules')),
			array('actions', t('actions'))
		));

		$layout = sly_Core::getLayout();
		$layout->appendToTitle(t('modules'));

      	print '<div class="sly-content">';
	}

	public function teardown()
	{
		print '</div>';
	}

	public function index()
	{
		$this->listModules();
		return true;
	}

	public function add()
	{
		global $I18N, $REX;

		$save  = sly_post('save', 'boolean', false);
		$user  = $REX['USER'];
		$login = $user->getValue('login');
		$now   = time();

		if ($save) {
			$module = array(
				'name'        => sly_post('name', 'string', ''),
				'eingabe'     => sly_post('input', 'string', ''),
				'ausgabe'     => sly_post('output', 'string', ''),
				'category_id' => sly_post('category_id', 'int', 0),
				'createuser'  => $login,
				'updateuser'  => $login,
				'createdate'  => $now,
				'updatedate'  => $now,
				'attributes'  => '',
				'revision'    => 0
			);

			$service = sly_Service_Factory::getService('Module');
			$service->create($module);

			print rex_info($I18N->msg('module_added'));
			$this->listModules();
			return true;
		}

		$this->func = 'add';
		$this->render('views/module/edit.phtml', array('module' => null));
		return true;
	}

	public function edit()
	{
		global $I18N;

		$module = $this->getModule();

		if ($module === null) {
			$this->listModules();
			return false;
		}

		$save    = sly_post('save', 'boolean', false);
		$service = sly_Service_Factory::getService('Module');

		if ($save) {
			$module->setName(sly_post('name', 'string', ''));
			$module->setInput(sly_post('input', 'string', ''));
			$module->setOutput(sly_post('output', 'string', ''));
			$module = $service->save($module);

			print rex_info($I18N->msg('module_updated').' | '.$I18N->msg('articles_updated'));

			$goon = sly_post('goon', 'boolean', false);

			if (!$goon) {
				$this->listModules();
				return true;
			}
		}

		$params     = array('module' => $module, 'actions' => $service->getAttachedActions($module));
		$this->func = 'edit';

		$this->render('views/module/edit.phtml', $params);

		if (!empty($params['actions'])) {
			$this->render('views/module/module_action.phtml', $params);
		}

		return true;
	}

	public function delete()
	{
		global $REX, $I18N;

		$module = $this->getModule();

		if ($module === null) {
			$this->listModules();
			return false;
		}

		$service = sly_Service_Factory::getService('Module');
		$usages  = $service->findUsages($module);

		if (!empty($usages)) {
			$errormsg     = array();
			$languages    = sly_Core::config()->get('CLANG');
			$multilingual = count($languages) > 1;

			foreach ($usages as $articleID => $usage) {
				$article = $usage['article'];
				$clangID = $usage['clang'];
				$aID     = $article->getId();
				$label   = $article->getName().' ['.$aID.']';

				if ($multilingual) {
					$label = '('.rex_translate($languages[$clangID]).') '.$label;
				}

				$errormsg[] = '<li><a href="index.php?page=content&amp;article_id='.$aID.'&amp;clang='.$clangID.'&amp;slot='.$usage['slot'].'">'.sly_html($label).'</a></li>';
			}

			$moduleName = sly_html($module->getName());
			$warning    = '<ul>'.implode("\n", $errormsg).'</ul>';

			print rex_warning($I18N->msg('module_cannot_be_deleted', $moduleName).$warning);
			return false;
		}

		$service->deleteWithActions($module);
		print rex_info($I18N->msg('module_deleted'));

		$this->listModules();
		return true;
	}

	public function add_action()
	{
		global $I18N;

		$module   = $this->getModule();
		$service  = sly_Service_Factory::getService('Module');
		$save     = rex_post('save', 'boolean', false);
		$actionID = sly_post('action_id', 'rex-action-id');

		if ($save && $actionID) {
			$action = sly_Service_Factory::getService('Action')->findById($actionID);
			$service->attachAction($module, $action);
			print rex_info($I18N->msg('action_taken'));
		}

		unset($_POST['save']);
		$this->edit();
	}

	public function delete_action()
	{
		global $I18N;

		$pid     = sly_get('pid', 'int', 0);
		$service = sly_Service_Factory::getService('Module');

		if ($service->detachActionById($pid)) {
			print rex_info($I18N->msg('action_deleted_from_module'));
		}
		else {
			print rex_warning('Fehler beim Löschen der Verknüpfung.');
		}

		unset($_POST['save']);
		$this->edit();
	}

	public function checkPermission()
	{
		global $REX;
		return isset($REX['USER']) && $REX['USER']->isAdmin();
	}

	protected function listModules()
	{
		$service = sly_Service_Factory::getService('Module');
		$modules = $service->find(null, null, 'name', null, null);
		$this->render('views/module/list.phtml', array('modules' => $modules));
	}

	protected function getModule()
	{
		global $I18N;

		$moduleID = sly_request('id', 'int', 0);
		$service  = sly_Service_Factory::getService('Module');
		$module   = $service->findById($moduleID);

		if (!$moduleID || $module === null) {
			print rex_warning($I18N->msg('module_not_exists'));
			return null;
		}

		return $module;
	}
}
