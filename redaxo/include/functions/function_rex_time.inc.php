<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

rex_startScripttime();

function rex_showScriptTime()
{
	$start    = rex_startScriptTime();
	$end      = microtime(true);
	$duration = round($end - $start, 3);

	return $duration;
}

function rex_getCurrentTime()
{
	return microtime(true);
}

function rex_startScriptTime()
{
	static $start = null;
	if ($start === null) $start = microtime(true);
	return $start;
}
