<?php

/**
 * Sprach-Unterstützung von REDAXO
 *
 * @deprecated  sly_I18N direkt nutzen, die API ist identisch
 */
class i18n extends sly_I18N {
	public function __construct($locale = 'de_de', $path) {
		$type = defined('E_USER_DEPRECATED') ? E_USER_DEPRECATED : E_USER_WARNING;
		trigger_error('i18n ist deprecated. Benutzen Sie stattdessen sly_I18N.', $type);
		parent::__construct($locale, $path);
	}
}
