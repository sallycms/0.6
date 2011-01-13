<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup form
 */
abstract class sly_Form_Widget extends sly_Form_ElementBase {
	protected $namespace;

	public function __construct($name, $label, $value, $id, $allowedAttributes, $namespace) {
		parent::__construct($name, $label, $value, $id, $allowedAttributes);
		$this->namespace = 'sly.form.widget.'.$namespace;
	}

	public function getWidgetID() {
		$registry = sly_Core::getTempRegistry();
		$key      = $this->namespace.'.counter';

		if (!$registry->has($key)) {
			$registry->set($key, 1);
		}

		return $registry->get($key);
	}

	public function consumeWidgetID() {
		$registry = sly_Core::getTempRegistry();
		$key      = $this->namespace.'.counter';

		$registry->set($key, $this->getWidgetID() + 1);
	}
}
