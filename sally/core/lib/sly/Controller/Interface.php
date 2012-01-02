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
 * @since 0.6
 */
interface sly_Controller_Interface {
	/**
	 * Check access
	 *
	 * This method should check whether the current user (if any) has access to
	 * the requested action. In many cases, you will just make sure someone is
	 * logged in at all, but you can also decide this on a by-action basis.
	 *
	 * @param  string $action  the action to be called
	 * @return boolean         true if access is granted, else false
	 */
	public function checkPermission($action);
}
