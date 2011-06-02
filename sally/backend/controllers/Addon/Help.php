<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Addon_Help extends sly_Controller_Sally {
	protected $addon  = null;
	protected $plugin = null;

	public function init() {
		$addon  = sly_request('addon', 'string', '');
		$plugin = sly_request('plugin', 'string', '');

		$addons      = sly_Service_Factory::getAddOnService()->getRegisteredAddOns();
		$this->addon = in_array($addon, $addons) ? $addon : null;

		if ($this->addon) {
			$layout = sly_Core::getLayout();
			$layout->pageHeader(t('addon'));
			print '<div class="sly-content">';

			$plugins      = sly_Service_Factory::getPluginService()->getRegisteredPlugins($this->addon);
			$this->plugin = in_array($plugin, $plugins) ? $plugin : null;
		}
		else {
			$controller = new sly_Controller_Addon();
			$controller->init();
			$controller->index();
		}
	}

	public function teardown() {
		print '</div>';
	}

	public function index() {
		if ($this->addon === null) return;

		$this->render('views/addon/help.phtml', array(
			'addon'  => $this->addon,
			'plugin' => $this->plugin
		));
	}

	public function checkPermission() {
		$user = sly_Util_User::getCurrentUser();
		return isset($user) && $user->isAdmin();
	}
}
