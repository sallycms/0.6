<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Contentmeta extends sly_Controller_Content_Base {
	public function indexAction() {
		if ($this->header() !== true) return;

		print $this->render('content/meta/index.phtml', array(
			'article' => $this->article,
			'user'    => sly_Util_User::getCurrentUser()
		));
	}

	protected function getPageName() {
		return 'contentmeta';
	}

	public function processmetaformAction() {
		try {
			// save metadata
			if (sly_post('save_meta', 'boolean', false)) {
				$this->saveMeta();
			}

			// make article the startarticle
			elseif (sly_post('to_startarticle', 'boolean', false) && $this->canConvertToStartArticle()) {
				$this->convertToStartArticle();
			}

			// copy content to another language
			elseif (sly_post('copy_content', 'boolean', false)) {
				$this->copyContent();
			}

			// move article to other category
			elseif (sly_post('move_article', 'boolean', false)) {
				$this->moveArticle();
			}

			elseif (sly_post('copy_article', 'boolean', false)) {
				$this->copyArticle();
			}

			elseif (sly_post('move_category', 'string')) {
				$this->moveCategory();
			}
		}
		catch (Exception $e) {
			$this->warning = $e->getMessage();
		}

		$this->indexAction();
	}

	private function saveMeta() {
		$name = sly_post('meta_article_name', 'string');

		sly_Service_Factory::getArticleService()->edit($this->article->getId(), $this->article->getClang(), $name);

		// notify system
		$this->info = t('metadata_updated');

		sly_Core::dispatcher()->notify('SLY_ART_META_UPDATED', null, array(
			'id'    => $this->article->getId(),
			'clang' => $this->article->getClang()
		));

		$this->article = sly_Util_Article::findById($this->article->getId());
	}

	private function convertToStartArticle() {
		try {
			sly_Service_Factory::getArticleService()->convertToStartArticle($this->article->getId());

			$this->info    = t('article_converted_to_startarticle');
			$this->article = sly_Util_Article::findById($this->article->getId());
		}
		catch (sly_Exception $e) {
			$this->warning = t('cannot_convert_to_startarticle').': '.$e->getMessage();
		}
	}

	private function copyContent() {
		$clang_a = sly_post('clang_a', 'int');
		$clang_b = sly_post('clang_b', 'int');
		$user    = sly_Util_User::getCurrentUser();

		if (!sly_Util_Language::hasPermissionOnLanguage($user, $clang_a)) {
			$lang = sly_Util_Language::findById($clang_a);
			throw new sly_Authorisation_Exception(t('you_have_no_access_to_this_language', sly_translate($lang->getName())));
		}

		if (!sly_Util_Language::hasPermissionOnLanguage($user, $clang_b)) {
			$lang = sly_Util_Language::findById($clang_b);
			throw new sly_Authorisation_Exception(t('you_have_no_access_to_this_language', sly_translate($lang->getName())));
		}

		if ($this->canCopyContent($clang_a, $clang_b)) {
			$article_id = $this->article->getId();

			try {
				sly_Service_Factory::getArticleService()->copyContent($article_id, $article_id, $clang_a, $clang_b);
				$this->info = t('article_content_copied');
			}
			catch (sly_Exception $e) {
				$this->warning = t('cannot_copy_article_content').': '.$e->getMessage();
			}
		}
		else {
			$this->warning = t('no_rights_to_this_function');
		}
	}

	private function moveArticle() {
		$target = sly_post('category_id_new', 'int');

		if ($this->canMoveArticle()) {
			try {
				sly_Service_Factory::getArticleService()->move($this->article->getId(), $target);

				$this->info    = t('article_moved');
				$this->article = sly_Util_Article::findById($this->article->getId());
			}
			catch (sly_Exception $e) {
				$this->warning = t('cannot_move_article').': '.$e->getMessage();
			}
		}
		else {
			$this->warning = t('no_rights_to_this_function');
		}
	}

	private function copyArticle() {
		$target = sly_post('category_copy_id_new', 'int');

		if ($this->canCopyArticle()) {
			try {
				$newID         = sly_Service_Factory::getArticleService()->copy($this->article->getId(), $target);
				$this->info    = t('article_copied');
				$this->article = sly_Util_Article::findById($newID);
			}
			catch (sly_Exception $e) {
				$this->warning = t('cannot_copy_article').': '.$e->getMessage();
			}
		}
		else {
			$this->warning = t('no_rights_to_this_function');
		}
	}

	private function moveCategory() {
		$target = sly_post('category_id_new', 'int');

		if ($this->canMoveCategory()) {
			try {
				sly_Service_Factory::getCategoryService()->move($this->article->getCategoryId(), $target);

				$this->info    = t('category_moved');
				$this->article = sly_Util_Article::findById($this->article->getCategoryId());
			}
			catch (sly_Exception $e) {
				$this->warning = t('cannot_move_category').': '.$e->getMessage();
			}
		}
		else {
			$this->warning = t('no_rights_to_this_function');
		}
	}

	/**
	 * @return boolean
	 */
	protected function canMoveArticle() {
		if ($this->article->isStartArticle()) return false;
		$user = sly_Util_User::getCurrentUser();
		return $user->isAdmin() || $user->hasRight('article', 'move', $this->article->getId());
	}

	/**
	 * @return boolean
	 */
	protected function canConvertToStartArticle() {
		return $this->canDoStuff('article2startpage[]');
	}

	/**
	 * @return boolean
	 */
	protected function canCopyContent() {
		return sly_Util_Language::isMultilingual() && $this->canDoStuff('copyContent[]');
	}

	/**
	 * @return boolean
	 */
	protected function canCopyArticle() {
		return $this->canDoStuff('copyArticle[]');
	}

	/**
	 * @return boolean
	 */
	protected function canMoveCategory() {
		return $this->canDoStuff('moveCategory[]', true);
	}

	private function canDoStuff($right, $categoryOnly = false, $requireEditing = true) {
		if ($categoryOnly && !$this->article->isStartArticle()) return false;

		$user = sly_Util_User::getCurrentUser();

		if ($requireEditing && !sly_Util_Article::canEditArticle($user, $this->article->getId())) {
			return false;
		}

		return $user->isAdmin() || $user->hasRight($right);
	}
}
