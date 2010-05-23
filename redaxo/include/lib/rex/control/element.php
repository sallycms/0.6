<?php

class rex_form_control_element extends rex_form_element
{
	public $saveElement;
	public $applyElement;
	public $deleteElement;
	public $resetElelement;
	public $abortElement;
	
	private $className;

	public function __construct(&$table, $saveElement = null, $applyElement = null, $deleteElement = null, $resetElement = null, $abortElement = null)
	{
		parent::__construct('', $table);

		$this->saveElement   = $saveElement;
		$this->applyElement  = $applyElement;
		$this->deleteElement = $deleteElement;
		$this->resetElement  = $resetElement;
		$this->abortElement  = $abortElement;
	}

	public function _get()
	{
		$this->className = '';
		
		$s  = $this->prepareElement($this->saveElement,   '');
		$s .= $this->prepareElement($this->applyElement,  'rex-form-submit-2');
		$s .= $this->prepareElement($this->deleteElement, 'rex-form-submit-2', 'return confirm(\'Löschen?\');');
		$s .= $this->prepareElement($this->resetElement,  'rex-form-submit-2', 'return confirm(\'Änderungen verwerfen?\');');
		$s .= $this->prepareElement($this->abortElement,  'rex-form-submit-2');

		if (!empty($s)) {
			$class = empty($this->className) ? '' : ' '.$this->className;
			$s     = '<p class="rex-form-col-a'.$class.'">'.$s.'</p>';
		}

		return $s;
	}
	
	private static function prepareElement($element, $class, $onClick = null)
	{
		if (!$element) return '';
		
		if (!$element->hasAttribute('class')) {
			$element->setAttribute('class', 'rex-form-submit '.$class);
		}
		
		if ($onClick && !$element->hasAttribute('onclick')) {
			$element->setAttribute('onclick', $onClick);
		}
		
		$this->className = $element->formatClass();
		return $element->formatElement();
	}

	public static function submitted($element)
	{
		return is_object($element) && rex_post($element->getAttribute('name'), 'string') != '';
	}

	public function saved()    { return self::submitted($this->saveElement);   }
	public function applied()  { return self::submitted($this->applyElement);  }
	public function deleted()  { return self::submitted($this->deleteElement); }
	public function resetted() { return self::submitted($this->resetElement);  }
	public function aborted()  { return self::submitted($this->abortElement);  }
}
