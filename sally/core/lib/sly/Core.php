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
 * @ingroup core
 */
class sly_Core {
	private static $instance;  ///< sly_Core
	private $app;              ///< sly_App_Interface
	private $cache;            ///< BabelCache_Interface
	private $configuration;    ///< sly_Configuration
	private $dispatcher;       ///< sly_Event_Dispatcher
	private $curClang;         ///< int
	private $curArticleId;     ///< int
	private $varTypes;         ///< array
	private $layout;           ///< sly_Layout
	private $i18n;             ///< sly_I18N
	private $errorHandler;     ///< sly_ErrorHandler
	private $response;         ///< sly_Response

	// Use the following constants when you don't have access to the real
	// config values (i.e. when in setup mode). They should map the values
	// in sallyStatic.yml.

	const DEFAULT_FILEPERM = 0664; ///< int
	const DEFAULT_DIRPERM  = 0777; ///< int

	private function __construct() {
		$this->configuration = new sly_Configuration();
		$this->dispatcher    = new sly_Event_Dispatcher();
	}

	/**
	 * Get the single core instance
	 *
	 * @return sly_Core  the singleton
	 */
	public static function getInstance() {
		if (!self::$instance) self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Get the global caching instance
	 *
	 * @return BabelCache_Interface  caching instance
	 */
	public static function cache() {
		// Because sly_Cache depends on self::config() being available, we cannot
		// init the cache in sly_Core->__construct().

		$inst = self::getInstance();
		if (!$inst->cache) $inst->cache = sly_Cache::factory();
		return $inst->cache;
	}

	/**
	 * @param sly_App_Interface $app  the current system app
	 */
	public static function setCurrentApp(sly_App_Interface $app) {
		self::getInstance()->app = $app;
	}

	/**
	 * @return sly_App_Interface
	 */
	public static function getCurrentApp() {
		return self::getInstance()->app;
	}

	/**
	 * @param int $clangId  the new clang or null to reset
	 */
	public static function setCurrentClang($clangId) {
		self::getInstance()->curClang = $clangId === null ? null : (int) $clangId;
	}

	/**
	 * @param int $articleId  the new article ID or null to reset
	 */
	public static function setCurrentArticleId($articleId) {
		self::getInstance()->curArticleId = $articleId === null ? null : (int) $articleId;
	}

	/**
	 * Returns the current language ID
	 *
	 * Checks the request param 'clang' and returns a validated value.
	 *
	 * @return int  the current clang
	 */
	public static function getCurrentClang() {
		$instance = self::getInstance();

		if (!isset($instance->curClang)) {
			$instance->curClang = sly_request('clang', 'rex-clang-id', self::getDefaultClangId());
		}

		return $instance->curClang;
	}

	/**
	 * Returns the current language
	 *
	 * @return sly_Model_Language  the current language
	 */
	public static function getCurrentLanguage() {
		$clang = sly_Core::getCurrentClang();
		return sly_Service_Factory::getLanguageService()->findById($clang);
	}

	/**
	 * Returns the current article ID
	 *
	 * Checks the request param 'article_id' and returns a validated value. If
	 * the article was not found, the ID of the Not Found article is returned.
	 *
	 * @return int  the current article ID
	 */
	public static function getCurrentArticleId() {
		$conf     = self::config();
		$instance = self::getInstance();

		if (!isset($instance->curArticleId)) {
			$instance->curArticleId = sly_request('article_id', 'int', self::getSiteStartArticleId());

			if (!sly_Util_Article::exists($instance->curArticleId)) {
				$instance->curArticleId = self::getNotFoundArticleId();
			}

			if (!sly_Util_Article::exists($instance->curArticleId)) {
				$instance->curArticleId = -1;
			}
		}

		return $instance->curArticleId;
	}

	/**
	 * Returns the current article
	 *
	 * @param  int $clang         null for the current clang, or else a specific clang
	 * @return sly_Model_Article  the current article
	 */
	public static function getCurrentArticle($clang = null) {
		$articleID = self::getCurrentArticleId();
		$clang     = $clang === null ? self::getCurrentClang() : (int) $clang;

		return sly_Util_Article::findById($articleID, $clang);
	}

	/**
	 * Register a new var class
	 *
	 * @param string $varType  class name of the new variable
	 */
	public static function registerVarType($varType) {
		self::getInstance()->varTypes[] = $varType;
	}

	/**
	 * Gibt immer eine Liste von Instanzen der Variablentypen zurück
	 *
	 * @throws sly_Exception  if one of the registered classes does not inherit rex_var
	 * @return array          list of rex_var instances
	 */
	public static function getVarTypes() {
		$instance = self::getInstance();

		if (!isset($instance->varTypes)) $instance->varTypes = array();

		foreach ($instance->varTypes as $idx => $obj) {
			if (is_string($obj)) { // Es hat noch kein Autoloading für diese Klasse stattgefunden
				$obj = new $obj();
				if (!($obj instanceof rex_var)) throw new sly_Exception('VarType '.$instance->varTypes[$idx].' is no inheriting class of rex_var.');
				$instance->varTypes[$idx] = $obj;
			}
		}

		return $instance->varTypes;
	}

	/**
	 * @return sly_Configuration  the system configuration
	 */
	public static function config() {
		return self::getInstance()->configuration;
	}

	/**
	 * @return sly_Event_Dispatcher  the event dispatcher
	 */
	public static function dispatcher() {
		return self::getInstance()->dispatcher;
	}

	/**
	 * Get the current layout instance
	 *
	 * @return sly_Layout  the layout instance
	 */
	public static function getLayout() {
		$instance = self::getInstance();

		if (!isset($instance->layout)) {
			throw new sly_Exception(t('layout_has_not_been_set'));
		}

		return $instance->layout;
	}

	/**
	 * Set the current layout instance
	 *
	 * @param sly_Layout $layout  the layout instance
	 */
	public static function setLayout(sly_Layout $layout) {
		self::getInstance()->layout = $layout;
	}

	/**
	 * @return boolean  true if backend, else false
	 */
	public static function isBackend() {
		return defined('IS_SALLY_BACKEND') && IS_SALLY_BACKEND == true;
	}

	/**
	 * @return boolean  true if developer mode, else false
	 */
	public static function isDeveloperMode() {
		static $var = null;
		if ($var === null) $var = (boolean) self::config()->get('DEVELOPER_MODE');
		return $var;
	}

	/**
	 * @return string  the project name
	 */
	public static function getProjectName() {
		return self::config()->get('PROJECTNAME');
	}

	/**
	 * @return int  the project homepage ID (start article)
	 */
	public static function getSiteStartArticleId() {
		return (int) self::config()->get('START_ARTICLE_ID');
	}

	/**
	 * @return int  the not-found article's ID
	 */
	public static function getNotFoundArticleId() {
		return (int) self::config()->get('NOTFOUND_ARTICLE_ID');
	}

	/**
	 * @return int  the default clang ID
	 */
	public static function getDefaultClangId() {
		return (int) self::config()->get('DEFAULT_CLANG_ID');
	}

	/**
	 * @return string  the default (backend) locale
	 */
	public static function getDefaultLocale() {
		return self::config()->get('DEFAULT_LOCALE');
	}

	/**
	 * @return string  the default article type
	 */
	public static function getDefaultArticleType() {
		return self::config()->get('DEFAULT_ARTICLE_TYPE');
	}

	/**
	 * @return string  the class name of the global caching strategy
	 */
	public static function getCachingStrategy() {
		return self::config()->get('CACHING_STRATEGY');
	}

	/**
	 * @return string  the timezone's name
	 */
	public static function getTimezone() {
		return self::config()->get('TIMEZONE');
	}

	/**
	 * @return int  permissions for files
	 */
	public static function getFilePerm($default = self::DEFAULT_FILEPERM) {
		return (int) self::config()->get('FILEPERM', $default);
	}

	/**
	 * @return int  permissions for directory
	 */
	public static function getDirPerm($default = self::DEFAULT_DIRPERM) {
		return (int) self::config()->get('DIRPERM', $default);
	}

	/**
	 * @return sring  the database table prefix
	 */
	public static function getTablePrefix() {
		return self::config()->get('DATABASE/TABLE_PREFIX');
	}

	/**
	 * @return sly_I18N  the global i18n instance
	 */
	public static function getI18N() {
		return self::getInstance()->i18n;
	}

	/**
	 * @param sly_I18N $i18n  the new translation object
	 */
	public static function setI18N(sly_I18N $i18n) {
		self::getInstance()->i18n = $i18n;
	}

	/**
	 * Get persistent registry instance
	 *
	 * @return sly_Registry_Persistent  the registry singleton
	 */
	public static function getPersistentRegistry() {
		return sly_Registry_Persistent::getInstance();
	}

	/**
	 * Get temporary registry instance
	 *
	 * @return sly_Registry_Temp  the registry singleton
	 */
	public static function getTempRegistry() {
		return sly_Registry_Temp::getInstance();
	}

	/**
	 * @param  string $pattern  the pattern (X = major version, Y = minor version, Z = minor version)
	 * @return string           the pattern with replaced version numbers
	 */
	public static function getVersion($pattern = 'X.Y.Z') {
		static $version = null;

		if ($version === null)  {
			$config  = self::config();
			$version = $config->get('VERSION');
		}

		$pattern = str_replace('s', 'sly', $pattern);
		$pattern = str_replace('S', 'sally', $pattern);
		$pattern = str_replace('X', $version['MAJOR'], $pattern);
		$pattern = str_replace('Y', $version['MINOR'], $pattern);
		$pattern = str_replace('Z', $version['BUGFIX'], $pattern);

		return $pattern;
	}

	/**
	 * Returns the backend navigation
	 * @deprecated
	 * @return sly_Layout_Navigation_Backend  the navigation object used for the backend menu
	 */
	public static function getNavigation() {
		return self::getLayout('Backend')->getNavigation();
	}

	/**
	 * loads all known addons into Sally
	 */
	public static function loadAddons() {
		sly_Service_Factory::getAddOnService()->loadComponents();
		self::dispatcher()->notify('ADDONS_INCLUDED');
	}

	public static function registerCoreVarTypes() {
		self::registerVarType('rex_var_article');
		self::registerVarType('rex_var_template');
		self::registerVarType('rex_var_value');
		self::registerVarType('rex_var_link');
		self::registerVarType('rex_var_media');
	}

	public static function registerListeners() {
		$listeners  = self::config()->get('LISTENERS', array());
		$dispatcher = self::dispatcher();

		foreach ($listeners as $event => $callbacks) {
			foreach ($callbacks as $callback) {
				$dispatcher->register($event, $callback);
			}
		}

		$dispatcher->notify('SLY_LISTENERS_REGISTERED');
	}

	/**
	 * @param sly_ErrorHandler $errorHandler  the new error handler instance
	 */
	public static function setErrorHandler(sly_ErrorHandler $errorHandler) {
		self::getInstance()->errorHandler = $errorHandler;
	}

	/**
	 * @return sly_ErrorHandler  the current error handler
	 */
	public static function getErrorHandler() {
		return self::getInstance()->errorHandler;
	}

	/**
	 * @param sly_Response $errorHandler  the new response instance
	 */
	public static function setResponse(sly_Response $response) {
		self::getInstance()->response = $response;
	}

	/**
	 * @return sly_Response  the current response
	 */
	public static function getResponse() {
		$instance = self::getInstance();

		if (!$instance->response) {
			$response = new sly_Response('', 200);
			$response->setContentType('text/html', 'UTF-8');

			$instance->response = $response;
		}

		return $instance->response;
	}

	/**
	 * Returns the current backend page
	 *
	 * @deprecated as of 0.6, use getCurrentController()
	 *
	 * @return string  current page or null if in frontend
	 */
	public static function getCurrentPage() {
		return self::isBackend() ? self::getCurrentController() : null;
	}

	/**
	 * Returns the current controller
	 *
	 * @return string  current controller
	 */
	public static function getCurrentController() {
		return self::getCurrentApp()->getCurrentController();
	}

	/**
	 * Clears the complete system cache
	 *
	 * @return string  the info messages (collected from all listeners)
	 */
	public static function clearCache() {
		clearstatcache();

		$obj = new sly_Util_Directory(SLY_DYNFOLDER.'/internal/sally/article_slice');
		$obj->deleteFiles();

		$obj = new sly_Util_Directory(SLY_DYNFOLDER.'/internal/sally/templates');
		$obj->deleteFiles();

		// clear loader cache
		sly_Loader::clearCache();

		// clear our own data caches
		self::cache()->flush('sly', true);

		// clear asset cache
		sly_Service_Factory::getAssetService()->clearCache();

		// create bootcache
		sly_Util_BootCache::recreate('frontend');
		sly_Util_BootCache::recreate('backend');

		return self::dispatcher()->filter('SLY_CACHE_CLEARED', t('delete_cache_message'));
	}
}
