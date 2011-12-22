<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup authorisation
 */
class sly_Authorisation_MediaCategoryListProvider implements sly_Authorisation_ListProvider {

	public function getObjectIds() {
		$categories = sly_Service_Factory::getMediaCategoryService()->find();
		$res = array(self::ALL);
		foreach($categories as $category) {
			$res[] = $category->getId();
		}
		return $res;
	}

	public function getObjectTitle($id) {
		if($id == self::ALL) return t('all');
		return sly_Util_MediaCategory::findById($id)->getName();
	}

}

?>
