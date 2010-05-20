<?php

$config->appendFile($SLY['INCLUDE_PATH'].'/config/addons.yaml', 'ADDON');
$config->appendFile($SLY['INCLUDE_PATH'].'/config/plugins.yaml', 'ADDON/plugins');
$SLY['ADDON'] = $config->get('ADDON');

foreach (OOAddon::getAvailableAddons() as $addonName) {
	$addonConfig = rex_addons_folder($addonName).'config.inc.php';
	
	if (file_exists($addonConfig)) {
		require_once $addonConfig;
	}

	foreach (OOPlugin::getAvailablePlugins($addonName) as $pluginName) {
		$pluginConfig = rex_plugins_folder($addonName, $pluginName).'config.inc.php';
		
		if (file_exists($pluginConfig)) {
			rex_pluginManager::addon2plugin($addonName, $pluginName, $pluginConfig);
		}
	}
}

rex_register_extension_point('ADDONS_INCLUDED');
