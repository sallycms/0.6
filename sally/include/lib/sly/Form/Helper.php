<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup form
 */
abstract class sly_Form_Helper {
	private static $select;
	private static $user;
	private static $type;
	private static $hideOffline;
	private static $clang;

	public static function getMediaCategorySelect($name, $root = null, $user = null, $id = null) {
		global $I18N;

		$init   = array(0 => $I18N->msg('pool_kats_no'));
		$select = new sly_Form_Select_DropDown($name, '', -1, $init, $id);

		if ($root === null) {
			$rootCats = OOMediaCategory::getRootCategories();
		}
		else {
			$service  = sly_Service_Factory::getService('Media_Category');
			$rootCat  = $service->findById((int) $root);
			$rootCats = $rootCat ? array($rootCat) : null;
		}

		self::$select      = $select;
		self::$user        = $user;
		self::$type        = 'media';
		self::$hideOffline = false; // media cats cannot be offline
		self::$clang       = false; // media cats are monolingual

		if ($rootCats) {
			foreach ($rootCats as $rootCat) {
				self::walkTree($rootCat, 0);
			}
		}

		return $select;
	}

	public function getCategorySelect($name, $hideOffline = true, $clang = false, $root = null, $user = null, $id = null) {
		$select = new sly_Form_Select_DropDown($name, '', -1, array(0 => 'Homepage'), $id);

		if ($root === null) {
			$rootCats = OOCategory::getRootCategories($hideOffline, $clang);
		}
		else {
			$rootCat  = OOCategory::getCategoryById((int) $root, $clang);
			$rootCats = $rootCat ? array($rootCat) : null;
		}

		self::$select      = $select;
		self::$user        = $user;
		self::$type        = 'structure';
		self::$hideOffline = (boolean) $hideOffline;
		self::$clang       = $clang;

		if ($rootCats) {
			foreach ($rootCats as $rootCat) {
				self::walkTree($rootCat, 0);
			}
		}

		return $select;
	}

	private static function walkTree($category, $depth)
	{
		global $REX;
		if (empty($category)) return;

		if (self::canSeeCategory($category)) {
			$name = $category->getName();

			// Die Anzeige hängt immer vom aktuellen Benutzer ab.

			if ($REX['USER']->hasPerm('advancedMode[]')) {
				$name .= ' ['.$category->getId().']';
			}

			self::$select->addValue($category->getId(), str_repeat(' ', $depth*2).$name);
		}

		$children = $category->getChildren(self::$hideOffline, self::$clang);

		if (is_array($children)) {
			foreach ($children as $child) {
				self::walkTree($child, $depth + 1);
			}
		}
	}

	private static function isAdmin() {
		if (!self::$user) return false;

		$isAdmin = self::$user->hasPerm('admin[]');

		switch (self::$type) {
			case 'media':     $isAdmin |= self::$user->hasPerm('media[0]'); break;
			case 'structure': $isAdmin |= self::$user->hasPerm('csw[0]'); break;
		}

		return $isAdmin;
	}

	private static function canSeeCategory($category) {
		if (self::$user === null || self::isAdmin()) return true;

		switch (self::$type) {
			case 'media':
				return self::$user->hasPerm('media['.$category->getId().']');

			case 'structure':
				return self::$user->hasCategoryPerm($category->getId());

			default:
				return true;
		}
	}
}
