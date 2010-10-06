<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

$addonService  = sly_Service_Factory::getAddOnService();
$pluginService = sly_Service_Factory::getPluginService();

foreach ($addonService->getAvailableAddons() as $addonName) {
	$addonService->loadAddon($addonName);

	foreach ($pluginService->getAvailablePlugins($addonName) as $pluginName) {
		$pluginService->loadPlugin(array($addonName, $pluginName));
	}
}

unset($addonService);
unset($pluginService);

rex_register_extension_point('ADDONS_INCLUDED');