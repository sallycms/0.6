<?php

// Stellt ein Element im Formular dar
// Nur für internes Handling!
class rex_form_element
{
  var $value;
  var $label;
  var $tag;
  var $table;
  var $attributes;
  var $separateEnding;
  var $fieldName;
  var $header;
  var $footer;
  var $prefix;
  var $suffix;
  var $notice;

  function rex_form_element($tag, &$table, $attributes = array(), $separateEnding = false)
  {
    $this->value = null;
    $this->label = '';
    $this->tag = $tag;
    $this->table =& $table;
    $this->setAttributes($attributes);
    $this->separateEnding = $separateEnding;
    $this->setHeader('');
    $this->setFooter('');
    $this->setPrefix('');
    $this->setSuffix('');
    $this->setFieldName('');
  }

  // --------- Attribute setter/getters

  function setValue($value)
  {
    $this->value = $value;
  }

  function getValue()
  {
    return $this->value;
  }

  function setFieldName($name)
  {
    $this->fieldName = $name;
  }

  function getFieldName()
  {
    return $this->fieldName;
  }

  function setLabel($label)
  {
    $this->label = $label;
  }

  function getLabel()
  {
    return $this->label;
  }

  function setNotice($notice)
  {
    $this->notice = $notice;
  }

  function getNotice()
  {
    return $this->notice;
  }

  function getTag()
  {
    return $this->tag;
  }

  function setSuffix($suffix)
  {
    $this->suffix = $suffix;
  }

  function getSuffix()
  {
    return $this->suffix;
  }

  function setPrefix($prefix)
  {
    $this->prefix = $prefix;
  }

  function getPrefix()
  {
    return $this->prefix;
  }

  function setHeader($header)
  {
    $this->header = $header;
  }

  function getHeader()
  {
    return $this->header;
  }

  function setFooter($footer)
  {
    $this->footer = $footer;
  }

  function getFooter()
  {
    return $this->footer;
  }

  function _normalizeId($id)
  {
    return preg_replace('/[^a-zA-Z\-0-9_]/i','_', $id);
  }

  function _normalizeName($name)
  {
    return preg_replace('/[^\[\]a-zA-Z\-0-9_]/i','_', $name);
  }

  function setAttribute($name, $value)
  {
    if($name == 'value')
    {
      $this->setValue($value);
    }
    else
    {
      if($name == 'id')
      {
        $value = $this->_normalizeId($value);
      }
      elseif($name == 'name')
      {
        $value = $this->_normalizeName($value);
      }

      // Wenn noch kein Label gesetzt, den Namen als Label verwenden
      if($name == 'name' && $this->getLabel() == '')
      {
        $this->setLabel($value);
      }

      $this->attributes[$name] = $value;
    }
  }

  function getAttribute($name, $default = null)
  {
    if($name == 'value')
    {
      return $this->getValue();
    }
    elseif($this->hasAttribute($name))
    {
      return $this->attributes[$name];
    }

    return $default;
  }

  function setAttributes($attributes)
  {
    foreach($attributes as $name => $value)
    {
      $this->setAttribute($name, $value);
    }
  }

  function getAttributes()
  {
    return $this->attributes;
  }

  function hasAttribute($name)
  {
    return isset($this->attributes[$name]);
  }

  function hasSeparateEnding()
  {
    return $this->separateEnding;
  }

  // --------- Element Methods

  function formatClass()
  {
    $s = '';
    $class = $this->getAttribute('class');

    if ($class != '')
    {
    	$s .= $class;
    }

    return $s;
  }

  function formatLabel()
  {
    $s = '';
    $label = $this->getLabel();

    if($label != '')
    {
      $s .= '          <label for="'. $this->getAttribute('id') .'">'. $label .'</label>'. "\n";
    }

    return $s;
  }

  function formatElement()
  {
    $attr = '';
    $value = htmlspecialchars($this->getValue());

    foreach($this->getAttributes() as $attributeName => $attributeValue)
    {
      $attr .= ' '. $attributeName .'="'. $attributeValue .'"';
    }

    if($this->hasSeparateEnding())
    {
      return '          <'. $this->getTag(). $attr .'>'. $value .'</'. $this->getTag() .'>'. "\n";
    }
    else
    {
      $attr .= ' value="'. $value .'"';
      return '          <'. $this->getTag(). $attr .' />'. "\n";
    }
  }

  function formatNotice()
  {
    $notice = $this->getNotice();
    if($notice != '')
    {
      return '<span class="rex-form-notice" id="'. $this->getAttribute('id') .'_notice">'. $notice .'</span>';
    }
    return '';
  }

  function _get()
  {
    $s = '';
		$class = ' class="rex-form-col-a';
		$formatClass = $this->formatClass();
		
		if ($formatClass != '')
			$class .= ' '.$formatClass;
			
		$class .= '"';
		    
    $s .= '        <p'.$class.'>'. "\n";
    $s .= $this->getPrefix();
    
    $s .= $this->formatLabel();
    $s .= $this->formatElement();
    $s .= $this->formatNotice();

    $s .= $this->getSuffix();
    $s .= '        </p>'. "\n";

    return $s;
  }

  function get()
  {
    $s = '';
    $s .= $this->getHeader();

		$s .= '    <div class="rex-form-row">'. "\n";
		
    $s .= $this->_get();
    
    $s .= '    </div>'. "\n";

    $s .= $this->getFooter();
    return $s;
  }

  function show()
  {
    echo $this->get();
  }
}
