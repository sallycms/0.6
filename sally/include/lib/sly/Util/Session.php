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
 * @ingroup util
 */
class sly_Util_Session {
    /**
	 * start a session if it noch already started
	 *
	 */
	public static function start()
	{
		if (!session_id()) session_start();
	}
	
}
?>
