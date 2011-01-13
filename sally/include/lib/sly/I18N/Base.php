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
 * @ingroup i18n
 */
interface sly_I18N_Base {
	public function msg($key);
	public function addMsg($key, $msg);
	public function hasMsg($key);
}
