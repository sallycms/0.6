<?php

class sly_Controller_Structure extends sly_Controller_Sally {

	protected $categoryId;
	protected $clangId;

	protected function init() {
		parent::init();
		$this->categoryId = rex_request('category_id', 'rex-category-id');
		$this->clangId = rex_request('clang', 'rex-clang-id', sly_Core::config()->get('START_CLANG_ID'));
		sly_Core::getLayout()->pageHeader(t('title_structure'), $this->getBreadcrumb());
		$this->render('views'.DIRECTORY_SEPARATOR.'toolbars'.DIRECTORY_SEPARATOR.'languages.phtml', array('clang' => $this->clangId, 'sprachen_add' => '&amp;category_id=' . $this->categoryId));
		echo sly_Core::dispatcher()->filter('PAGE_STRUCTURE_HEADER', '',
			array(
				'category_id' => $this->categoryId,
				'clang'       => $this->clangId
			)
		);
	}

	protected function index() {
		$service = sly_Service_Factory::getService('Category');
		$currentCategory = $service->findById($this->categoryId, $this->clangId);
		$categories = $service->find(array('re_id' => $this->categoryId, 'clang' => $this->clangId), null, 'prior ASC');
		$this->render('views'.DIRECTORY_SEPARATOR.'structure'.DIRECTORY_SEPARATOR.'category_table.phtml',
			array(
				'categories'      => $categories,
				'currentCategory' => $currentCategory,
				'advancedMode'    => sly_Util_User::getCurrentUser()->hasRight('advancedMode[]'),
				'statusTypes'     => $service->getStati()
			)
		);
	}

	/**
	 * returns the breadcrumb string
	 *
	 * @return string
	 */
	protected function getBreadcrumb() {
		$result = '';
		$cat = OOCategory::getCategoryById($this->categoryId);
		if ($cat) {
			foreach ($cat->getParentTree() as $parent) {
				if($this->canEditCategory($parent->getId())) {
					$result .= '<li> : <a href="index.php?page=structure&amp;category_id='.$parent->getId().'&amp;clang='.$this->clangId.'">'.sly_html($parent->getName()).'</a></li>';
				}
			}
		}

		$result = '
			<!-- *** OUTPUT OF CATEGORY-TOOLBAR - START *** -->
			<ul id="rex-navi-path">
				<li>' . t('path') . '</li>
				<li> : <a href="index.php?page=structure&amp;category_id=0&amp;clang=' . $this->clangId . '">Homepage</a></li>
				' . $result . '
			</ul>
			<!-- *** OUTPUT OF CATEGORY-TOOLBAR - END *** -->
			';
		return $result;
	}

	/**
	 * checks if a user can edit a category
	 *
	 * @param int $categoryId
	 * @return boolean
	 */
	protected function canEditCategory($categoryId) {
		$user = sly_Util_User::getCurrentUser();
		if ($user->isAdmin() || $user->hasRight('csw[0]')) return true;
		if($user->hasRight('editContentOnly[]')) return false;
		
		$cat = OOCategory::getCategoryById($categoryId);
		while ($cat) {
			if ($user->hasRight('csw[' . $categoryId . ']')) return true;
			$cat = $cat->getParent();
		}
		return false;
	}

	protected function canPublishCategory(sly_Model_Category $category) {
		$user = sly_Util_User::getCurrentUser();
		return $user->isAdmin() || ($user->hasRight('publishCategory[]') && $this->canEditCategory($category->getId()));
	}

	/**
	 * checks action permissions for the current user
	 *
	 * @return boolean
	 */
	protected function checkPermission() {
		$user = sly_Util_User::getCurrentUser();
		if ($this->action == 'index') {
			return!is_null($user);
		} elseif (sly_Util_String::startsWith ($this->action, 'edit') || sly_Util_String::startsWith ($this->action, 'add')) {
			return $this->canEditCategory($this->categoryId);
		}
		return false;
	}

}

?>
