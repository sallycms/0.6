<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

// Da quasi alle verfügbaren In-Memory-Caches zwar eine (Teil)menge an Daten
// löschen können, aber keine Suchen erlauben, gibt es diese kleine
// Zwischen-Interface.

interface sly_Cache_IFlushable extends sly_ICache {
	public function flush($namespace, $recursive = false);
}
