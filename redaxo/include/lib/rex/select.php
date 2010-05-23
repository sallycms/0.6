<?php

/**
 * Klasse zur Erstellung eines HTML-Pulldown-Menues (Select-Box)
 *
 * @package redaxo4
 */

class rex_select
{
	public $attributes;
	public $options;
	public $option_selected;

	public function __construct()
	{
		$this->init();
	}

	public function init()
	{
		$this->attributes = array();
		$this->resetSelected();
		$this->setName('standard');
		$this->setSize('5');
		$this->setMultiple(false);
	}

	public function setAttribute($name, $value)
	{
		$this->attributes[$name] = $value;
	}

	/**
	 * @return boolean
	 */
	public function delAttribute($name)
	{
		if ($this->hasAttribute($name)) {
			unset($this->attributes[$name]);
			return true;
		}
		
		return false;
	}

	/**
	 * @return boolean
	 */
	public function hasAttribute($name)
	{
		return isset($this->attributes[$name]);
	}

	/**
	 * @return mixed
	 */
	public function getAttribute($name, $default = '')
	{
		if ($this->hasAttribute($name)) {
			return $this->attributes[$name];
		}
		
		return $default;
	}

	public function setMultiple($multiple)
	{
		if ($multiple) $this->setAttribute('multiple', 'multiple');
		else $this->delAttribute('multiple');
	}

	public function setName($name)
	{
		$this->setAttribute('name', $name);
	}

	public function setId($id)
	{
		$this->setAttribute('id', $id);
	}

	/**
	 * select style
	 * Es ist möglich sowohl eine Styleklasse als auch einen Style zu uebergeben.
	 *
	 * Aufrufbeispiel:
	 * $sel_media->setStyle('class="inp100"');
	 * und/oder
	 * $sel_media->setStyle("width:150px;");
	 */
	public function setStyle($style)
	{
		if (strpos($style, 'class=') !== false) {
			if (preg_match('/class=["\']?([^"\']*)["\']?/i', $style, $matches)) {
				$this->setAttribute('class', $matches[1]);
			}
		}
		else {
			$this->setAttribute('style', $style);
		}
	}

	public function setSize($size)
	{
		$this->setAttribute('size', (int) $size);
	}

	public function setSelected($selected)
	{
		if (!is_array($selected)) $selected = array($selected);
		
		foreach ($selected as $sectvalue) {
			$this->option_selected[] = htmlspecialchars($sectvalue);
		}
	}

	public function resetSelected()
	{
		$this->option_selected = array();
	}

	/**
	 * Fügt eine Option hinzu
	 */
	public function addOption($name, $value, $id = 0, $re_id = 0)
	{
		$this->options[$re_id][] = array($name, $value, $id);
	}

	/**
	 * Fügt ein Array von Optionen hinzu, dass eine mehrdimensionale Struktur hat.
	 *
	 * Dim   Wert
	 * 0.    Name
	 * 1.    Value
	 * 2.    Id
	 * 3.    Re_Id
	 * 4.    Selected
	 *
	 * @return boolean
	 */
	public function addOptions($options, $useOnlyValues = false)
	{
		if (!is_array($options) || empty($options)) {
			return false;
		}
		
		// Hier vorher auf is_array abfragen, da bei Strings auch die Syntax mit [] funktioniert
		// $ab = "hallo"; $ab[2] -> "l"
		$grouped = isset($options[0]) && is_array($options[0]) && isset($options[0][2]) && isset($options[0][3]);
		
		foreach ($options as $key => $option) {
			$option = (array) $option;
			
			if ($grouped) {
				$this->addOption($option[0], $option[1], $option[2], $option[3]);
				
				if (isset($option[4])) {
					$this->setSelected($option[4]);
				}
			}
			else {
				if ($useOnlyValues) {
					$this->addOption($option[0], $option[0]);
				}
				else {
					if (!isset($option[1])) $option[1] = $key;
					$this->addOption($option[0], $option[1]);
				}
			}
		}
		
		return true;
	}

	/**
	 * Fügt ein Array von Optionen hinzu, dass eine Key/Value Struktur hat.
	 * Wenn $use_keys mit false, werden die Array-Keys mit den Array-Values überschrieben
	 */
	public function addArrayOptions($options, $use_keys = true)
	{
		foreach($options as $key => $value) {
			if (!$use_keys) $key = $value;
			$this->addOption($value, $key);
		}
	}

	/**
	 * Fügt Optionen anhand der �bergeben SQL-Select-Abfrage hinzu.
	 */
	public function addSqlOptions($qry)
	{
		$sql = new rex_sql();
		$this->addOptions($sql->getArray($qry, MYSQL_NUM));
	}

	/**
	 * Fügt Optionen anhand der �bergeben DBSQL-Select-Abfrage hinzu.
	 */
	public function addDBSqlOptions($qry)
	{
		$sql = new rex_sql();
		$this->addOptions($sql->getDBArray($qry, MYSQL_NUM));
	}

	/**
	 * @return string
	 */
	public function get()
	{
		$attr = array();
		
		foreach($this->attributes as $name => $value) {
			$attr[] = trim($name).'="'.trim($value).'"';
		}

		$output = '<select '.implode(' ', $attr).'>';
		if (is_array($this->options)) $output .= $this->_outGroup(0);
		$output .= '</select>';
		
		return $output;
	}

	public function show()
	{
		print $this->get();
	}

	/**
	 * @return string
	 */
	protected function _outGroup($re_id, $level = 0)
	{
		if ($level > 100) {
			// nur mal so zu Sicherheit .. man weiß nie ;)
			print 'rex_select->_outGroup overflow @ level 100.';
			exit;
		}

		$output = '';
		$group  = $this->_getGroup($re_id);
		
		foreach ($group as $option) {
			list($name, $value, $id) = $option;
			
			$output  .= $this->_outOption($name, $value, $level);
			$subgroup = $this->_getGroup($id, true);
			
			if ($subgroup !== false) {
				$output .= $this->_outGroup($id, $level + 1);
			}
		}
		
		return $output;
	}

	/**
	 * @return string
	 */
	protected function _outOption($name, $value, $level = 0)
	{
		$name   = sly_html($name);
		$value  = sly_html($value);
		$indent = str_repeat('&nbsp;&nbsp;&nbsp;', $level);

		$selected = '';
		
		if ($this->option_selected !== null) {
			$selected = in_array($value, $this->option_selected) ? ' selected="selected"' : '';
		}

		return '<option value="'.$value.'"'.$selected.'>'.$indent.$name.'</option>';
	}

	/**
	 * @return mixed
	 */
	protected function _getGroup($re_id, $ignore_main_group = false)
	{
		if ($ignore_main_group && $re_id == 0) {
			return false;
		}

		foreach ($this->options as $gname => $group) {
			if ($gname == $re_id) return $group;
		}

		return false;
	}
}
