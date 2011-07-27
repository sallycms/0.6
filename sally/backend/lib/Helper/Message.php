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
 * @author zozi@webvariants.de
 */
class sly_Helper_Message {

	public static function message($message, $cssClass = '', $msgwrapper = 'p') {
		$return = '<div class="sly-message ' . $cssClass . '"><' . $msgwrapper . '>';
		$return .= '<span>' . $message . '</span>';
		$return .= '</' . $msgwrapper . '></div>';
		return $return;
	}

	public static function info($message) {
		return self::message($message, 'sly-info');
	}

	public static function warn($message) {
		return self::message($message, 'sly-warn');
	}

}

?>
