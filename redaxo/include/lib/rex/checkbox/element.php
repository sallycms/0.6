<?php

class rex_form_checkbox_element extends rex_form_options_element
{
  // 1. Parameter nicht genutzt, muss aber hier stehen,
  // wg einheitlicher Konstrukturparameter
  function rex_form_checkbox_element($tag = '', &$table, $attributes = array())
  {
    parent::rex_form_options_element('', $table, $attributes);
    // Jede checkbox bekommt eingenes Label
    $this->setLabel('');
  }

  function formatLabel()
  {
    // Da Jedes Feld schon ein Label hat, hier nur eine "Ueberschrift" anbringen
    return '<span>'. $this->getLabel() .'</span>';
  }

  function formatElement()
  {
    $s = '';
    $values = explode('|+|', $this->getValue());
    $options = $this->getOptions();
    $name = $this->getAttribute('name');
    $id = $this->getAttribute('id');

    $attr = '';
    foreach($this->getAttributes() as $attributeName => $attributeValue)
    {
      if($attributeName == 'name' || $attributeName == 'id') continue;
      $attr .= ' '. $attributeName .'="'. $attributeValue .'"';
    }

    foreach($options as $opt_name => $opt_value)
    {
      $checked = in_array($opt_value, $values) ? ' checked="checked"' : '';
      $opt_id = $id .'_'. $this->_normalizeId($opt_value);
      $opt_attr = $attr . ' id="'. $opt_id .'"';
      $s .= '<input type="checkbox" name="'. $name .'['. $opt_value .']" value="'. htmlspecialchars($opt_value) .'"'. $opt_attr . $checked.' />
             <label for="'. $opt_id .'">'. $opt_name .'</label>';
    }
    return $s;
  }
}
