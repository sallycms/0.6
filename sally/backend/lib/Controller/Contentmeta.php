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
		$this->init();

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
		$this->init();

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
		$srcClang  = sly_post('clang_a', 'int', 0);
		$dstClangs = array_unique(sly_postArray('clang_b', 'int'));
		$user      = sly_Util_User::getCurrentUser();
		$infos     = array();
		$errs      = array();
		$articleID = $this->article->getId();

		if (empty($dstClangs)) {
			throw new sly_Authorisation_Exception(t('no_language_selected'));
		}

		if (!sly_Util_Language::hasPermissionOnLanguage($user, $srcClang)) {
			$lang = sly_Util_Language::findById($srcClang);
			throw new sly_Authorisation_Exception(t('you_have_no_access_to_this_language', sly_translate($lang->getName())));
		}

		foreach ($dstClangs as $targetClang) {
			if (!sly_Util_Language::hasPermissionOnLanguage($user, $targetClang)) {
				$lang = sly_Util_Language::findById($targetClang);
				$errs[$targetClang] = t('you_have_no_access_to_this_language', sly_translate($lang->getName()));
				continue;
			}

			if (!$this->canCopyContent($srcClang, $targetClang)) {
				$errs[$targetClang] = t('no_rights_to_this_function');
				continue;
			}

			try {
				sly_Service_Factory::getArticleService()->copyContent($articleID, $articleID, $srcClang, $targetClang);
				$infos[$targetClang] = t('article_content_copied');
			}
			catch (sly_Exception $e) {
				$errs[$targetClang] = t('cannot_copy_article_content').': '.$e->getMessage();
			}
		}

		// only prepend language names if there were more than one language
		if (count($dstClangs) > 1) {
			foreach ($infos as $clang => $msg) {
				$lang = sly_Util_Language::findById($clang);
				$infos[$clang] = sly_translate($lang->getName()).': '.$msg;
			}

			foreach ($errs as $clang => $msg) {
				$lang = sly_Util_Language::findById($clang);
				$errs[$clang] = sly_translate($lang->getName()).': '.$msg;
			}
		}

		$this->info    = implode("<br />\n", $infos);
		$this->warning = implode("<br />\n", $errs);
	}

	private function moveArticle() {
		$target = sly_post('category_id_new', 'int', 0);

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
		$target = sly_post('category_copy_id_new', 'int', 0);

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
		$target = sly_post('category_id_new', 'int', 0);

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
		return $user->isAdmin() || $user->hasRight('article', 'move', 0) || $user->hasRight('article', 'move', $this->article->getId());
	}

	/**
	 * @return boolean
	 */
	protected function canConvertToStartArticle() {
		return $this->canDoStuff('article2startpage');
	}

	/**
	 * @return boolean
	 */
	protected function canCopyContent() {
		return sly_Util_Language::isMultilingual() && $this->canDoStuff('copyContent');
	}

	/**
	 * @return boolean
	 */
	protected function canCopyArticle() {
		return $this->canDoStuff('copyArticle');
	}

	/**
	 * @return boolean
	 */
	protected function canMoveCategory() {
		return $this->canDoStuff('moveCategory', true);
	}

	private function canDoStuff($right, $categoryOnly = false, $requireEditing = true) {
		if ($categoryOnly && !$this->article->isStartArticle()) return false;

		$user = sly_Util_User::getCurrentUser();

		if ($requireEditing && !sly_Util_Article::canEditArticle($user, $this->article->getId())) {
			return false;
		}

		return $user->isAdmin() || $user->hasRight('transitional', $right);
	}
}
