<?php

/**
 * Klasse zum Erstellen von Formularen
 * @package redaxo4
 */
class rex_form
{
	public $name;
	public $tableName;
	public $method;
	public $fieldset;
	public $whereCondition;
	public $elements;
	public $params;
	public $mode;
	public $sql;
	public $debug;
	public $applyUrl;
	public $message;
	public $warning;
	public $divId;

	function __construct($tableName, $fieldset, $whereCondition, $method = 'post', $debug = false)
	{
		global $REX;

		if (!in_array($method, array('post', 'get'))) {
			trigger_error('rex_form: 3. Parameter darf nur die Werte "post" oder "get" annehmen!', E_USER_ERROR);
		}

		$this->name           = md5($tableName.$whereCondition.$method);
		$this->method         = $method;
		$this->tableName      = $tableName;
		$this->elements       = array();
		$this->params         = array();
		$this->whereCondition = $whereCondition;
		$this->divId          = 'rex-addon-editmode';
		
		$this->addFieldset($fieldset);

		if ($REX['REDAXO']) {
			$this->loadBackendConfig();
		}

		$this->setMessage('');

		$this->sql = new rex_sql();
		$this->sql->setQuery('SELECT * FROM '.$tableName.' WHERE '.$this->whereCondition.' LIMIT 2');

		$numRows = $this->sql->getRows();
		
		if ($numRows == 0) {
			// Kein Datensatz gefunden => Mode: Add
			$this->setEditMode(false);
		}
		elseif ($numRows == 1) {
			// Ein Datensatz gefunden => Mode: Edit
			$this->setEditMode(true);
		}
		else {
			trigger_error('rex_form: Die gegebene WHERE-Bedingung führt nicht zu einem eindeutigen Datensatz!', E_USER_ERROR);
		}
	}

	public function init()
	{
		// nichts tun
	}

	public static function factory($tableName, $fieldset, $whereCondition, $method = 'post', $debug = false, $class = null)
	{
		// keine spezielle Klasse angegeben -> Default-Klasse verwenden?
		if (!$class) {
			$class = rex_register_extension_point('REX_FORM_CLASSNAME', 'rex_form', array(
				'tableName'      => $tableName,
				'fieldset'       => $fieldset,
				'whereCondition' => $whereCondition,
				'method'         => $method,
				'debug'          => $debug
			));
		}

		return new $class($tableName, $fieldset, $whereCondition, $method, $debug);
	}

	public function loadBackendConfig()
	{
		global $I18N;

		$saveLabel   = $I18N->msg('form_save');
		$applyLabel  = $I18N->msg('form_apply');
		$deleteLabel = $I18N->msg('form_delete');
		$resetLabel  = $I18N->msg('form_reset');
		$abortLabel  = $I18N->msg('form_abort');
		$func        = rex_request('func', 'string');

		$this->addParam('page', rex_request('page', 'string'));
		$this->addParam('subpage', rex_request('subpage', 'string'));
		$this->addParam('func', $func);
		$this->addParam('list', rex_request('list', 'string'));

		$saveElement   = null;
		$applyElement  = null;
		$deleteElement = null;
		$resetElement  = null;  // immer null in REDAXO 4.2.1
		$abortElement  = null;  // immer null in REDAXO 4.2.1
		
		if (!empty($saveLabel)) {
			$saveElement = $this->addInputField('submit', 'save', $saveLabel, array('internal::useArraySyntax' => false), false);
		}

		if ($func == 'edit') {
			if (!empty($applyLabel))  $applyElement  = $this->addInputField('submit', 'apply', $applyLabel, array('internal::useArraySyntax' => false), false);
			if (!empty($deleteLabel)) $deleteElement = $this->addInputField('submit', 'delete', $deleteLabel, array('internal::useArraySyntax' => false), false);
		}

		if ($saveElement || $applyElement || $deleteElement) {
			$this->addControlField($saveElement, $applyElement, $deleteElement, $resetElement, $abortElement);
		}
	}

	/**
	 * Gibt eine URL zurück
	 */
	public function getUrl($params = array(), $escape = true)
	{
		$params = array_merge($this->getParams(), $params);
		$params['form'] = $this->getName();
		$paramString = http_build_query($params, '', $escape ? '&amp;' : '&');
		
		return 'index.php?'. $paramString;
	}

	public function addFieldset($fieldset)
	{
		$this->fieldset = $fieldset;
	}

	public function addField($tag, $name, $value = null, $attributes = array(), $addElement = true)
	{
		$element = $this->createElement($tag, $name, $value, $attributes);
		if ($addElement) $this->addElement($element);
		return $element;
	}

	public function addInputField($type, $name, $value = null, $attributes = array(), $addElement = true)
	{
		$attributes['type'] = $type;
		return $this->addField('input', $name, $value, $attributes, $addElement);
	}

	public function addTextField($name, $value = null, $attributes = array())
	{
		if (!isset($attributes['class'])) $attributes['class'] = 'rex-form-text';
		return $this->addInputField('text', $name, $value, $attributes);
	}

	public function addReadOnlyTextField($name, $value = null, $attributes = array())
	{
		$attributes['readonly'] = 'readonly';
		if (!isset($attributes['class'])) $attributes['class'] = 'rex-form-read';
		return $this->addInputField('text', $name, $value, $attributes);
	}

	public function addReadOnlyField($name, $value = null, $attributes = array())
	{
		$attributes['internal::fieldSeparateEnding'] = true;
		$attributes['internal::noNameAttribute']     = true;
		if (!isset($attributes['class'])) $attributes['class'] = 'rex-form-read';
		return $this->addField('span', $name, $value, $attributes, true);
	}

	public function addHiddenField($name, $value = null, $attributes = array())
	{
		return $this->addInputField('hidden', $name, $value, $attributes, true);
	}

	public function addCheckboxField($name, $value = null, $attributes = array())
	{
		$attributes['internal::fieldClass'] = 'rex_form_checkbox_element';
		if (!isset($attributes['class'])) $attributes['class'] = 'rex-form-checkbox rex-form-label-right';
		return $this->addField('', $name, $value, $attributes);
	}

	public function addRadioField($name, $value = null, $attributes = array())
	{
		if (!isset($attributes['class'])) $attributes['class'] = 'rex-form-radio';
		$attributes['internal::fieldClass'] = 'rex_form_radio_element';
		return $this->addField('radio', $name, $value, $attributes);
	}

	public function addTextAreaField($name, $value = null, $attributes = array())
	{
		$attributes['internal::fieldSeparateEnding'] = true;
		
		if (!isset($attributes['cols']))  $attributes['cols']  = 50;
		if (!isset($attributes['rows']))  $attributes['rows']  = 6;
		if (!isset($attributes['class'])) $attributes['class'] = 'rex-form-textarea';

		return $this->addField('textarea', $name, $value, $attributes);
	}

	public function addSelectField($name, $value = null, $attributes = array())
	{
		if(!isset($attributes['class'])) $attributes['class'] = 'rex-form-select';
		$attributes['internal::fieldClass'] = 'rex_form_select_element';
		return $this->addField('', $name, $value, $attributes, true);
	}

	public function addMediaField($name, $value = null, $attributes = array())
	{
		$attributes['internal::fieldClass'] = 'rex_form_widget_media_element';
		return $this->addField('', $name, $value, $attributes, true);
	}

	public function addMedialistField($name, $value = null, $attributes = array())
	{
		$attributes['internal::fieldClass'] = 'rex_form_widget_medialist_element';
		return $this->addField('', $name, $value, $attributes, true);
	}

	public function addLinkmapField($name, $value = null, $attributes = array())
	{
		$attributes['internal::fieldClass'] = 'rex_form_widget_linkmap_element';
		return $this->addField('', $name, $value, $attributes, true);
	}

	public function addControlField($saveElement = null, $applyElement = null, $deleteElement = null, $resetElement = null, $abortElement = null)
	{
		return $this->addElement(new rex_form_control_element($this, $saveElement, $applyElement, $deleteElement, $resetElement, $abortElement));
	}

	public function addParam($name, $value)
	{
		$this->params[$name] = $value;
	}

	public function getParams()
	{
		return $this->params;
	}

	public function getParam($name, $default = null)
	{
		return isset($this->params[$name]) ? $this->params[$name] : $default;
	}

	public function addElement($element)
	{
		$this->elements[$this->fieldset][] = $element;
		return $element;
	}

	public function createElement($tag, $name, $value, $attributes = array())
	{
		$id        = $this->tableName.'_'.$this->fieldset.'_'.$name;
		$postValue = $this->elementPostValue($this->getFieldsetName(), $name);
		
		// evtl. POST-Werte wieder übernehmen (auch externe Werte überschreiben)
		
		if ($postValue !== null) {
			$value = stripslashes($postValue);
		}

		// Wert aus der DB nehmen, falls keiner extern und keiner im POST angegeben
		
		if ($value === null && $this->sql->getRows() == 1) {
			$value = $this->sql->getValue($name);
		}

		if (!isset($attributes['internal::useArraySyntax'])) {
			$attributes['internal::useArraySyntax'] = true;
		}

		// eigentlichen Feldnamen nochmals speichern
		
		$fieldName = $name;
		
		if ($attributes['internal::useArraySyntax'] === true) {
			$name = $this->fieldset.'['.$name.']';
		}
		elseif ($attributes['internal::useArraySyntax'] === false) {
			$name = $this->fieldset.'_'.$name;
		}
		
		unset($attributes['internal::useArraySyntax']);

		$class          = 'rex_form_element';
		$separateEnding = false;
		$internal_attr  = array('name' => $name);
		
		if (isset($attributes['internal::fieldClass'])) {
			$class = $attributes['internal::fieldClass'];
			unset($attributes['internal::fieldClass']);
		}

		if (isset($attributes['internal::fieldSeparateEnding'])) {
			$separateEnding = $attributes['internal::fieldSeparateEnding'];
			unset($attributes['internal::fieldSeparateEnding']);
		}
		
		if (isset($attributes['internal::noNameAttribute'])) {
			$internal_attr = array();
			unset($attributes['internal::noNameAttribute']);
		}

		// 1. Array: Eigenschaften, die via Parameter Überschrieben werden können/dürfen
		// 2. Array: Eigenschaften, via Parameter
		// 3. Array: Eigenschaften, die hier fest definiert sind / nicht veränderbar via Parameter
		
		$attributes = array_merge(array('id' => $id), $attributes, $internal_attr);
		$element    = new $class($tag, $this, $attributes, $separateEnding);
		$element->setFieldName($fieldName);
		$element->setValue($value);
		
		return $element;
	}

	public function setEditMode($isEditMode)
	{
		$this->mode = $isEditMode ? 'edit' : 'add';
	}

	public function isEditMode()
	{
		return $this->mode == 'edit';
	}

	public function setApplyUrl($url)
	{
		if (is_array($url)) $url = $this->getUrl($url, false);
		$this->applyUrl = $url;
	}

	public static function isHeaderElement($element)
	{
		return is_object($element) && $element->getTag() == 'input' && $element->getAttribute('type') == 'hidden';
	}

	public static function isFooterElement($element)
	{
		return self::isControlElement($element);
	}

	public static function isControlElement($element)
	{
		return $element instanceof rex_form_control_element;
	}

	public function getHeaderElements()
	{
		return $this->getElements(array(__CLASS__, 'isHeaderElement'));
	}

	public function getFooterElements()
	{
		return $this->getElements(array(__CLASS__, 'isFooterElement'));
	}
	
	protected function getElements($predicate)
	{
		$elements = array();
		
		foreach ($this->elements as $fieldsetName => $fieldsetElementsArray) {
			foreach ($fieldsetElementsArray as $element) {
				if ($predicate($element)) $elements[] = $element;
			}
		}
		
		return $elements;
	}

	public function getFieldsetName()
	{
		return $this->fieldset;
	}

	public function getFieldsets()
	{
		return array_keys($this->elements);
	}

	public function getFieldsetElements()
	{
		$fieldsetElements = array();
		
		foreach ($this->elements as $fieldsetName => $fieldsetElementsArray) {
			foreach ($fieldsetElementsArray as $element) {
				if (self::isHeaderElement($element)) continue;
				if (self::isFooterElement($element)) continue;

				$fieldsetElements[$fieldsetName][] = $element;
			}
		}
		
		return $fieldsetElements;
	}

	public function getControlElement()
	{
		$controlElements = $this->getElements(array(__CLASS__, 'isControlElement'));
		return empty($controlElements) ? null : reset($controlElements);
	}

	public function getElement($fieldsetName, $elementName)
	{
		$normalizedName = rex_form_element::_normalizeName($fieldsetName.'['.$elementName.']');
		return $this->_getElement($fieldsetName, $normalizedName);
	}

	protected function _getElement($fieldsetName, $elementName)
	{
		if (!is_array($this->elements[$fieldsetName])) {
			return null;
		}
		
		foreach ($this->elements[$fieldsetName] as $idx => $element) {
			if ($element->getAttribute('name') == $elementName) {
				return $element;
			}
		}
		
		return null;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setWarning($warning)
	{
		$this->warning = $warning;
	}

	public function getWarning()
	{
		return rex_request($this->getName().'_warning', 'string');
	}

	public function setMessage($message)
	{
		$this->message = $message;
	}

	public function getMessage()
	{
		return rex_request($this->getName().'_msg', 'string');
	}

	/**
	 * Callbackfunktion, damit in Subklassen der Value noch beeinflusst werden kann
	 * kurz vor'm Löschen
	 */
	public function preDelete($fieldsetName, $fieldName, $fieldValue, $deleteSql)
	{
		return $fieldValue;
	}

	/**
	 * Callbackfunktion, damit in subklassen der Value noch beeinflusst werden kann
	 * kurz vor'm Speichern
	 */
	public function preSave($fieldsetName, $fieldName, $fieldValue, $saveSql)
	{
		global $REX;

		static $setOnce = false;

		if (!$setOnce) {
			$fieldnames = $this->sql->getFieldnames();

			if (in_array('updateuser', $fieldnames)) $saveSql->setValue('updateuser', $REX['USER']->getValue('login'));
			if (in_array('updatedate', $fieldnames)) $saveSql->setValue('updatedate', time());

			if (!$this->isEditMode()) {
				if (in_array('createuser', $fieldnames)) $saveSql->setValue('createuser', $REX['USER']->getValue('login'));
				if (in_array('createdate', $fieldnames)) $saveSql->setValue('createdate', time());
			}
			
			$setOnce = true;
		}

		return $fieldValue;
	}

	/**
	 * Callbackfunktion, damit in Subklassen der Value noch beeinflusst werden kann
	 * wenn das Feld mit Datenbankwerten angezeigt wird
	 */
	public function preView($fieldsetName, $fieldName, $fieldValue)
	{
		return $fieldValue;
	}

	public function fieldsetPostValues($fieldsetName)
	{
		// Name normalisieren, da der gepostete Name auch zuvor normalisiert wurde
		$normalizedFieldsetName = rex_form_element::_normalizeName($fieldsetName);
		return rex_post($normalizedFieldsetName, 'array');
	}

	public function elementPostValue($fieldsetName, $fieldName, $default = null)
	{
		$fields = $this->fieldsetPostValues($fieldsetName);
		if (isset($fields[$fieldName])) return $fields[$fieldName];
		return $default;
	}

	/**
	 * Validiert die Eingaben.
	 * Gibt true zurück wenn alles ok war, false bei einem allgemeinen Fehler oder
	 * einen String mit einer Fehlermeldung.
	 *
	 * Eingaben sind via
	 *   $el    = $this->getElement($fieldSetName, $fieldName);
	 *   $val   = $el->getValue();
	 * erreichbar.
	*/
	public function validate()
	{
		return true;
	}

	/**
	 * Speichert das Formular
	 *
	 * Gibt true zurück wenn alles ok war, false bei einem allgemeinen Fehler oder
	 * einen String mit einer Fehlermeldung.
	 */
	public function save()
	{
		// trigger extensions point
		// Entscheiden zwischen UPDATE <-> CREATE via editMode möglich
		// Falls die Extension FALSE zurückgibt, nicht speicher,
		// um hier die Möglichkeit offen zu haben eigene Validierungen/Speichermechanismen zu implementieren
		
		if (rex_register_extension_point('REX_FORM_'.strtoupper($this->getName()).'_SAVE', '', array('form' => $this)) === false) {
			return;
		}

		$sql = rex_sql::getInstance();
		$sql->setTable($this->tableName);

		foreach ($this->getFieldsets() as $fieldsetName) {
			// POST-Werte ermitteln
			$fieldValues = $this->fieldsetPostValues($fieldsetName);
			
			foreach($fieldValues as $fieldName => $fieldValue) {
				// Callback, um die Values vor dem Speichern noch beeinflussen zu können
				$fieldValue = $this->preSave($fieldsetName, $fieldName, $fieldValue, $sql);

				if (is_array($fieldValue)) {
					$fieldValue = implode('|+|', $fieldValue);
				}

				// Element heraussuchen
				$element = $this->getElement($fieldsetName, $fieldName);

				// Den POST-Wert als Value in das Feld speichern
				// Da generell alles von REDAXO escaped wird, hier slashes entfernen
				$element->setValue(stripslashes($fieldValue));

				// Den POST-Wert in die DB speichern (inkl. Slashes)
				$sql->setValue($fieldName, $fieldValue);
			}
		}

		if ($this->isEditMode()) {
			$sql->setWhere($this->whereCondition);
			return $sql->update();
		}
		
		return $sql->insert();
	}

	public function delete()
	{
		$deleteSql = rex_sql::getInstance();
		$deleteSql->debugsql = $this->debug;
		$deleteSql->setTable($this->tableName);
		$deleteSql->setWhere($this->whereCondition);

		foreach($this->getFieldsets() as $fieldsetName) {
			// POST-Werte ermitteln
			$fieldValues = $this->fieldsetPostValues($fieldsetName);
			
			foreach ($fieldValues as $fieldName => $fieldValue) {
				// Callback, um die Values vor dem Löschen noch beeinflussen zu können
				$fieldValue = $this->preDelete($fieldsetName, $fieldName, $fieldValue, $deleteSql);

				// Element heraussuchen
				$element = $this->getElement($fieldsetName, $fieldName);

				// Den POST-Wert als Value in das Feld speichern
				// Da generell alles von REDAXO escaped wird, hier Slashes entfernen
				$element->setValue(stripslashes($fieldValue));
			}
		}

		return $deleteSql->delete();
	}

	public function redirect($listMessage = '', $listWarning = '', $params = array())
	{
		if (!empty($listMessage)) {
			$listName = sly_request('list', 'string');
			$params[$listName.'_msg'] = $listMessage;
		}

		if (!empty($listWarning)) {
			$listName = sly_request('list', 'string');
			$params[$listName.'_warning'] = $listWarning;
		}

		header('Location: '.$this->applyUrl.http_build_query($params, '', '&'));
		exit();
	}

	public function get()
	{
		global $I18N;

		$this->init();
		$this->setApplyUrl($this->getUrl(array('func' => ''), false));
		$this->handleSubmittedForm();
		
		// Parameter dem Formular hinzufügen
		
		foreach($this->getParams() as $name => $value) {
			$this->addHiddenField($name, $value, array('internal::useArraySyntax' => 'none'));
		}

		$s       = '';
		$warning = $this->getWarning();
		$message = $this->getMessage();
		
		if (!empty($warning)) {
			$s .= '  '.rex_warning($warning);
		}
		elseif (!empty($message)) {
			$s .= '  '.rex_info($message);
		}

		$s .= '<div id="'.$this->divId.'" class="rex-form">';

		$i          = 0;
		$addHeaders = true;
		$fieldsets  = $this->getFieldsetElements();
		$last       = count($fieldsets);

		$s .= '  <form action="index.php" method="'. $this->method .'">';
		
		foreach ($fieldsets as $fieldsetName => $fieldsetElements) {
			$s .= '<fieldset class="rex-form-col-1">';
			$s .= '<legend>'.sly_html($fieldsetName).'</legend>';
			$s .= '<div class="rex-form-wrapper">';

			// Die HeaderElemente nur im 1. Fieldset ganz am Anfang einfügen
			
			if ($i == 0 && $addHeaders) {
				foreach ($this->getHeaderElements() as $element) {
					// Callback
					$element->setValue($this->preView($fieldsetName, $element->getFieldName(), $element->getValue()));
					// HeaderElemente immer ohne <p>
					$s .= $element->formatElement();
				}
				
				$addHeaders = false;
			}

			foreach ($fieldsetElements as $element) {
				// Callback
				$element->setValue($this->preView($fieldsetName, $element->getFieldName(), $element->getValue()));
				$s .= $element->get();
			}

			// Die FooterElemente nur innerhalb des letzten Fieldsets
			if (($i + 1) == $last) {
				foreach ($this->getFooterElements() as $element) {
					// Callback
					$element->setValue($this->preView($fieldsetName, $element->getFieldName(), $element->getValue()));
					$s .= $element->get();
				}
			}

			$s .= '</div></fieldset>';
			$i++;
		}

		$s .= '</form></div>';
		return $s;
	}

	public function show()
	{
		print $this->get();
	}
	
	protected function handleSubmittedForm()
	{
		global $I18N;
		
		$controlElement = $this->getControlElement();

		if ($controlElement !== null) {
			if ($controlElement->saved()) {
				// speichern und umleiten
				// Nachricht in der Liste anzeigen
				
				if (($result = $this->validate()) === true && ($result = $this->save()) === true)
					$this->redirect($I18N->msg('form_saved'));
				elseif (is_string($result) && $result != '')
					// Falls ein Fehler auftritt, das Formular wieder anzeigen mit der Meldung
					$this->setWarning($result);
				else
					$this->setWarning($I18N->msg('form_save_error'));
			}
			elseif ($controlElement->applied()) {
				// speichern und wiederanzeigen
				// Nachricht im Formular anzeigen
				
				if (($result = $this->validate()) === true && ($result = $this->save()) === true)
					$this->setMessage($I18N->msg('form_applied'));
				elseif (is_string($result) && $result != '')
					$this->setWarning($result);
				else
					$this->setWarning($I18N->msg('form_save_error'));
			}
			elseif ($controlElement->deleted()) {
				// speichern und wiederanzeigen
				// Nachricht in der Liste anzeigen
				
				if (($result = $this->delete()) === true)
					$this->redirect($I18N->msg('form_deleted'));
				elseif (is_string($result) && $result != '')
					$this->setWarning($result);
				else
					$this->setWarning($I18N->msg('form_delete_error'));
			}
			elseif ($controlElement->resetted()) {
				// verwerfen und wiederanzeigen
				// Nachricht im Formular anzeigen
				$this->setMessage($I18N->msg('form_resetted'));
			}
			elseif ($controlElement->aborted()) {
				// verwerfen und umleiten
				// Nachricht in der Liste anzeigen
				$this->redirect($I18N->msg('form_resetted'));
			}
		}
	}
}
