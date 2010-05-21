<?php

/**
 * Menupunkt nur einbinden, falls ein Plugin sich angemeldet hat 
 * via BE_STYLE_PAGE_CONTENT inhalt auszugeben
 *  
 * @param $params Extension-Point Parameter
 */
function rex_be_add_page($params)
{
	if (rex_extension_is_registered('BE_STYLE_PAGE_CONTENT')) {
		global $REX;
		$REX['ADDON']['name']['be_style'] = 'Backend Style';
	}
}

/**
 * Fügt die benötigen Stylesheets ein
 * 
 * @param $params Extension-Point Parameter
 */
function rex_be_style_css_add($params)
{
	foreach (OOPlugin::getAvailablePlugins('be_style') as $plugin) {
		$layout = sly_Core::getLayout();
		$layout->addCssfile('scaffold/be_style/'.$plugin.'/css_main.css');
	}
}
