<?php
 
/**
 * F�gt die zus�tzlichen zu css_main.css ben�tigten Stylesheets ein
 * 
 * @param $params Extension-Point Parameter
 */
function rex_be_style_agk_skin_css_add($params)
{
  echo '      
    <!--[if lte IE 7]>
      <link rel="stylesheet" href="css/be_style/agk_skin/css_ie_lte_7.css" type="text/css" media="screen, projection, print" />
    <![endif]-->
  
    <!--[if lte IE 6]>
      <link rel="stylesheet" href="css/be_style/agk_skin/css_ie_lte_6.css" type="text/css" media="screen, projection, print" />
    <![endif]-->';

}
