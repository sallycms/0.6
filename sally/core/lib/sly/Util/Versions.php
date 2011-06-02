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
 * @ingroup util
 */
class sly_Util_Versions {
	public static function get($component) {
		return sly_Core::config()->get('versions/'.$component, false);
	}

	public static function set($component, $version) {
		return sly_Core::config()->set('versions/'.$component, $version);
	}
}
