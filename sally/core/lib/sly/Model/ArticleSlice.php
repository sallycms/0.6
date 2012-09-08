<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Business Model for ArticleSlices
 *
 * @author  zozi@webvariants.de
 * @ingroup model
 */
class sly_Model_ArticleSlice extends sly_Model_Base_Id implements sly_Model_ISlice {
	protected $article_id;
	protected $clang;
	protected $slot;
	protected $slice_id;
	protected $pos;
	protected $createdate;
	protected $updatedate;
	protected $createuser;
	protected $updateuser;
	protected $revision;
	protected $slice; ///< sly_Model_Slice

	protected $_attributes = array(
		'updateuser' => 'string',
		'createuser' => 'string',
		'createdate' => 'int',
		'updatedate' => 'int',
		'pos'        => 'int',
		'article_id' => 'int',
		'clang'      => 'int',
		'slot'       => 'string',
		'slice_id'   => 'int',
		'revision'   => 'int'
	); ///< array

	/**
	 *
	 * @return int
	 */
	public function getArticleId()  { return $this->article_id; }

	/**
	 *
	 * @return int
	 */
	public function getClang()      { return $this->clang;      }

	/**
	 *
	 * @return string
	 */
	public function getSlot()       { return $this->slot;       }

	/**
	 *
	 * @return int
	 */
	public function getPosition()   { return $this->pos;        }

	/**
	 * @deprecated  since 0.6
	 */
	public function getPrior()      { return $this->pos;        }

	/**
	 *
	 * @return int
	 */
	public function getSliceId()    { return $this->slice_id;   }

	/**
	 *
	 * @return int
	 */
	public function getCreateDate() { return $this->createdate; }

	/**
	 *
	 * @return int
	 */
	public function getUpdateDate() { return $this->updatedate; }

	/**
	 *
	 * @return string
	 */
	public function getCreateUser() { return $this->createuser; }

	/**
	 *
	 * @return int
	 */
	public function getUpdateUser() { return $this->updateuser; }

	/**
	 *
	 * @return int
	 */
	public function getRevision()   { return $this->revision;   }

	/**
	 *
	 * @return sly_Model_Article
	 */
	public function getArticle() {
		return sly_Util_Article::findById($this->getArticleId(), $this->getClang());
	}

	/**
	 *
	 * @return sly_Model_Slice
	 */
	public function getSlice() {
		if (empty($this->slice)) {
			$this->slice = sly_Service_Factory::getSliceService()->findById($this->getSliceId());
		}
		return $this->slice;
	}

	public function getPrevious() {
		$service = sly_Service_Factory::getArticleSliceService();
		$db      = sly_DB_Persistence::getInstance();

		return $service->findOne(sprintf('slot = %s AND pos < %d AND article_id = %d AND clang = %d ORDER BY pos DESC', $db->quote($this->getSlot()), $this->getPosition(), $this->getArticleId(), $this->getClang()));
	}

	public function getNext() {
		$service = sly_Service_Factory::getArticleSliceService();
		$db      = sly_DB_Persistence::getInstance();

		return $service->findOne(sprintf('slot = %s AND pos > %d AND article_id = %d AND clang = %d ORDER BY pos ASC', $db->quote($this->getSlot()), $this->getPosition(), $this->getArticleId(), $this->getClang()));
	}

	public function getModule() {
		return $this->getSlice()->getModule();
	}

	public function setModule($module) {
		$slice = &$this->getSlice();
		$slice->setModule($module);
		sly_Service_Factory::getSliceService()->save($slice);
	}

	/**
	 * @param int $updatedate
	 */
	public function setUpdateDate($updatedate) {
		$this->updatedate = (int) $updatedate;
	}

	/**
	 * @param string $updateuser
	 */
	public function setUpdateUser($updateuser) {
		$this->updateuser = $updateuser;
	}

	/**
	 * @param int $createdate
	 */
	public function setCreateDate($createdate) {
		$this->createdate = (int) $createdate;
	}

	/**
	 * @param string $createuser
	 */
	public function setCreateUser($createuser) {
		$this->createuser = $createuser;
	}

	/**
	 *
	 * @param int $position
	 */
	public function setPosition($position) {
		$this->pos = (int) $position;
	}

	/**
	 * @param  string $finder
	 * @param  string $value
	 * @return sly_Model_SliceValue
	 */
	public function addValue($finder, $value = null) {
		return $this->getSlice()->addValue($finder, $value);
	}

	public function setValues($values = array()) {
		return $this->getSlice()->setValues($values);
	}

	/**
	 * @param  string $type
	 * @param  string $finder
	 * @return mixed
	 */
	public function getValue($finder) {
		return $this->getSlice()->getValue($finder);
	}

	public function getValues() {
		return $this->getSlice()->getValues();
	}

	/**
	 * @return int
	 */
	public function flushValues() {
		$this->getSlice()->flushValues();
	}

	/**
	 * get the rendered output
	 *
	 * @return string
	 */
	public function getOutput() {
		$values   = $this->getValues();
		$renderer = new sly_Slice_Renderer($this->getModule(), $values);
		$output   = $renderer->renderOutput($this);
		return $output;
	}

	/**
	 * drop slice from serialized instance
	 */
	public function __sleep() {
		$this->slice = null;
	}
}
