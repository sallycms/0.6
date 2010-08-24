<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class sly_Controller_Base
{
	const PAGEPARAM    = 'page';
	const SUBPAGEPARAM = 'subpage';
	const ACTIONPARAM  = 'func';

	protected $action;

	protected function __construct()
	{
		$this->action = rex_request(self::ACTIONPARAM, 'string', 'index');
	}

	public static function getPage($isLogin = false)
	{
		global $REX;

		$config = sly_Core::config();
		$page   = strtolower(sly_request(self::PAGEPARAM, 'string'));

		if ($isLogin) {
			$page = strtolower($REX['LOGIN']->getStartpage());
		}

		// Erst normale Startseite, dann User-Startseite, dann System-Startseite und
		// zuletzt auf die Profilseite zurückfallen.

		if (!isset($REX['PAGES'][$page]) && !class_exists('sly_Controller_'.ucfirst($page))) {
			$page = strtolower($REX['LOGIN']->getStartpage());

			if (!isset($REX['PAGES'][$page])) {
				$page = strtolower($config->get('START_PAGE'));

				if (!isset($REX['PAGES'][$page])) {
					$page = 'profile';
				}
			}
		}

		$_REQUEST[self::PAGEPARAM] = $page;
		return $page;
	}

	public static function factory($forcePage = null, $forceSubpage = null)
	{
		$config = sly_Core::config();
		$page    = $forcePage === null    ? sly_request(self::PAGEPARAM, 'string', $config->get('START_PAGE')) : $forcePage;
		$subpage = $forceSubpage === null ? strtolower(sly_request(self::SUBPAGEPARAM, 'string', '')) : $forceSubpage;
		$name    = 'sly_Controller_'.ucfirst($page);

		if (!empty($subpage) && $subpage != 'index') {
			$name .= '_'.ucfirst($subpage);
		}

		if (class_exists($name)) {
			return new $name($name);
		}

		return null;
	}

	public function dispatch()
	{
		if (!method_exists($this, $this->action)) {
			throw new sly_Controller_Exception('HTTP 404: Methode '. $this->action .' in '. get_class($this) .' nicht gefunden!');
		}

		if ($this->checkPermission() !== true){
			throw new sly_Authorisation_Exception('HTTP 403: Zugriff auf '. $this->action .' in '. get_class($this) .' nicht gestattet!');
		}

		$this->init();

		$method = $this->action;
		$retval = $this->$method();

		$this->teardown();
	}

	protected function render($filename, $params = array())
	{
		global $REX, $I18N;

		// Die Parameternamen $params und $filename sind zu kurz, als dass
		// man sie zuverlässig nutzen könnte. Wenn $params durch extract()
		// während der Ausführung überschrieben wird kann das unvorhersehbare
		// Folgen haben. Darum wird $filename und $params in kryptische
		// Variablen verpackt und aus dem Kontext entfernt.
		$filenameHtuG50hNCdikAvf7CZ1F = $filename;
		$paramsHtuG50hNCdikAvf7CZ1F = $params;
		unset($filename);
		unset($params);
		extract($paramsHtuG50hNCdikAvf7CZ1F);

		ob_start();
		include SLY_INCLUDE_PATH.DIRECTORY_SEPARATOR.$filenameHtuG50hNCdikAvf7CZ1F;
		print ob_get_clean();
	}

	protected function init()
	{
	}

	protected function teardown()
	{
	}

	protected abstract function index();
	protected abstract function checkPermission();
}
