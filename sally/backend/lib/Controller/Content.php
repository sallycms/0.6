<?php

/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Content extends sly_Controller_Backend {

	protected $article;
	protected $slot;
	protected $info;
	protected $warning;

	protected function init() {
		$clang = sly_Core::getCurrentClang();
		$this->article = sly_Util_Article::findById(sly_request('article_id', 'rex-article-id'), $clang);
		$this->slot = sly_request('slot', 'string', sly_Util_Session::get('contentpage_slot', ''));
		sly_Util_Session::set('contentpage_slot', $this->slot);
		if (is_null($this->article)) {
			sly_Core::getLayout()->pageHeader(t('content'));
			throw new sly_Exception(t('no_article_available'));
		}
	}

	protected function header() {
		if (is_null($this->article)) {
			sly_Core::getLayout()->pageHeader(t('content'));
			print rex_warning(t('no_article_available'));
		} else {
			sly_Core::getLayout()->pageHeader(t('content'), $this->getBreadcrumb());

			$art = $this->article;

			$this->renderLanguageBar();
			// extend menu
			print sly_Core::dispatcher()->filter('PAGE_CONTENT_HEADER', '', array(
						'article_id' => $art->getId(),
						'clang' => $art->getClang(),
						'category_id' => $art->getCategoryId()
					));
		}
	}

	protected function index() {
		$this->header();
		if (is_null($this->article)) return;
		print $this->render('content/index.phtml', array('mode' => 'edit'));
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

	protected function renderLanguageBar() {
		print $this->render('toolbars/languages.phtml', array(
					'clang' => $this->article->getClang(),
					'sprachen_add' => '&amp;article_id=' . $this->article->getId()
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
		$cat = $art->getCategory();
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

		$subpage = self::getSubpageParam();
		$result .= '</ul><p>';
		$result .= $art->isStartArticle() ? t('start_article') . ': ' : t('article') . ': ';
		$result .= '<a href="index.php?page=content' . (!empty($subpage) ? '&amp;subpage=' . $subpage : '') . '&amp;article_id=' . $art->getId() . '&amp;clang=' . $art->getClang() . '">' . str_replace(' ', '&nbsp;', sly_html($art->getName())) . '</a>';
		$result .= '</p>';

		return $result;
	}

	protected function setArticleType() {
		$type = sly_post('article_type', 'string');
		$service = sly_Service_Factory::getArticleService();
		// change type and update database
		$service->setType($this->article, $type);

		$this->info= t('article_updated');
		$this->article = $service->findById($this->article->getId(), $this->article->getClang());
		$this->index();
	}
	
	protected function moveSlice() {
		
	}

}
