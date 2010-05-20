<?php

class rex_form_widget_linkmap_element extends rex_form_element
{
  // 1. Parameter nicht genutzt, muss aber hier stehen,
  // wg einheitlicher Konstrukturparameter
  function rex_form_widget_linkmap_element($tag = '', &$table, $attributes = array())
  {
    parent::rex_form_element('', $table, $attributes);
  }


  function formatElement()
  {
    static $widget_counter = 1;

    $html = rex_var_link::getLinkButton($widget_counter, $this->getValue());
    $html = str_replace('LINK['. $widget_counter .']', $this->getAttribute('name'), $html);

    $widget_counter++;
    return $html;
  }
}
