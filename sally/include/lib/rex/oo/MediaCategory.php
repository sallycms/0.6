<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Object Oriented Framework: Bildet eine Kategorie im Medienpool ab
 *
 * @ingroup redaxo2
 */
class OOMediaCategory
{
	private $id = 0;
	private $parent_id = 0;
	private $name = '';
	private $path = '';
	private $createdate = 0;
	private $updatedate = 0;
	private $createuser = '';
	private $updateuser = '';
	private $children = array();
	private $files = array();
	private $revision = 0;

	protected function __construct()
	{
		/* empty by design */
	}

	public static function _getTableName()
	{
		return sly_Core::config()->get('DATABASE/TABLE_PREFIX').'file_category';
	}

	/**
	 * @return OOMediaCategory
	 */
	public static function getCategoryById($id)
	{
		$id = (int) $id;
		if ($id <= 0) return null;

		$query  = 'SELECT * FROM '.self::_getTableName().' WHERE id = '.$id;
		$sql    = rex_sql::getInstance();
		$result = $sql->getArray($query);

		if (empty($result)) {
			return null;
		}

		$result = $result[0];
		$cat    = new self();

		$cat->id         = (int) $result['id'];
		$cat->parent_id  = (int) $result['re_id'];
		$cat->name       = $result['name'];
		$cat->path       = $result['path'];
		$cat->createdate = (int) $result['createdate'];
		$cat->updatedate = (int) $result['updatedate'];
		$cat->createuser = $result['createuser'];
		$cat->updateuser = $result['updateuser'];
		$cat->children   = null;
		$cat->files      = null;

		return $cat;
	}

	public static function getRootCategories()
	{
		$query    = 'SELECT id FROM '.self::_getTableName().' WHERE re_id = 0 ORDER BY name';
		$sql      = rex_sql::getInstance();
		$result   = $sql->getArray($query);
		$rootCats = array();

		if (is_array($result)) {
			foreach ($result as $line) {
				$rootCats[] = self::getCategoryById($line['id']);
			}
		}

		return $rootCats;
	}

	/**
	 * @return array
	 */
	public static function getCategoryByName($name)
	{
		$query  = 'SELECT id FROM '.self::_getTableName().' WHERE name = "'.$name.'"';
		$sql    = rex_sql::getInstance();
		$result = $sql->getArray($query);
		$cats   = array();

		if (is_array($result)) {
			foreach ($result as $line) {
				$cats[] = self::getCategoryById($line['id']);
			}
		}

		return $cats;

	}

	public function toString()
	{
		return 'OOMediaCategory, "'.$this->getId().'", "'.$this->getName().'"'."<br/>\n";
	}

	public function __toString()
	{
		return $this->toString();
	}

	public function getId()         { return $this->id;         }
	public function getName()       { return $this->name;       }
	public function getPath()       { return $this->path;       }
	public function getUpdateUser() { return $this->updateuser; }
	public function getUpdateDate() { return $this->updatedate; }
	public function getCreateUser() { return $this->createuser; }
	public function getCreateDate() { return $this->createdate; }
	public function getParentId()   { return $this->parent_id;  }

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getParent()
	{
		return self::getCategoryById($this->getParentId());
	}

	/**
	 * Get an array of all parentCategories.
	 * Returns an array of OOMediaCategory objects sorted by $prior.
	 */
	public function getParentTree()
	{
		$tree = array();

		if ($this->path) {
			$explode = explode('|', $this->path);

			if (is_array($explode)) {
				foreach ($explode as $var) {
					if (empty($var)) continue;
					$tree[] = self::getCategoryById($var);
				}
			}
		}

		return $tree;
	}

	/**
	 * Checks if $anObj is in the parent tree of the object
	 */
	public function inParentTree($anObj)
	{
		$tree = $this->getParentTree();

		foreach ($tree as $treeObj) {
			if ($treeObj == $anObj) {
				return true;
			}
		}

		return false;
	}

	public function getChildren()
	{
		if ($this->children === null) {
			$this->children = array();

			$qry    = 'SELECT id FROM '.self::_getTableName().' WHERE re_id = '.$this->getId().' ORDER BY name';
			$sql    = rex_sql::getInstance();
			$result = $sql->getArray($qry);

			if (is_array($result)) {
				foreach ($result as $row) {
					$this->children[] = self::getCategoryById($row['id']);
				}
			}
		}

		return $this->children;
	}

	public function countChildren()
	{
		return count($this->getChildren());
	}

	public function getMedia()
	{
		if ($this->files === null) {
			$this->files = array();

			$qry    = 'SELECT id FROM '.OOMedia::_getTableName().' WHERE category_id = '.$this->getId();
			$sql    = rex_sql::getInstance();
			$result = $sql->getArray($qry);

			if (is_array($result)) {
				foreach ($result as $line) {
					$this->files[] = OOMedia::getMediaById($line['id']);
				}
			}
		}

		return $this->files;
	}

	public function countMedia()
	{
		return count($this->getFiles());
	}

	public function isHidden()
	{
		trigger_error('Using OOMediaCategory::isHidden() is useless. It never worked.', E_USER_WARNING);
		return false; // this field never existed in the database
	}

	public function isRootCategory()
	{
		return $this->hasParent() === false;
	}

	public function isParent($mediaCat)
	{
		if (is_int($mediaCat)) {
			return $mediaCat == $this->getParentId();
		}
		elseif (self::isValid($mediaCat)) {
			return $this->getParentId() == $mediaCat->getId();
		}

		return null;
	}

	public static function isValid($obj)
	{
		return $obj instanceof self;
	}

	public function hasParent()
	{
		return $this->getParentId() != 0;
	}

	public function hasChildren()
	{
		return count($this->getChildren()) > 0;
	}

	public function hasMedia()
	{
		return count($this->getMedia()) > 0;
	}

	/**
	 * @return Returns <code>true</code> on success or <code>false</code> on error
	 */
	public function save()
	{
		$sql = rex_sql::getInstance();
		$sql->setTable(self::_getTableName());
		$sql->setValue('re_id', $this->getParentId());
		$sql->setValue('name', $this->getName());
		$sql->setValue('path', $this->getPath());
		$sql->setValue('revision', $this->revision);

		if ($this->getId() !== null) {
			$sql->addGlobalUpdateFields();
			$sql->setWhere('id = '.$this->getId().' LIMIT 1');
			return $sql->update();
		}
		else {
			$sql->addGlobalCreateFields();
			return $sql->insert();
		}
	}

	/**
	 * @return Returns <code>true</code> on success or <code>false</code> on error
	 */
	public function delete($recurse = false)
	{
		// Rekursiv löschen?

		if (!$recurse && $this->hasChildren()) {
			return false;
		}

		if ($recurse) {
			$children = $this->getChildren();

			foreach ($children as $child) {
				if (!$child->delete($recurse)) return false;
			}
		}

		// Alle Dateien löschen

		if ($this->hasMedia()) {
			$files = $this->getMedia();

			foreach ($files as $file) {
				if (!$file->delete()) return false;
			}
		}

		$qry = 'DELETE FROM '.self::_getTableName().' WHERE id = '.$this->getId().' LIMIT 1';
		$sql = rex_sql::getInstance();
		$sql->setQuery($qry);

		return !$sql->hasError() || $sql->getRows() != 1;
	}
}
