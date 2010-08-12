<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

$addonService  = sly_Service_Factory::getService('AddOn');
$pluginService = sly_Service_Factory::getService('Plugin');

foreach ($addonService->getAvailableAddons() as $addonName) {
	$addonService->loadConfig($addonName);
	$addonConfig = $addonService->baseFolder($addonName).'config.inc.php';

	if (file_exists($addonConfig)) {
		require_once $addonConfig;
	}

	foreach ($pluginService->getAvailablePlugins($addonName) as $pluginName) {
		$pluginService->loadConfig(array($addonName, $pluginName));
		$pluginConfig = $pluginService->baseFolder(array($addonName, $pluginName)).'config.inc.php';

		if (file_exists($pluginConfig)) {
			$pluginService->mentalGymnasticsInclude($pluginConfig, array($addonName, $pluginName));
		}
	}
}

unset($addonService);
unset($pluginService);

rex_register_extension_point('ADDONS_INCLUDED');
