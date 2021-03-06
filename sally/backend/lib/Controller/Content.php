<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Content extends sly_Controller_Content_Base {
	protected $slot;
	protected $localInfo;
	protected $localWarning;

	protected function init() {
		parent::init();
		$this->slot = sly_request('slot', 'string', sly_Util_Session::get('contentpage_slot', ''));

		// validate slot
		if ($this->article->hasTemplate()) {
			$templateName = $this->article->getTemplateName();

			if (!sly_Service_Factory::getTemplateService()->hasSlot($templateName, $this->slot)) {
				$this->slot = sly_Service_Factory::getTemplateService()->getFirstSlot($templateName);
			}
		}

		sly_Util_Session::set('contentpage_slot', $this->slot);
	}

	public function indexAction($extraparams = array()) {
		$this->init();
		if ($this->header() !== true) return;

		$service      = sly_Service_Factory::getArticleTypeService();
		$articletypes = $service->getArticleTypes();
		$modules      = array();

		if ($this->article->hasType()) {
			try {
				$modules = $service->getModules($this->article->getType(), $this->slot);
			}
			catch (Exception $e) {
				$modules = array();
			}
		}

		foreach ($modules as $idx => $module)    $modules[$idx]      = sly_translate($module);
		foreach ($articletypes as $idx => $type) $articletypes[$idx] = sly_translate($type);

		uasort($articletypes, 'strnatcasecmp');
		uasort($modules, 'strnatcasecmp');

		$params = array(
			'article'      => $this->article,
			'articletypes' => $articletypes,
			'modules'      => $modules,
			'slot'         => $this->slot,
			'slice_id'     => sly_request('slice_id', 'int', 0),
			'pos'          => sly_request('pos', 'int', 0),
			'function'     => sly_request('function', 'string'),
			'module'       => sly_request('add_module', 'string')
		);

		$params = array_merge($params, $extraparams);
		print $this->render('content/index.phtml', $params);
	}

	protected function getPageName() {
		return 'content';
	}

	public function checkPermission($action, $forceModule = null) {
		$this->action = $action;

		if (parent::checkPermission($this->action)) {
			$user = sly_Util_User::getCurrentUser();

			if ($action === 'moveslice') {
				return ($user->isAdmin() || $user->hasRight('transitional', 'moveSlice'));
			}

			if ($action === 'addarticleslice') {
				$module = $forceModule === null ? sly_request('module', 'string') : $forceModule;
				return ($user->isAdmin() || $user->hasRight('module', 'add', sly_Authorisation_ModuleListProvider::ALL) || $user->hasRight('module', 'add', $module));
			}

			if ($action === 'editarticleslice') {
				// skip the slice stuff if the user is admin
				if ($user->isAdmin()) return true;

				if ($forceModule === null) {
					$sliceservice = sly_Service_Factory::getArticleSliceService();
					$slice_id     = sly_request('slice_id', 'int', 0);
					$slice        = $sliceservice->findById($slice_id);
					$module       = $slice->getModule();
				}
				else {
					$module = $forceModule;
				}

				return $user->hasRight('module', 'edit', sly_Authorisation_ModuleListProvider::ALL) || $user->hasRight('module', 'edit', $module);
			}

			return true;
		}

		return false;
	}

	public function setarticletypeAction() {
		$this->init();

		$type    = sly_post('article_type', 'string');
		$service = sly_Service_Factory::getArticleService();

		// change type and update database
		$service->setType($this->article, $type);

		$this->info    = t('article_updated');
		$this->article = $service->findById($this->article->getId(), $this->article->getClang());

		$this->indexAction();
	}

	public function movesliceAction() {
		$this->init();

		$slice_id  = sly_get('slice_id', 'int', null);
		$direction = sly_get('direction', 'string', null);

		// check of module exists
		$module = sly_Util_ArticleSlice::getModule($slice_id);

		if (!$module) {
			$this->warning = t('module_not_found');
		}
		else {
			$user  = sly_Util_User::getCurrentUser();
			$clang = sly_Core::getCurrentClang();

			// check permission
			if ($user->isAdmin() || $user->hasRight('transitional', 'moveSlice') || $user->hasRight('module', 'edit', sly_Authorisation_ModuleListProvider::ALL) || $user->hasRight('module', 'edit', $module)) {
				$success = sly_Service_Factory::getArticleSliceService()->move($slice_id, $clang, $direction);

				if ($success) {
					$this->localInfo = t('slice_moved');
				}
				else {
					$this->localWarning = t('cannot_move_slice');
				}
			}
			else {
				$this->warning = t('no_rights_to_this_function');
			}
		}

		$this->indexAction();
	}

	public function addarticlesliceAction() {
		$this->init();

		$module      = sly_post('module', 'string');
		$user        = sly_Util_User::getCurrentUser();
		$extraparams = array();
		$slicedata   = $this->preSliceEdit('add');

		if ($slicedata['SAVE'] === true) {
			$sliceService = sly_Service_Factory::getArticleSliceService();

			// create the slice
			$slice = $sliceService->create(
				array(
					'pos'        => sly_post('pos', 'int'),
					'article_id' => $this->article->getId(),
					'clang'      => $this->article->getClang(),
					'slot'       => $this->slot,
					'module'     => $module,
					'revision'   => 0,
					'createdate' => time(),
					'createuser' => $user->getLogin(),
					'updatedate' => time(),
					'updateuser' => $user->getLogin()
				)
			);

			$this->setSliceValues($slicedata, $slice);

			$this->localInfo = t('slice_added');

			$this->postSliceEdit('add', $slice->getId());
		}
		else {
			$extraparams['function']    = 'add';
			$extraparams['module']      = $module;
			$extraparams['slicevalues'] = $this->getRequestValues(array());
		}

		$this->indexAction($extraparams);
	}

	public function editarticlesliceAction() {
		$this->init();

		$sliceservice = sly_Service_Factory::getArticleSliceService();
		$slice_id     = sly_request('slice_id', 'int', 0);
		$slice        = $sliceservice->findById($slice_id);

		$slicedata = $this->preSliceEdit('edit');

		if ($slicedata['SAVE'] === true) {
			$slice->setUpdateColumns();
			$slice->flushValues();
			$this->setSliceValues($slicedata, $slice);

			$sliceservice->save($slice);

			$this->localInfo .= t('slice_updated');
			$this->postSliceEdit('edit', $slice_id);
		}

		$extraparams = array();
		if (sly_post('btn_update', 'string') || $slicedata['SAVE'] !== true) {
			$extraparams['slicevalues'] = $slicedata['VALUES'];
			$extraparams['function']    = 'edit';
		}

		$this->indexAction($extraparams);
	}

	public function deletearticlesliceAction() {
		$this->init();

		$ok      = false;
		$sliceID = sly_request('slice_id', 'int', 0);
		$slice   = sly_Util_ArticleSlice::findById($sliceID);

		if (!$slice) {
			$this->localWarning = t('module_not_found', $sliceID);
			return $this->indexAction();
		}

		$module = $slice->getModule();
		$user   = sly_Util_User::getCurrentUser();

		if (!$user->isAdmin() && !$user->hasRight('module', 'edit', sly_Authorisation_ModuleListProvider::ALL) && !$user->hasRight('module', 'edit', $module)) {
			$this->localWarning = t('no_rights_to_this_module');
			return $this->indexAction();
		}

		if ($this->preSliceEdit('delete') !== false) {
			$ok = sly_Util_ArticleSlice::deleteById($sliceID);
		}

		if ($ok) {
			$this->localInfo = t('slice_deleted');
			$this->postSliceEdit('delete', $sliceID);
		}
		else {
			$this->localWarning = t('cannot_delete_slice');
		}

		$this->indexAction();
	}

	private function preSliceEdit($function) {
		if (!$this->article->hasTemplate()) return false;
		$user = sly_Util_User::getCurrentUser();

		if ($function == 'delete' || $function == 'edit') {
			$slice_id = sly_request('slice_id', 'int', 0);
			if (!sly_Util_ArticleSlice::exists($slice_id)) return false;
			$module = sly_Util_ArticleSlice::getModuleNameForSlice($slice_id );
		}
		else {
			$module = sly_post('module', 'string');
		}

		if ($function !== 'delete') {
			if (!sly_Service_Factory::getModuleService()->exists($module)) {
				$this->warning = t('module_not_found');
				return false;
			}

			if (!sly_Service_Factory::getArticleTypeService()->hasModule($this->article->getType(), $module, $this->slot)) {
				$this->warning = t('no_rights_to_this_function');
				return false;
			}
		}

		// Daten einlesen
		$slicedata = array('SAVE' => true, 'MESSAGES' => array());

		if ($function != 'delete') {
			$slicedata = $this->getRequestValues($slicedata);
		}

		// ----- PRE SAVE EVENT [ADD/EDIT/DELETE]
		$eventparams = array('module' => $module, 'article_id' => $this->article->getId(), 'clang' => $this->article->getClang());
		$slicedata   = sly_Core::dispatcher()->filter('SLY_SLICE_PRESAVE_'.strtoupper($function), $slicedata, $eventparams);

		if (!$slicedata['SAVE']) {
			// DONT SAVE/UPDATE SLICE
			if (!empty($slicedata['MESSAGES'])) {
				$this->localWarning = implode('<br />', $slicedata['MESSAGES']);
			}
			elseif ($this->action == 'deleteArticleSlice') {
				$this->localWarning = t('cannot_delete_slice');
			}
			else {
				$this->localWarning = t('cannot_update_slice');
			}

		}

		return $slicedata;
	}

	private function postSliceEdit($function, $articleSliceId) {
		$user = sly_Util_User::getCurrentUser();
		sly_Service_Factory::getArticleService()->touch($this->article, $user);

		$messages = sly_Core::dispatcher()->filter('SLY_SLICE_POSTSAVE_'.strtoupper($function), '', array('article_slice_id' => $articleSliceId));

		if (!empty($messages)) {
			$this->localInfo .= implode('<br />', $messages);
		}

		sly_core::dispatcher()->notify('SLY_CONTENT_UPDATED', '', array('article_id' => $this->article->getId(), 'clang' => $this->article->getClang()));
	}

	private function getRequestValues(array $slicedata) {
		$slicedata['VALUES'] = sly_post('slicevalue', 'array', array());
		return $slicedata;
	}

	private function setSliceValues(array $slicedata, sly_Model_ArticleSlice $slice) {
		if(isset($slicedata['VALUES'])) {
			$slice->setValues($slicedata['VALUES']);
		}
	}
}
