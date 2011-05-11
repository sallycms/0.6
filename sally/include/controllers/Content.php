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
	protected $clangId;

	protected function init() {
		$clang = sly_Core::getCurrentClang();
		$this->article = sly_Util_Article::findById(sly_request('article_id', 'rex-article-id'), $clang);

		if (is_null($this->article)) {
			sly_Core::getLayout()->pageHeader(t('content'));
			throw new sly_Exception(t('no_article_available'));
		}
	}
	
	protected function header() {
		sly_Core::getLayout()->pageHeader(t('content'), $this->getBreadcrumb());

		parent::render('views/toolbars/languages.phtml', array('clang' => $this->article->getClang(),
			'sprachen_add' => '&amp;mode=edit&amp;category_id=' . $this->article->getCategoryId() . '&amp;article_id=' . $this->article->getId())
		);

		// extend menu
		print sly_Core::dispatcher()->filter('PAGE_CONTENT_HEADER', '', array(
					'article_id' => $this->article->getId(),
					'clang' => $this->article->getClang(),
					'category_id' => $this->article->getCategoryId()
				));
		
	}

	protected function index() {
		$this->header();
		$this->render('index.phtml', array('mode' => 'edit'));
	}

	protected function render($filename, $params = array()) {
		$filename = 'views' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . $filename;
		parent::render($filename, $params);
	}

	protected function checkPermission() {
		$user = sly_Util_User::getCurrentUser();
		if (is_null($user))	return false;

		$articleId = sly_request('article_id', 'int');
		$article = sly_Util_Article::findById($articleId);
		if (is_null($article)) return true;

		return sly_Util_Category::hasPermissionOnCategory($user, $article->getCategoryId());
	}

	/**
	 * returns the breadcrumb string
	 *
	 * @return string
	 */
	protected function getBreadcrumb() {
		$user = sly_Util_User::getCurrentUser();
		$cat = sly_Util_Category::findById($this->article->getCategoryId());
		$result = '<ul class="sly-navi-path">
				<li>' . t('path') . '</li>
				<li> : <a href="index.php?page=structure&amp;category_id=0&amp;clang=' . $this->article->getClang() . '">Homepage</a></li>';


		if ($cat) {
			foreach ($cat->getParentTree() as $parent) {
				if (sly_Util_Category::hasPermissionOnCategory($user, $parent->getId())) {
					$result .= '<li> : <a href="index.php?page=structure&amp;category_id=' . $parent->getId() . '&amp;clang=' . $this->article->getClang() . '">' . sly_html($parent->getName()) . '</a></li>';
				}
			}
		}
		$result .= '</ul><p>';
		$result .= $this->article->isStartArticle() ? t('start_article') . ': ' : t('article') . ': ';
		$result .= '<a href="index.php?page=content&amp;article_id=' . $this->article->getId() . '&amp;mode=edit&amp;clang=' . $this->article->getClang() . '">' . str_replace(' ', '&nbsp;', sly_html($this->article->getName())) . '</a>';
		$result .= '</p>';

		return $result;
	}

}
