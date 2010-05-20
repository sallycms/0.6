<?php

class rex_form_select_element extends rex_form_element
{
  var $select;
  var $separator;

  // 1. Parameter nicht genutzt, muss aber hier stehen,
  // wg einheitlicher Konstrukturparameter
  function rex_form_select_element($tag = '', &$table, $attributes = array())
  {
    parent::rex_form_element('', $table, $attributes);

    $this->select =& new rex_select();
    $this->setSeparator('|+|');
  }

  function formatElement()
  {
    $multipleSelect = false;
    // Hier die Attribute des Elements an den Select weitergeben, damit diese angezeigt werden
    foreach($this->getAttributes() as $attributeName => $attributeValue)
    {
      if ($attributeName == 'multiple')
        $multipleSelect = true;

      $this->select->setAttribute($attributeName, $attributeValue);
    }

    if ($multipleSelect)
    {
        $this->setAttribute('name', $this->getAttribute('name').'[]');

        $selectedOptions = explode($this->separator, $this->getValue());
        if (is_array($selectedOptions) AND $selectedOptions[0] != '')
        {
          foreach($selectedOptions as $selectedOption)
          {
           $this->select->setSelected($selectedOption);
          }
        }
    }
    else
      $this->select->setSelected($this->getValue());

    $this->select->setName($this->getAttribute('name'));
    return $this->select->get();
  }

  function setSeparator($separator)
  {
    $this->separator = $separator;
  }

  function &getSelect()
  {
    return $this->select;
  }
}
