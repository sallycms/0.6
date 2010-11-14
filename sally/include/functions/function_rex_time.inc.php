<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

rex_startScriptTime();

function rex_showScriptTime() {
	$start    = rex_startScriptTime();
	$end      = microtime(true);
	$duration = round($end - $start, 3);

	return $duration;
}

function rex_getCurrentTime() {
	return microtime(true);
}

function rex_startScriptTime() {
	static $start = null;
	if ($start === null) $start = microtime(true);
	return $start;
}
