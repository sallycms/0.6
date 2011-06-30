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
		//validate slot
		$templateName = $this->article->getTemplateName();
		if ($this->article->hasTemplate()
				&& !sly_Service_Factory::getTemplateService()->hasSlot($templateName, $this->slot)) {
			$this->slot = sly_Service_Factory::getTemplateService()->getFirstSlot($templateName);
		}
		sly_Util_Session::set('contentpage_slot', $this->slot);
	}

	protected function index() {
		if ($this->header() !== true)
			return;

		$articletypes = sly_Service_Factory::getArticleTypeService()->getArticleTypes();
		uasort($articletypes, 'strnatcasecmp');
		print $this->render('content/index.phtml', array(
					'article' => $this->article,
					'articletypes' => $articletypes,
					'slot' => $this->slot
				));
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
				return ($user->isAdmin() || $user->hasRight('module[' . $module . ']') || $user->hasRight('module[0]'));
			}
			return true;
		}

		return false;
	}

	protected function setArticleType() {
		$type = sly_post('article_type', 'string');
		$service = sly_Service_Factory::getArticleService();
		// change type and update database
		$service->setType($this->article, $type);

		$this->info = t('article_updated');
		$this->article = $service->findById($this->article->getId(), $this->article->getClang());
		$this->index();
	}

	protected function moveSlice() {
		$slice_id = sly_get('slice_id', 'int', null);
		$direction = sly_get('direction', 'string', null);

		// Modul und Rechte vorhanden?

		$module = rex_slice_module_exists($slice_id);

		if (!$module) {
			// MODUL IST NICHT VORHANDEN
			$this->warning = t('module_not_found');
		} else {
			// RECHTE AM MODUL ?
			if ($user->isAdmin() || $user->hasRight('module[' . $module . ']') || $user->hasRight('module[0]')) {
				list($success, $message) = rex_moveSlice($slice_id, $clang, $direction);

				if ($success) {
					$this->localInfo = $message;
				} else {
					$this->localWarning = $message;
				}
			} else {
				$this->warning = t('no_rights_to_this_function');
			}
		}
	}

	protected function addArticleSlice() {
		$module = sly_post('module', 'string');
		$user = sly_Util_User::getCurrentUser();
		$slicedata = $this->preSliceEdit('add');

		if ($slicedata !== false) {

			$sql = sly_DB_Persistence::getInstance();
			$sliceService = sly_Service_Factory::getSliceService();

			//create the slice
			$slice = $sliceService->create(array('module' => $module));
			foreach (sly_Core::getVarTypes() as $obj) {
				$obj->setACValues($slice->getId(), $slicedata, true, false);
			}

			//create the articleslice
			$values = array(
				'prior' => sly_post('prior', 'int'),
				'article_id' => $this->article->getId(),
				'clang' => $this->article->getClang(),
				'slot' => $this->slot,
				'slice_id' => $slice->getId(),
				'revision' => 0,
				'createdate' => time(),
				'createuser' => $user->getLogin(),
				'updatedate' => time(),
				'updateuser' => $user->getLogin()
			);

			$sql->insert('article_slice', $values);
			$id = $sql->lastId();
			$pre = sly_Core::config()->get('DATABASE/TABLE_PREFIX');

			$sql->query('UPDATE ' . $pre . 'article_slice SET prior = prior + 1 ' .
					'WHERE article_id = ' . $this->article->getId() . ' AND clang = ' . $this->article->getClang() . ' AND slot = "' . $this->slot . '" ' .
					'AND prior >= ' . $values['prior'] . ' AND id <> ' . $id
			);

			$this->localInfo = t('block_added');

			$this->postSliceEdit('add');
		}
		$this->index();
	}

	protected function editArticleSlice() {
		
	}

	protected function deleteArticleSlice() {
		$ok = false;
		if ($this->preSliceEdit('delete') !== false) {
			$slice_id = sly_request('slice_id', 'rex-slice-id', '');
			$ok = sly_Util_ArticleSlice::deleteById($slice_id);
		}
		
		if($ok) {
			$this->info = t('block_deleted');
		}else {
			$this->warning = t('block_not_deleted');
		}
		$this->postSliceEdit('delete');
		$this->index();
	}

	private function preSliceEdit($function) {
		if(!$this->article->hasTemplate()) return false;
		$user = sly_Util_User::getCurrentUser();
		if ($function == 'delete' || $function == 'edit') {
			$slice_id = sly_request('slice_id', 'rex-slice-id', '');
			if(!sly_Util_ArticleSlice::exists($slice_id)) return false;
			$module = sly_Util_ArticleSlice::getModuleNameForSlice($slice_id );
		} else {
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
			foreach (sly_Core::getVarTypes() as $idx => $obj) {
				$slicedata = $obj->getACRequestValues($slicedata);
			}
		}

		// ----- PRE SAVE EVENT [ADD/EDIT/DELETE]
		$slicedata = sly_Core::dispatcher()->filter('SLY_SLICE_PRESAVE_' . strtoupper($function), $slicedata);

		if (!$slicedata['SAVE']) {
			// DONT SAVE/UPDATE SLICE
			if (!empty($slicedata['MESSAGES'])) {
				$this->localWarning = join('<br />', $slicedata['MESSAGES']);
			} elseif ($this->action == 'deleteArticleSlice') {
				$this->localWarning = t('slice_deleted_error');
			} else {
				$this->localWarning = t('slice_saved_error');
			}
			return false;
		}

		return $slicedata;
	}

	private function postSliceEdit($function) {
		$user = sly_Util_User::getCurrentUser();
		sly_Service_Factory::getArticleService()->touch($this->article, $user);

		$messages = sly_Core::dispatcher()->filter('SLY_SLICE_POSTSAVE_' . strtoupper($function), array());

		if (!empty($messages)) {
			if($function == 'delete')
				$this->info .= join('<br />', $messages);
			else
				$this->localInfo .= join('<br />', $messages);
		}
		sly_Core::cache()->flush(OOArticleSlice::CACHE_NS);
		sly_core::dispatcher()->notify('SLY_CONTENT_UPDATED', '', array('article_id' => $this->article->getId(), 'clang' => $this->article->getClang()));
	}

}
