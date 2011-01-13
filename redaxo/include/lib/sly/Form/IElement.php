<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

interface sly_Form_IElement
{
	public function getID();
	public function getName();
	public function getLabel();
	public function getValue();
	public function render();
	public function addClass($className);
	public function getAttribute($name);
	public function setAttribute($name, $value);
	public function removeAttribute($name);
	public function addOuterClass($className);
	public function getOuterClass();
	public function isContainer();
}
