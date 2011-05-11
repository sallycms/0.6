<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Content_Meta extends sly_Controller_Content {

	protected function render($filename, $params = array()) {
		$filename = DIRECTORY_SEPARATOR . 'meta' . DIRECTORY_SEPARATOR . $filename;
		parent::render($filename, $params);
	}

	protected function index() {
		$this->header();
		$this->render('index.phtml');
	}

	protected function processMetaForm() {

		require_once SLY_INCLUDE_PATH . '/functions/function_rex_content.inc.php';
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
					$this->info = t('content_tostartarticle_ok');
				} else {
					$this->warning = t('content_tostartarticle_failed');
				}
			}

			//move article to other category
			if (sly_post('movearticle', 'boolean', false) && $this->canMoveArticle()) {
				$new_category_id = sly_post('category_id_new', 'rex-category-id');

				if (sly_Util_Category::hasPermissionOnCategory(sly_Util_User::getCurrentUser(), $new_category_id)) {
					if (rex_moveArticle($this->article->getId(), $new_category_id)) {
						$this->info = t('content_articlemoved');
					} else {
						$this->warning = t('content_errormovearticle');
					}
				} else {
					$this->warning = t('no_rights_to_this_function');
				}
			}
			
			$this->article = sly_Util_Article::findById($this->article->getId(), $this->article->getClang());
		} catch (Exception $e) {
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
		return ($user->isAdmin() || $user->hasPerm('moveArticle[]')) && !$this->article->isStartArticle();
	}

	protected function canMorphToStartpage() {
		$user = sly_Util_User::getCurrentUser();
		return $user->isAdmin() || $user->hasPerm('article2startpage[]');
	}

}
