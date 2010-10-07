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
class sly_Util_Scaffold {
	/**
	 * Verarbeitet eine CSS-Datei mit CSScaffold
	 *
	 * In dieser Methode wird ein externes Scaffold (standardmäßig in
	 * assets/css/scaffold) eingebunden und zur Verarbeitung von CSS genutzt.
	 * Dabei wird explizit nicht gecached, da der Deployer diese Aufgabe
	 * übernimmt.
	 *
	 * @uses   Scaffold
	 * @param  string $cssFile  der Dateiname
	 * @return string           der fertig verarbeitete CSS-Code
	 */
	public static function process($cssFile) {
		static $isScaffoldInit = false;

		if (!$isScaffoldInit) {
			$scaffoldBase = sly_Util_Directory::join(SLY_INCLUDE_PATH, 'lib', 'Scaffold');
			require_once $scaffoldBase.'/libraries/Bootstrap.php';

			if (!defined('SCAFFOLD_PRODUCTION')) {
				define('SCAFFOLD_PRODUCTION', false);
			}

			$config = array(
				'document_root'    => $_SERVER['DOCUMENT_ROOT'],
				'system'           => $scaffoldBase,
				'cache'            => sly_Util_Directory::join($scaffoldBase, 'cache'),
				'cache_lifetime'   => false,
				'disable_flags'    => true,
				'enable_log'       => false,
				'error_threshold'  => 1,
				'gzip_compression' => false
			);

			Scaffold::setup($config);
			$isScaffoldInit = true;
		}

		return Scaffold::process($cssFile);
	}
}
