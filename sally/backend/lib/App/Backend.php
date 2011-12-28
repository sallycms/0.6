<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_App_Backend extends sly_App_Base implements sly_App_Interface {
	const CONTROLLER_PARAM = 'page';    ///< string  the request param that contains the page
	const ACTION_PARAM     = 'func';    ///< string  the request param that contains the action

	protected $controller = null;
	protected $action     = null;

	public function initialize() {
		$config = sly_Core::config();

		// only start session if not running unit tests
		if (!SLY_IS_TESTING) sly_Util_Session::start();

		// load static config
		$config->loadStatic(SLY_SALLYFOLDER.'/backend/config/static.yml');

		// are we in setup mode?
		$isSetup = $config->get('SETUP');

		// init timezone and locale
		$this->initUserSettings($isSetup);

		// make sure our layout is used later on
		sly_Core::setLayout(new sly_Layout_Backend());

		// instantiate asset service before addOns are loaded to make sure
		// the Scaffold CSS processing is first in the line for CSS files
		sly_Service_Factory::getAssetService();

		// and now init the rest (addOns, listeners, ...)
		parent::initialize();
	}

	public function run() {
		$config  = sly_Core::config();
		$isSetup = $config->get('SETUP');
		$layout  = sly_Core::getLayout();

		// force login controller if no login is found
		if (!$isSetup && sly_Util_User::getCurrentUser() === null) {
			$this->controller = 'login';
		}

		// get the most probably already prepared response object
		// (addOns had a shot at modifying it)
		$response = sly_Core::getResponse();

		// get page and action from the current request
		$page   = $this->controller === null ? $this->findPage() : $this->controller;
		$action = $this->getActionParam('index');

		// let the core know where we are
		$this->controller = $page;
		$this->action     = $action;

		// let the layout know as well
		$layout->setCurrentPage($page);

		// notify the addOns
		$this->notifySystemOfController(true);

		// do it, baby
		$content  = $this->dispatch($page, $action);
		$response = sly_Core::getResponse(); // re-fetch the current global response

		// if we got a string, wrap it in the layout and then in the response object
		if (is_string($content)) {
			$layout->setContent($content);
			$payload = $layout->render();
			$response->setContent($payload);
		}

		// if we got a response, use that one
		elseif ($content instanceof sly_Response) {
			$response = $content;
		}

		// everything else is a bug
		else {
			throw new LogicException('Controllers must return either content as a string or a Response, got '.gettype($content).'.');
		}

		// send the response :)
		$response->send();
	}

	protected function initUserSettings($isSetup) {
		if (!SLY_IS_TESTING && $isSetup) {
			$locale        = sly_Core::getDefaultLocale();
			$locales       = sly_I18N::getLocales(SLY_SALLYFOLDER.'/backend/lang');
			$requestLocale = sly_request('lang', 'string');
			$timezone      = @date_default_timezone_get();
			$user          = null;

			if (in_array($requestLocale, $locales)) {
				$locale = $requestLocale;
			}

			// force setup page
			$this->controller = 'setup';
		}
		else {
			$locale   = '';
			$timezone = '';
			$user     = sly_Util_User::getCurrentUser();

			// get user values
			if ($user instanceof sly_Model_User) {
				$locale   = $user->getBackendLocale();
				$timezone = $user->getTimeZone();
			}

			// re-set the values if the user profile has no value (meaning 'default')
			if (empty($locale))   $locale   = sly_Core::getDefaultLocale();
			if (empty($timezone)) $timezone = sly_Core::getTimezone();
		}

		// set the i18n object
		$i18n = new sly_I18N($locale, SLY_SALLYFOLDER.'/backend/lang');
		sly_Core::setI18N($i18n);

		// set timezone
		date_default_timezone_set($timezone);
	}

	/**
	 * Get the page param
	 *
	 * Reads the page param from the $_REQUEST array and returns it.
	 *
	 * @param  string $default  default value if param is not present
	 * @return string           the page param
	 */
	public function getControllerParam($default = '') {
		return strtolower(sly_request(self::CONTROLLER_PARAM, 'string', $default));
	}

	/**
	 * Get the action param
	 *
	 * Reads the action param from the $_REQUEST array and returns it.
	 *
	 * @param  string $default  default value if param is not present
	 * @return string           the action param
	 */
	public function getActionParam($default = '') {
		return strtolower(sly_request(self::ACTION_PARAM, 'string', $default));
	}

	/**
	 * Get the currently active page
	 *
	 * The page determines the controller that will be used for dispatching. It
	 * will be put into $_REQUEST (so that third party code can access the
	 * correct value).
	 *
	 * When setup is true, requests to the setup controller will be redirected to
	 * the profile page (always accessible). Otherwise, this method will also
	 * check whether the current user has access to the found controller. If a
	 * forbidden controller is requested, the profile page is used.
	 *
	 * @return string  the currently active page
	 */
	protected function findPage() {
		$config = sly_Core::config();
		$page   = $this->getControllerParam();

		// Erst normale Startseite, dann User-Startseite, dann System-Startseite und
		// zuletzt auf die Profilseite zurückfallen.

		if (strlen($page) === 0 || !$this->isControllerAvailable($page)) {
			$user = sly_Util_User::getCurrentUser();
			$page = $user ? $user->getStartpage() : null;

			if ($page === null || !$this->isControllerAvailable($page)) {
				$page = strtolower($config->get('START_PAGE'));

				if (!$this->isControllerAvailable($page)) {
					$page = 'profile';
				}
			}
		}

		return $page;
	}

	public function getControllerClassPrefix() {
		return 'sly_Controller';
	}

	public function getCurrentController() {
		return $this->controller;
	}

	public function getCurrentAction() {
		return $this->action;
	}

	protected function handleControllerError(Exception $e, $controller, $action) {
		// throw away all content (including notices and warnings)
		while (ob_get_level()) ob_end_clean();

		// manually create the error controller to pass the exception
		$controller = new sly_Controller_Error($e);

		// forward to the error page
		return new sly_Response_Forward($controller, 'index');
	}
}
