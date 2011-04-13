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
 * Business Model Klasse für Artikel
 *
 * @author christoph@webvariants.de
 */
class sly_Model_Article extends sly_Model_Base_Article {

	public function isStartPage() {
		return $this->getStartpage() == 1;
	}

	/**
	 * returns the category id
	 * @return int
	 */
	public function getCategoryId()
	{
		return $this->isStartPage() ? $this->getId() : $this->getParentId();
	}
	
	/**
	 * returns true if the articletype is set
	 *
	 * @return boolean
	 */
	public function hasType() {
		return !empty($this->type);
	}
	
	/**
	 * returns the template name of the template associated with the articletype of this article
	 *
	 * @return string the template name
	 */
	public function getTemplateName() {
		return sly_Service_Factory::getArticleTypeService()->getTemplate($this->_type);
	}
}
