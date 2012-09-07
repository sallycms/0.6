<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Structure extends sly_Controller_Backend implements sly_Controller_Interface {
	protected $categoryId;
	protected $clangId;
	protected $info;
	protected $warning;
	protected $artService;
	protected $catService;
	protected $renderAddCategory  = false;
	protected $renderEditCategory = false;
	protected $renderAddArticle   = false;
	protected $renderEditArticle  = false;
	protected $init = false;

	protected static $viewPath;

	public function __construct($dontRedirect = false) {
		parent::__construct();

		if (!$dontRedirect) {
			$user    = sly_Util_User::getCurrentUser();
			$allowed = $user->getAllowedCLangs();

			if (!empty($user) && !empty($allowed) && !isset($_REQUEST['clang']) && !in_array(sly_Core::getDefaultClangId(), $allowed)) {
				sly_Util_HTTP::redirect(sly_Util_HTTP::getBaseUrl(true).'/backend/index.php?page=structure&clang='.reset($allowed), '&', 302);
			}
		}
	}

	protected function init() {
		if ($this->init) return true;
		$this->init = true;

		self::$viewPath = 'structure/';

		$this->categoryId = sly_request('category_id', 'int', 0);
		$this->clangId    = sly_Core::getCurrentClang();
		$this->artService = sly_Service_Factory::getArticleService();
		$this->catService = sly_Service_Factory::getCategoryService();

		if (count(sly_Util_Language::findAll()) === 0) {
			sly_Core::getLayout()->pageHeader(t('structure'));
			print sly_Helper_Message::info(t('no_languages_yet'));
			return false;
		}

		sly_Core::getLayout()->pageHeader(t('structure'), $this->getBreadcrumb());

		print $this->render('toolbars/languages.phtml', array(
			'curClang' => $this->clangId,
			'params'   => array('page' => 'structure', 'category_id' => $this->categoryId)
		));

		print sly_Core::dispatcher()->filter('PAGE_STRUCTURE_HEADER', '', array(
			'category_id' => $this->categoryId,
			'clang'       => $this->clangId
		));

		return true;
	}

	public function indexAction() {
		$this->viewAction();
	}

	public function viewAction() {
		if (!$this->init()) return;

		$currentCategory = $this->catService->findById($this->categoryId, $this->clangId);
		$categories      = $this->catService->findByParentId($this->categoryId, false, $this->clangId);
		$articles        = $this->artService->findArticlesByCategory($this->categoryId, false, $this->clangId);
		$maxPosition     = $this->artService->getMaxPosition($this->categoryId);
		$maxCatPosition  = $this->catService->getMaxPosition($this->categoryId);

		if (!empty($this->info))    print sly_Helper_Message::info($this->info);
		if (!empty($this->warning)) print sly_Helper_Message::warn($this->warning);

		print $this->render(self::$viewPath.'category_table.phtml', array(
			'categories'      => $categories,
			'currentCategory' => $currentCategory,
			'statusTypes'     => $this->catService->getStates(),
			'maxPosition'     => $maxPosition,
			'maxCatPosition'  => $maxCatPosition
		));

		print $this->render(self::$viewPath.'article_table.phtml', array(
			'articles'       => $articles,
			'statusTypes'    => $this->artService->getStates(),
			'canAdd'         => $this->canEditCategory($this->categoryId),
			'canEdit'        => $this->canEditCategory($this->categoryId),
			'maxPosition'    => $maxPosition,
			'maxCatPosition' => $maxCatPosition
		));
	}

	public function editstatuscategoryAction() {
		if (!$this->init()) return;

		$editId = sly_get('edit_id', 'int', 0);

		if ($editId) {
			try {
				$this->catService->changeStatus($editId, $this->clangId);
				$this->info = t('category_status_updated');
			}
			catch (sly_Exception $e) {
				$this->warning = $e->getMessage();
			}
		}
		else {
			$this->warning = t('category_not_found');
		}

 		$this->viewAction();
	}

	public function editstatusarticleAction() {
		if (!$this->init()) return;

		$editId = sly_get('edit_id', 'int', 0);

		if ($editId) {
			try {
				$this->artService->changeStatus($editId, $this->clangId);
				$this->info = t('article_status_updated');
			}
			catch (sly_Exception $e) {
				$this->warning = $e->getMessage();
			}
		}
		else {
			$this->warning = t('article_not_found');
		}

 		$this->viewAction();
	}

	public function deletecategoryAction() {
		if (!$this->init()) return;

		$editId = sly_get('edit_id', 'int', 0);

		if ($editId) {
			try {
				$this->catService->delete($editId);
				$this->info = t('category_deleted');
			}
			catch (sly_Exception $e) {
				$this->warning = $e->getMessage();
			}
		}
		else {
			$this->warning = t('category_not_found');
		}

 		$this->viewAction();
	}

	public function deletearticleAction() {
		if (!$this->init()) return;

		$editId = sly_get('edit_id', 'int', 0);

		if ($editId) {
			try {
				$this->artService->delete($editId);
				$this->info = t('article_deleted');
			}
			catch (sly_Exception $e) {
				$this->warning = $e->getMessage();
			}
		}
		else {
			$this->warning = t('article_not_found');
		}

 		$this->viewAction();
	}

	public function addcategoryAction() {
		if (!$this->init()) return;

		if (sly_post('do_add_category', 'boolean')) {
			$name     = sly_post('category_name',     'string');
			$position = sly_post('category_position', 'int', 0);

			try {
				$this->catService->add($this->categoryId, $name, 0, $position);
				$this->info = t('category_added');
			}
			catch (sly_Exception $e) {
				$this->warning           = $e->getMessage();
				$this->renderAddCategory = true;
			}
		}
		else {
			$this->renderAddCategory = true;
		}

		$this->viewAction();
	}

	public function addarticleAction() {
		if (!$this->init()) return;

		if (sly_post('do_add_article', 'boolean')) {
			$name     = sly_post('article_name',     'string');
			$position = sly_post('article_position', 'int', 0);

			try {
				$this->artService->add($this->categoryId, $name, 0, $position);
				$this->info = t('article_added');
			}
			catch (sly_Exception $e) {
				$this->warning          = $e->getMessage();
				$this->renderAddArticle = true;
			}
		}
		else {
			$this->renderAddArticle = true;
		}

		$this->viewAction();
	}

	public function editcategoryAction() {
		if (!$this->init()) return;

		$editId = sly_request('edit_id', 'int');

		if (sly_post('do_edit_category', 'boolean')) {
			$name     = sly_post('category_name',     'string');
			$position = sly_post('category_position', 'int');

			try {
				$this->catService->edit($editId, $this->clangId, $name, $position);
				$this->info = t('category_updated');
			}
			catch (sly_Exception $e) {
				$this->warning            = $e->getMessage();
				$this->renderEditCategory = $editId;
			}
		}
		else {
			$this->renderEditCategory = $editId;
		}

		$this->viewAction();
	}

	public function editarticleAction() {
		if (!$this->init()) return;

		$editId = sly_request('edit_id', 'int');

		if (sly_post('do_edit_article', 'boolean')) {
			$name     = sly_post('article_name',     'string');
			$position = sly_post('article_position', 'integer');

			try {
				$this->artService->edit($editId, $this->clangId, $name, $position);
				$this->info = t('article_updated');
			}
			catch (sly_Exception $e) {
				$this->warning           = $e->getMessage();
				$this->renderEditArticle = $editId;
			}
		}
		else {
			$this->renderEditArticle = $editId;
		}

		$this->viewAction();
	}

	/**
	 * returns the breadcrumb string
	 *
	 * @return string
	 */
	protected function getBreadcrumb() {
		$result = '';
		$cat    = $this->catService->findById($this->categoryId);

		if ($cat) {
			foreach ($cat->getParentTree() as $parent) {
				if ($this->canViewCategory($parent->getId())) {
					$result .= '<li> : <a href="index.php?page=structure&amp;category_id='.$parent->getId().'&amp;clang='.$this->clangId.'">'.sly_html($parent->getName()).'</a></li>';
				}
			}
		}

		$result = '
			<ul class="sly-navi-path">
				<li>'.t('path').'</li>
				<li> : <a href="index.php?page=structure&amp;category_id=0&amp;clang='.$this->clangId.'">'.t('home').'</a></li>
				'.$result.'
			</ul>
			';

		return $result;
	}

	/**
	 * checks if a user can edit a category
	 *
	 * @param  int $categoryId
	 * @return boolean
	 */
	protected function canEditCategory($categoryId) {
		$user = sly_Util_User::getCurrentUser();
		return sly_Util_Article::canEditArticle($user, $categoryId);
	}

	/**
	 * checks if a user can change a category's status
	 *
	 * @param  int $categoryId
	 * @return boolean
	 */
	protected function canPublishCategory($categoryId) {
		$user = sly_Util_User::getCurrentUser();
		return $user->isAdmin() || $user->hasRight('article', 'publish', 0) || $user->hasRight('article', 'publish', $categoryId);
	}

	/**
	 * checks if a user can view a category
	 *
	 * @param  int $categoryId
	 * @return boolean
	 */
	protected function canViewCategory($categoryId) {
		$user = sly_Util_User::getCurrentUser();
		return sly_Util_Category::canReadCategory($user, $categoryId);
	}

	/**
	 * checks if a user can edit an article
	 *
	 * @param  int $articleId
	 * @return boolean
	 */
	protected function canEditContent($articleId) {
		$user = sly_Util_User::getCurrentUser();
		return sly_Util_Article::canEditContent($user, $articleId);
	}

	/**
	 * checks action permissions for the current user
	 *
	 * @return boolean
	 */
	public function checkPermission($action) {
		$categoryId = sly_request('category_id', 'int');
		$editId     = sly_request('edit_id', 'int');
		$clang      = sly_Core::getCurrentClang();
		$user       = sly_Util_User::getCurrentUser();

		if ($user === null || !sly_Util_Language::hasPermissionOnLanguage($user, $clang)) {
			return false;
		}

		if ($action === 'index') {
			return $this->canViewCategory($categoryId);
		}

		if (sly_Util_String::startsWith($action, 'editstatus')) {
			if ($action === 'editstatuscategory') {
				return $this->canPublishCategory($editId);
			}
			else {
				return $this->canPublishCategory($categoryId);
			}
		}
		elseif (sly_Util_String::startsWith($action, 'edit') || sly_Util_String::startsWith($action, 'delete')) {
			return $this->canEditCategory($editId);
		}
		elseif (sly_Util_String::startsWith($action, 'add')) {
			return $this->canEditCategory($categoryId);
		}

		return true;
	}
}
