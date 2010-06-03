<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

$addonService  = sly_Service_Factory::getService('AddOn');
$pluginService = sly_Service_Factory::getService('Plugin');

foreach ($addonService->getAvailableAddons() as $addonName) {
	$addonConfig = $addonService->baseFolder($addonName).'config.inc.php';
	
	if (file_exists($addonConfig)) {
		require_once $addonConfig;
	}

	foreach ($pluginService->getAvailablePlugins($addonName) as $pluginName) {
		$pluginConfig = $pluginService->baseFolder(array($addonName, $pluginName)).'config.inc.php';
		
		if (file_exists($pluginConfig)) {
			$pluginService->mentalGymnasticsInclude($pluginConfig, array($addonName, $pluginName));
		}
	}
}

unset($addonService);
unset($pluginService);

rex_register_extension_point('ADDONS_INCLUDED');
