<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
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

	protected function index($extraparams = array()) {
		if ($this->header() !== true) return;

		$articletypes = sly_Service_Factory::getArticleTypeService()->getArticleTypes();
		uasort($articletypes, 'strnatcasecmp');

		$params = array(
			'article'      => $this->article,
			'articletypes' => $articletypes,
			'slot'         => $this->slot,
			'slice_id'     => sly_request('slice_id', 'rex-slice-id', ''),
			'prior'        => sly_request('prior', 'int', 0),
			'function'     => sly_request('function', 'string')
		);

		$params = array_merge($params, $extraparams);
		print $this->render('content/index.phtml', $params);
	}

	protected function getPageName() {
		return 'content';
	}

	protected function checkPermission() {
		if (parent::checkPermission()) {
			$user = sly_Util_User::getCurrentUser();

			if ($this->action == 'moveSlice') {
				return ($user->isAdmin() || $user->hasRight('moveSlice[]'));
			}

			if ($this->action == 'addArticleSlice') {
				$module = sly_post('module', 'string');
				return ($user->isAdmin() || $user->hasRight('module['.$module.']') || $user->hasRight('module[0]'));
			}

			return true;
		}

		return false;
	}

	protected function setArticleType() {
		$type    = sly_post('article_type', 'string');
		$service = sly_Service_Factory::getArticleService();

		// change type and update database
		$service->setType($this->article, $type);

		$this->info    = t('article_updated');
		$this->article = $service->findById($this->article->getId(), $this->article->getClang());

		$this->index();
	}

	protected function moveSlice() {
		$slice_id  = sly_get('slice_id', 'int', null);
		$direction = sly_get('direction', 'string', null);

		// Modul und Rechte vorhanden?

		require_once SLY_COREFOLDER.'/functions/function_rex_content.inc.php';
		$module = rex_slice_module_exists($slice_id);

		if (!$module) {
			$this->warning = t('module_not_found');
		}
		else {
			$user  = sly_Util_User::getCurrentUser();
			$clang = sly_Core::getCurrentClang();

			// Rechte am Modul?
			if ($user->isAdmin() || $user->hasRight('module['.$module.']') || $user->hasRight('module[0]')) {
				list($success, $message) = rex_moveSlice($slice_id, $clang, $direction);

				if ($success) {
					$this->localInfo = $message;
				}
				else {
					$this->localWarning = $message;
				}
			}
			else {
				$this->warning = t('no_rights_to_this_function');
			}
		}

		$this->index();
	}

	protected function addArticleSlice() {
		$module      = sly_post('module', 'string');
		$user        = sly_Util_User::getCurrentUser();
		$extraparams = array();
		$slicedata   = $this->preSliceEdit('add');

		if ($slicedata !== false) {
			$sql          = sly_DB_Persistence::getInstance();
			$sliceService = sly_Service_Factory::getSliceService();

			// create the slice
			$slice = $sliceService->create(array('module' => $module));
			$this->setSliceValues($slicedata, $slice);

			// create the articleslice
			$values = array(
				'prior'      => sly_post('prior', 'int'),
				'article_id' => $this->article->getId(),
				'clang'      => $this->article->getClang(),
				'slot'       => $this->slot,
				'slice_id'   => $slice->getId(),
				'revision'   => 0,
				'createdate' => time(),
				'createuser' => $user->getLogin(),
				'updatedate' => time(),
				'updateuser' => $user->getLogin()
			);

			$sql->insert('article_slice', $values);
			$id  = $sql->lastId();
			$pre = sly_Core::config()->get('DATABASE/TABLE_PREFIX');

			$sql->query(
				'UPDATE '.$pre.'article_slice SET prior = prior + 1 ' .
				'WHERE article_id = '.$this->article->getId().' AND clang = '.$this->article->getClang().' AND slot = "'.$this->slot.'" '.
				'AND prior >= '.$values['prior'].' AND id <> '.$id
			);

			$this->localInfo = t('block_added');

			$this->postSliceEdit('add', $id);
		}
		else {
			$extraparams['function']    = 'add';
			$extraparams['slicevalues'] = $this->getRequestValues(array());
		}

		$this->index($extraparams);
	}

	protected function editArticleSlice() {
		$slice_id     = sly_request('slice_id', 'rex-slice-id', 0);
		$function     = sly_request('function', 'string', '');
		$ooslice      = OOArticleSlice::getArticleSliceById($slice_id);
		$sliceService = sly_Service_Factory::getSliceService();
		$realslice    = $sliceService->findById($ooslice->getSliceId());

		$slicedata = $this->preSliceEdit('edit');

		if ($slicedata !== false) {
			$realslice->flushValues();
			$this->setSliceValues($slicedata, $realslice);

			$values = array(
				'updatedate' => time(),
				'updateuser' => sly_Util_User::getCurrentUser()->getLogin()
			);

			sly_DB_Persistence::getInstance()->update('article_slice', $values, array('id' => $slice_id));
			sly_Util_Slice::clearSliceCache($realslice->getId());
			$this->localInfo .= t('block_updated');

			$this->postSliceEdit('edit', $slice_id);
		}

		$extraparams = array();

		if (!$slicedata && $function == 'edit') {
			$extraparams['slicevalues'] = $this->getRequestValues(array());
		}

		if (sly_post('btn_update', 'string')) {
			$extraparams['function'] = 'edit';
		}

		$this->index($extraparams);
	}

	protected function deleteArticleSlice() {
		$ok = false;

		if ($this->preSliceEdit('delete') !== false) {
			$slice_id = sly_request('slice_id', 'rex-slice-id', '');
			$ok       = sly_Util_ArticleSlice::deleteById($slice_id);
		}

		if ($ok) {
			$this->localInfo = t('block_deleted');
		}
		else {
			$this->localWarning = t('block_not_deleted');
		}

		$this->postSliceEdit('delete', $slice_id);
		$this->index();
	}

	private function preSliceEdit($function) {
		if (!$this->article->hasTemplate()) return false;
		$user = sly_Util_User::getCurrentUser();

		if ($function == 'delete' || $function == 'edit') {
			$slice_id = sly_request('slice_id', 'rex-slice-id', '');
			if (!sly_Util_ArticleSlice::exists($slice_id)) return false;
			$module = sly_Util_ArticleSlice::getModuleNameForSlice($slice_id );
		}
		else {
			$module = sly_post('module', 'string');
		}

		if (!sly_Service_Factory::getModuleService()->exists($module)) {
			$this->warning = t('module_not_found');
			return false;
		}

		if (!sly_Service_Factory::getTemplateService()->hasModule($this->article->getTemplateName(), $module, $this->slot)) {
			$this->warning = t('no_rights_to_this_function');
			return false;
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
				$this->localWarning = t('slice_deleted_error');
			}
			else {
				$this->localWarning = t('slice_saved_error');
			}

			return false;
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

		sly_Core::cache()->flush(OOArticleSlice::CACHE_NS);
		sly_core::dispatcher()->notify('SLY_CONTENT_UPDATED', '', array('article_id' => $this->article->getId(), 'clang' => $this->article->getClang()));
	}

	private function getRequestValues(array $slicedata) {
		foreach (sly_Core::getVarTypes() as $obj) {
			$slicedata = $obj->getRequestValues($slicedata);
		}

		return $slicedata;
	}

	private function setSliceValues(array $slicedata, sly_Model_Slice $slice) {
		$sliceID = $slice->getId();

		foreach (sly_Core::getVarTypes() as $obj) {
			$obj->setSliceValues($slicedata, $sliceID);
		}
	}
}
