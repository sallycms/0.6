<?php

$REX['ADDON']  = $config->get('ADDON');
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
