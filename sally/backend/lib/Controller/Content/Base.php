<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_Controller_Content_Base extends sly_Controller_Backend implements sly_Controller_Interface {
	protected $article;
	protected $info;
	protected $warning;

	protected function init() {
		$clang = sly_Core::getCurrentClang();
		$id    = sly_request('article_id', 'int');

		$this->article = sly_Util_Article::findById($id, $clang);

		if (is_null($this->article)) {
			sly_Core::getLayout()->pageHeader(t('content'));
			throw new sly_Exception(t('no_articles_available'));
		}

		sly_Core::setCurrentArticleId($id);
	}

	protected function renderLanguageBar() {
		print $this->render('toolbars/languages.phtml', array('curClang' => $this->article->getClang(), 'params' => array(
			'page'       => $this->getPageName(),
			'article_id' => $this->article->getId()
		)));
	}

	/**
	 * returns the breadcrumb string
	 *
	 * @return string
	 */
	protected function getBreadcrumb() {
		$art    = $this->article;
		$user   = sly_Util_User::getCurrentUser();
		$cat    = $art->getCategory();
		$result = '<ul class="sly-navi-path">
			<li>'.t('path').'</li>
			<li> : <a href="index.php?page=structure&amp;category_id=0&amp;clang='.$art->getClang().'">'.t('home').'</a></li>';


		if ($cat) {
			foreach ($cat->getParentTree() as $parent) {
				if (sly_Util_Category::canReadCategory($user, $parent->getId())) {
					$result .= '<li> : <a href="index.php?page=structure&amp;category_id='.$parent->getId().'&amp;clang='.$art->getClang().'">'.sly_html($parent->getName()).'</a></li>';
				}
			}
		}

		$result .= '<li> | '.($art->isStartArticle() ? t('startarticle') : t('article')).'</li>';
		$result .= '<li> : <a href="index.php?page='.$this->getPageName().'&amp;article_id='.$art->getId().'&amp;clang='.$art->getClang().'">'.str_replace(' ', '&nbsp;', sly_html($art->getName())).'</a></li>';
		$result .= '</ul>';

		return $result;
	}

	protected function header() {
		if ($this->article === null) {
			sly_Core::getLayout()->pageHeader(t('content'));
			print sly_Helper_Message::warn(t('no_articles_available'));
			return false;
		}
		else {
			sly_Core::getLayout()->pageHeader(t('content'), $this->getBreadcrumb());

			$art = $this->article;

			$this->renderLanguageBar();

			// extend menu
			print sly_Core::dispatcher()->filter('PAGE_CONTENT_HEADER', '', array(
				'article_id'  => $art->getId(),
				'clang'       => $art->getClang(),
				'category_id' => $art->getCategoryId()
			));

			return true;
		}
	}

	public function checkPermission($action) {
		$user = sly_Util_User::getCurrentUser();
		if (is_null($user)) return false;

		$articleId = sly_request('article_id', 'int');
		$article   = sly_Util_Article::findById($articleId);

		// all users are allowed to see the error message in init()
		if (is_null($article)) return true;

		$clang   = sly_Core::getCurrentClang();
		$clangOk = sly_Util_Language::hasPermissionOnLanguage($user, $clang);
		if (!$clangOk) return false;

		return sly_Util_Article::canEditContent($user, $article->getId());
	}
}
