<?php

class sly_Controller_Content extends sly_Controller_Sally {

	protected function init() {
	
		$category_id = sly_request('category_id', 'rex-category-id');
		$article_id = sly_request('article_id', 'rex-article-id');
		$slice_id = sly_request('slice_id', 'rex-slice-id', '');
		$function = sly_request('function', 'string');
		$slot = sly_request('slot', 'string');
		$clang       = sly_Core::getCurrentClang();
		
		parent::render('views/toolbars/languages.phtml', array('clang' => $clang,
			'sprachen_add' => '&amp;mode=edit&amp;category_id=' . $category_id . '&amp;article_id=' . $article_id)
		);

		// extend menu
		print sly_Core::dispatcher()->filter('PAGE_CONTENT_HEADER', '', array(
					'article_id' => $article_id,
					'clang' => $clang,
					'function' => $function,
					'slice_id' => $slice_id,
					'slot' => $slot,
					'category_id' => $category_id,
				));
	}

	protected function index() {
		$this->render('index.phtml', array('mode' => 'edit'));
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
		if (is_null($article))
			return false;

		$categoryId = $article->getCategoryId();

		return sly_Util_Category::hasPermissionOnCategory($user, $categoryId);
	}

}
