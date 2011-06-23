<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Contentmeta extends sly_Controller_Content {

	protected function header() {
		sly_Core::getLayout()->pageHeader(t('content'), $this->getBreadcrumb());

		$art = $this->article;

		$this->renderLanguageBar('&amp;subpage=meta&amp;article_id='.$art->getId());
		// extend menu
		print sly_Core::dispatcher()->filter('PAGE_CONTENT_HEADER', '', array(
			'article_id'  => $art->getId(),
			'clang'       => $art->getClang(),
			'category_id' => $art->getCategoryId()
		));
	}

	protected function index() {
		if(!$this->article) return;
		$this->header();
		print $this->render('content/meta/index.phtml', array('article' => $this->article));
	}

	protected function processMetaForm() {

		require_once SLY_COREFOLDER . '/functions/function_rex_content.inc.php';
		try {
			//save metadata
			if (sly_post('savemeta', 'boolean', false)) {
				$name = sly_post('meta_article_name', 'string');

				sly_Service_Factory::getArticleService()->edit($this->article->getId(), $this->article->getClang(), $name);

				// notify system
				$this->info = t('metadata_updated');
				sly_Core::dispatcher()->notify('SLY_ART_META_UPDATED', null, array(
					'id' => $this->article->getId(),
					'clang' => $this->article->getClang()
				));
			}

			//make article the startarticle
			if (sly_post('article2startpage', 'boolean', false) && $this->canMorphToStartpage()) {
				if (rex_article2startpage($this->article->getId())) {
					$this->info    = t('content_tostartarticle_ok');
					$this->article = sly_Util_Article::findById($this->article->getId());
				}
				else {
					$this->warning = t('content_tostartarticle_failed');
				}
			}

			//copy content to another language
			if (sly_post('copycontent', 'boolean', false)) {
				if ($this->canCopyContent()) {
					$clang_a = sly_post('clang_a', 'rex-clang-id');
					$clang_b = sly_post('clang_b', 'rex-clang-id');

					if (rex_copyContent($article_id, $article_id, $clang_a, $clang_b)) {
						$this->info = t('content_contentcopy');
					}
					else {
						$this->warning = t('content_errorcopy');
					}
				}
			}

			//move article to other category
			if (sly_post('movearticle', 'boolean', false)) {
				$new_category_id = sly_post('category_id_new', 'rex-category-id');

				if (sly_Util_Category::hasPermissionOnCategory(sly_Util_User::getCurrentUser(), $new_category_id) && $this->canMoveArticle()) {
					if (rex_moveArticle($this->article->getId(), $new_category_id)) {
						$this->info    = t('content_articlemoved');
						$this->article = sly_Util_Article::findById($this->article->getId());
					}
					else {
						$this->warning = t('content_errormovearticle');
					}
				}
				else {
					$this->warning = t('no_rights_to_this_function');
				}
			}

			if (sly_post('copyarticle', 'boolean', false)) {
				$new_category_id = sly_post('category_copy_id_new', 'rex-category-id');

				if ($this->canCopyArticle()) {
					if (($new_id = rex_copyArticle($this->article->getId(), $new_category_id)) !== false) {
						$this->info    = t('content_articlecopied');
						$this->article = sly_Util_Article::findById($new_id);
					}
					else {
						$this->warning = t('content_errorcopyarticle');
					}
				}
				else {
					$this->warning = t('no_rights_to_this_function');
				}
			}

			if (sly_post('movecategory', 'string')) {
				$new_category_id = sly_post('category_id_new', 'rex-category-id');
				if (sly_Util_Category::hasPermissionOnCategory(sly_Util_User::getCurrentUser(), $new_category_id) && $this->canMoveCategory()) {
					if (rex_moveCategory($this->article->getCategoryId(), $new_category_id)) {
						$this->info    = t('category_moved');
						$this->article = sly_Util_Article::findById($this->article->getCategoryId());
					}
					else {
						$this->warning = t('content_error_movecategory');
					}
				}
				else {
					$this->warning = t('no_rights_to_this_function');
				}
			}
		}
		catch (Exception $e) {
			$this->warning = $e->getMessage();
		}

		$this->index();
	}

	/**
	 *
	 * @param int $destinationCategory id of destination category
	 */
	protected function canMoveArticle() {
		$user = sly_Util_User::getCurrentUser();
		return ($user->isAdmin() || $user->hasRight('moveArticle[]')) && !$this->article->isStartArticle();
	}

	protected function canMorphToStartpage() {
		$user = sly_Util_User::getCurrentUser();
		return $user->isAdmin() || $user->hasRight('article2startpage[]');
	}

	protected function canCopyContent() {
		$user = sly_Util_User::getCurrentUser();
		return $user->isAdmin() || $user->hasRight('copyContent[]') && sly_Util_Language::isMultilingual();
	}

	protected function canCopyArticle() {
		$user = sly_Util_User::getCurrentUser();
		return $user->isAdmin() || $user->hasRight('copyArticle[]');
	}

	protected function canMoveCategory() {
		$user = sly_Util_User::getCurrentUser();
		return $this->article->isStartArticle() && $user->isAdmin() || $user->hasRight('moveCategory[]');
	}
}
