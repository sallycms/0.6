<?php

// Stellt ein Element im Formular dar
// Nur für internes Handling!

abstract class rex_form_element // seit Sally abstract
{
	public $value;
	public $label;
	public $tag;
	public $table; // scheinbar ungenutzt --xrstf
	public $attributes;
	public $separateEnding;
	public $fieldName;
	public $header;
	public $footer;
	public $prefix;
	public $suffix;
	public $notice;

	public function __construct($tag, &$table, $attributes = array(), $separateEnding = false)
	{
		$this->value          = null;
		$this->label          = '';
		$this->tag            = $tag;
		$this->table          = &$table;
		$this->separateEnding = $separateEnding;
		
		$this->setAttributes($attributes);
		$this->setHeader('');
		$this->setFooter('');
		$this->setPrefix('');
		$this->setSuffix('');
		$this->setFieldName('');
	}

	// Setter / Getter

	public function setValue($value)    { $this->value = $value;    }
	public function setFieldName($name) { $this->fieldName = $name; }
	public function setLabel($label)    { $this->label = $label;    }
	public function setNotice($notice)  { $this->notice = $notice;  }
	public function setSuffix($suffix)  { $this->suffix = $suffix;  }
	public function setPrefix($prefix)  { $this->prefix = $prefix;  }
	public function setHeader($header)  { $this->header = $header;  }
	public function setFooter($footer)  { $this->footer = $footer;  }

	public function getValue()     { return $this->value;     }
	public function getFieldName() { return $this->fieldName; }
	public function getLabel()     { return $this->label;     }
	public function getNotice()    { return $this->notice;    }
	public function getTag()       { return $this->tag;       }
	public function getSuffix()    { return $this->suffix;    }
	public function getPrefix()    { return $this->prefix;    }
	public function getHeader()    { return $this->header;    }
	public function getFooter()    { return $this->footer;    }

	protected static function _normalizeId($id)
	{
		return preg_replace('/[^a-z\-0-9_]/is','_', $id);
	}

	protected static function _normalizeName($name)
	{
		return preg_replace('/[^\[\]a-z\-0-9_]/is','_', $name);
	}

	public function setAttribute($name, $value)
	{
		$name  = trim($name);
		$value = trim($value);
		
		if ($name == 'value') {
			$this->setValue($value);
		}
		else {
			if ($name == 'id') {
				$value = self::_normalizeId($value);
			}
			elseif ($name == 'name') {
				$value = self::_normalizeName($value);
			}

			// Wenn noch kein Label gesetzt, den Namen als Label verwenden
			
			if ($name == 'name' && $this->getLabel() == '') {
				$this->setLabel($value);
			}

			$this->attributes[$name] = $value;
		}
	}

	public function getAttribute($name, $default = null)
	{
		if ($name == 'value') {
			return $this->getValue();
		}
		elseif ($this->hasAttribute($name)) {
			return $this->attributes[$name];
		}

		return $default;
	}

	public function setAttributes($attributes)
	{
		foreach ($attributes as $name => $value) {
			$this->setAttribute($name, $value);
		}
	}

	public function getAttributes()
	{
		return $this->attributes;
	}

	public function hasAttribute($name)
	{
		return isset($this->attributes[$name]);
	}

	public function hasSeparateEnding()
	{
		return $this->separateEnding;
	}

	public function formatClass()
	{
		return trim($this->getAttribute('class'));
	}

	public function formatLabel()
	{
		$label = $this->getLabel();

		if (!empty($label)) {
			return '<label for="'.$this->getAttribute('id').'">'.$label.'</label>';
		}

		return '';
	}

	public function formatElement()
	{
		$attr  = sly_Util_HTML::buildAttributeString($this->getAttributes());
		$value = sly_html($this->getValue());

		if ($this->hasSeparateEnding()) {
			return '<'.$this->getTag().' '.$attr.'>'.$value.'</'.$this->getTag().'>';
		}
		
		$attr .= ' value="'.$value.'"';
		return '<'.$this->getTag().$attr.' />';
	}

	public function formatNotice()
	{
		$notice = $this->getNotice();
		
		if (!empty($notice)) {
			return '<span class="rex-form-notice" id="'.$this->getAttribute('id').'_notice">'.$notice.'</span>';
		}
		
		return '';
	}

	protected function _get()
	{
		$s           = '';
		$class       = ' class="rex-form-col-a';
		$formatClass = $this->formatClass();

		if ($formatClass) $class .= ' '.$formatClass;

		$class .= '"';

		$s .= '<p'.$class.'>';
		$s .= $this->getPrefix();
		$s .= $this->formatLabel();
		$s .= $this->formatElement();
		$s .= $this->formatNotice();
		$s .= $this->getSuffix();
		$s .= '</p>';

		return $s;
	}

	public function get()
	{
		$s  = $this->getHeader();
		$s .= '<div class="rex-form-row">';
		$s .= $this->_get();
		$s .= '</div>';
		$s .= $this->getFooter();
		
		return $s;
	}

	public function show()
	{
		print $this->get();
	}
}
