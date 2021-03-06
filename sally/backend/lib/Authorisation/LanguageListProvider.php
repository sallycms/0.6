<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup authorisation
 */
class sly_Authorisation_LanguageListProvider implements sly_Authorisation_ListProvider {

	public function getObjectIds() {
		return sly_Util_Language::findAll(true);
	}

	public function getObjectTitle($id) {
		return sly_Util_Language::findById($id)->getName();
	}

}

?>
