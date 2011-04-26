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
class sly_Util_HTTP {
	public static function redirect($target, $parameters = array(), $noticeText = '', $status = 301) {
		$targetUrl = '';

		if ($target instanceof sly_Model_Article || sly_Util_String::isInteger($target)) {
			$clang     = $target instanceof sly_Model_Article ? $target->getCLang() : sly_Core::getCurrentClang();
			$targetUrl = self::getAbsoluteUrl($target, $clang, $parameters, '&');
		}
		else {
			$targetUrl = $target;
		}

		if (empty($noticeText)) {
			$noticeText = t('redirect_to', sly_html($targetUrl));
		}

		$stati  = array(301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other');
		$status = isset($stati[$status]) ? $status : 301;
		$text   = $stati[$status];

		while (ob_get_level()) ob_end_clean();
		header('HTTP/1.0 '.$status.' '.$text);
		header('Location: '.$targetUrl);
		exit($noticeText);
	}

	public static function getAbsoluteUrl($targetArticle, $clang = false, $parameters = array(), $divider = '&amp;') {
		$articleUrl = self::getUrl($targetArticle, $clang, $parameters, $divider);

		if ($articleUrl[0] == '/') {
			return self::getBaseUrl(false).$articleUrl;
		}

		$baseURL = self::getBaseUrl(true);
		return $baseURL.'/'.$articleUrl;
	}

	public static function getUrl($targetArticle, $clang = false, $parameters = array(), $divider = '&amp;') {
		$articleID = self::resolveArticle($targetArticle);
		return rex_getUrl($articleID, $clang, 'NoName', $parameters, $divider);
	}

	public static function getBaseUrl($addScriptPath = false) {
		$baseURL = 'http'.(!empty($_SERVER['HTTPS']) ? 's' : '').'://'.$_SERVER['HTTP_HOST'].($addScriptPath ? str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])) : '');
		return rtrim($baseURL, '/');
	}

	/**
	 * Ermitteln einer Artikel-ID
	 *
	 * Diese Methode ermittelt zu einem Artikel die dazugehörige ID.
	 *
	 * @param  mixed $article  sly_Model_Article oder int
	 * @return int             die ID oder -1 falls keine gefunden wurde
	 */
	protected static function resolveArticle($article) {
		if (sly_Util_String::isInteger($article)) return (int) $article;
		if ($article instanceof sly_Model_Article) return (int) $article->getId();

		return -1;
	}
}
