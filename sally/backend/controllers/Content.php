<?php

/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Content extends sly_Controller_Sally {

	protected $article;
	//protected $clangId;
	protected $slot;

	protected function init() {
		$clang = sly_Core::getCurrentClang();
		$this->article = sly_Util_Article::findById(sly_request('article_id', 'rex-article-id'), $clang);
		$this->slot = sly_request('slot', 'string');
		if (is_null($this->article)) {
			sly_Core::getLayout()->pageHeader(t('content'));
			throw new sly_Exception(t('no_article_available'));
		}
	}

	protected function header() {
		sly_Core::getLayout()->pageHeader(t('content'), $this->getBreadcrumb());

		$art = $this->article;

		$this->renderLanguageBar('&amp;article_id=' . $art->getId());
		// extend menu
		print sly_Core::dispatcher()->filter('PAGE_CONTENT_HEADER', '', array(
					'article_id' => $art->getId(),
					'clang' => $art->getClang(),
					'category_id' => $art->getCategoryId()
				));
	}

	protected function index() {
		if (!$this->article)
			return;
		$this->header();
		$this->render('index.phtml');
	}

	protected function render($filename, $params = array()) {
		$filename = 'views' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . $filename;
		parent::render($filename, $params);
	}

	protected function checkPermission() {
		$user = sly_Util_User::getCurrentUser();
		if (is_null($user))
			return false;

		$articleId = sly_request('article_id', 'int');
		$article = sly_Util_Article::findById($articleId);

		// all users are allowed to see the error message in init()
		if (is_null($article))
			return true;

		return sly_Util_Category::hasPermissionOnCategory($user, $article->getCategoryId());
	}

	protected function renderLanguageBar($add) {
		parent::render('views/toolbars/languages.phtml', array(
			'clang' => $this->article->getClang(),
			'sprachen_add' => $add
		));
	}

	/**
	 * returns the breadcrumb string
	 *
	 * @return string
	 */
	protected function getBreadcrumb() {
		$art = $this->article;
		$user = sly_Util_User::getCurrentUser();
		$cat = sly_Util_Category::findById($art->getCategoryId());
		$result = '<ul class="sly-navi-path">
				<li>' . t('path') . '</li>
				<li> : <a href="index.php?page=structure&amp;category_id=0&amp;clang=' . $art->getClang() . '">Homepage</a></li>';


		if ($cat) {
			foreach ($cat->getParentTree() as $parent) {
				if (sly_Util_Category::hasPermissionOnCategory($user, $parent->getId())) {
					$result .= '<li> : <a href="index.php?page=structure&amp;category_id=' . $parent->getId() . '&amp;clang=' . $art->getClang() . '">' . sly_html($parent->getName()) . '</a></li>';
				}
			}
		}

		$result .= '</ul><p>';
		$result .= $art->isStartArticle() ? t('start_article') . ': ' : t('article') . ': ';
		$result .= '<a href="index.php?page=content&amp;article_id=' . $art->getId() . '&amp;mode=edit&amp;clang=' . $art->getClang() . '">' . str_replace(' ', '&nbsp;', sly_html($art->getName())) . '</a>';
		$result .= '</p>';

		return $result;
	}

}
