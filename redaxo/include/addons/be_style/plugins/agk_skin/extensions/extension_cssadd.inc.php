<?php
 
/**
 * F�gt die zus�tzlichen zu css_main.css ben�tigten Stylesheets ein
 * 
 * @param $params Extension-Point Parameter
 */
function rex_be_style_agk_skin_css_add($params)
{
	$layout = sly_Core::getLayout();
	$layout->addCSSFile('css/be_style/agk_skin/css_ie_lte_7.css', 'all', 'if lte IE 6');
	$layout->addCSSFile('css/be_style/agk_skin/css_ie_lte_6.css', 'all', 'if lte IE 6');
}
