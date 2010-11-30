<?php

/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Service-Klasse for Article types
 *
 * @author  zozi@webvariants.de
 * @ingroup service
 */
class sly_Service_ArticleType {

	private $data;

	public function __construct() {
		$this->data = sly_Util_YAML::load(SLY_BASE . DIRECTORY_SEPARATOR . 'develop' . DIRECTORY_SEPARATOR . 'types.yml');
	}

	public function getArticleTypes() {
		$types = array();
		foreach ($this->data as $name => $data) {
			$types[$name] = $data['title'];
		}
		return $types;
	}

	public function getTitle($articleType) {
		$this->exists($articleType, true);
		return $this->data[$articleType]['title'];
	}

	public function getTemplate($articleType) {
		$this->exists($articleType, true);
		return $this->data[$articleType]['template'];
	}

	public function exists($articleType, $throwException = false) {
		if (!array_key_exists($articleType, $this->data)) {
			if ($throwException)
				throw new sly_Exception(sprintf(t('exception_articletype_not_exists'), $articleType));
			return false;
		}
		return true;
	}

}