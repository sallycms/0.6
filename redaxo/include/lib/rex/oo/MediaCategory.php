<?php


/**
 * Object Oriented Framework: Bildet eine Kategorie im Medienpool ab
 * @package redaxo4
 * @version svn:$Id$
 */

class OOMediaCategory
{
	// id
	private $_id = "";
	// re_id
	private $_parent_id = "";

	// name
	private $_name = "";
	// path
	private $_path = "";

	// createdate
	private $_createdate = "";
	// updatedate
	private $_updatedate = "";

	// createuser
	private $_createuser = "";
	// updateuser
	private $_updateuser = "";

	// child categories
	private $_children = "";
	// files (media)
	private $_files = "";

	private $_revision = 0;
	
	/**
	 * @access protected
	 */
	protected function __construct($id = null)
	{
		$this->getCategoryById($id);
	}

	/**
	 * @access protected
	 */
	public static function _getTableName()
	{
		global $REX;
		return $REX['TABLE_PREFIX'] . 'file_category';
	}

	/**
	 * @access public
	 */
	public static function getCategoryById($id)
	{
		$id = (int) $id;
		if (!is_numeric($id))
		{
			return null;
		}

		$query = 'SELECT * FROM ' . OOMediaCategory :: _getTableName() . ' WHERE id = ' . $id;

		$sql = rex_sql::getInstance();
		//        $sql->debugsql = true;
		$result = $sql->getArray($query);

		if (count($result) == 0)
		{
			// Zuerst einer Variable zuweisen, da RETURN BY REFERENCE
			$return = null;
			return $return;
		}

		$result = $result[0];
		$cat = new OOMediaCategory();

		$cat->_id = $result['id'];
		$cat->_parent_id = $result['re_id'];

		$cat->_name = $result['name'];
		$cat->_path = $result['path'];

		$cat->_createdate = $result['createdate'];
		$cat->_updatedate = $result['updatedate'];

		$cat->_createuser = $result['createuser'];
		$cat->_updateuser = $result['updateuser'];

		$cat->_children = null;
		$cat->_files = null;

		return $cat;
	}

	/**
	 * @access public
	 */
	public static function getRootCategories()
	{
		$qry = 'SELECT id FROM ' . OOMediaCategory :: _getTableName() . ' WHERE re_id = 0 order by name';
		$sql = rex_sql::getInstance();
		$sql->setQuery($qry);
		$result = $sql->getArray();

		$rootCats = array ();
		if (is_array($result))
		{
			foreach ($result as $line)
			{
				$rootCats[] = OOMediaCategory::getCategoryById($line['id']);
			}
		}

		return $rootCats;
	}

	/**
	 * @access public
	 */
	public static function getCategoryByName($name)
	{
		$query = 'SELECT id FROM ' . OOMediaCategory :: _getTableName() . ' WHERE name = "' . $name . '"';
		$sql = rex_sql::getInstance();
		//$sql->debugsql = true;
		$result = $sql->getArray($query);

		$media = array ();
		if (is_array($result))
		{
			foreach ($result as $line)
			{
				$media[] = & OOMediaCategory :: getCategoryById($line['id']);
			}
		}

		return $media;

	}

	/**
	 * @access public
	 */
	public function toString()
	{
		return 'OOMediaCategory, "' . $this->getId() . '", "' . $this->getName() . '"' . "<br/>\n";
	}

	public function __toString(){
		return $this->toString();
	}

	/**
	 * @access public
	 */
	public function getId()
	{
		return $this->_id;
	}

	/**
	 * @access public
	 */
	public function getName()
	{
		return $this->_name;
	}
	
	public function setName($name){
		$this->_name = $name;
	}

	/**
	 * @access public
	 */
	public function getPath()
	{
		return $this->_path;
	}

	/**
	 * @access public
	 */
	public function getUpdateUser()
	{
		return $this->_updateuser;
	}

	/**
	 * @access public
	 */
	public function getUpdateDate()
	{
		return $this->_updatedate;
	}

	/**
	 * @access public
	 */
	public function getCreateUser()
	{
		return $this->_createuser;
	}

	/**
	 * @access public
	 */
	public function getCreateDate()
	{
		return $this->_createdate;
	}

	/**
	 * @access public
	 */
	public function getParentId()
	{
		return $this->_parent_id;
	}

	/**
	 * @access public
	 */
	public function getParent()
	{
		return OOMediaCategory :: getCategoryById($this->getParentId());
	}

	/**
	 * @access public
	 * Get an array of all parentCategories.
	 * Returns an array of OORedaxo objects sorted by $prior.
	 *
	 */
	public function getParentTree()
	{
		$tree = array();
		if($this->_path)
		{
			$explode = explode('|', $this->_path);
			if(is_array($explode))
			{
				foreach($explode as $var)
				{
					if($var != '')
					{
						$tree[] = OOMediaCategory :: getCategoryById($var);
					}
				}
			}
		}
		return $tree;
	}

	/*
	 * Object Function:
	 * Checks if $anObj is in the parent tree of the object
	 */
	public function inParentTree($anObj)
	{
		$tree = $this->getParentTree();
		foreach($tree as $treeObj)
		{
			if($treeObj == $anObj)
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @access public
	 */
	public function getChildren()
	{
		if ($this->_children === null)
		{
			$this->_children = array ();
			$qry = 'SELECT id FROM ' . OOMediaCategory :: _getTableName() . ' WHERE re_id = ' . $this->getId() . ' ORDER BY name ';
			$sql = rex_sql::getInstance();
			$sql->setQuery($qry);
			$result = $sql->getArray();
			if (is_array($result))
			{
				foreach ($result as $row)
				{
					$id = $row['id'];
					$this->_children[] = OOMediaCategory::getCategoryById($id);
				}
			}
		}

		return $this->_children;
	}

	/**
	 * @access public
	 */
	public function countChildren()
	{
		return count($this->getChildren());
	}

	/**
	 * @access public
	 */
	public function getMedia()
	{
		if ($this->_files === null)
		{
			$this->_files = array ();
			$qry = 'SELECT file_id FROM ' . OOMedia :: _getTableName() . ' WHERE category_id = ' . $this->getId();
			$sql = rex_sql::getInstance();
			$sql->setQuery($qry);
			$result = $sql->getArray();
			if (is_array($result))
			{
				foreach ($result as $line)
				{
					$this->_files[] = & OOMedia :: getMediaById($line['file_id']);
				}
			}
		}

		return $this->_files;
	}

	/**
	 * @access public
	 */
	public function countMedia()
	{
		return count($this->getFiles());
	}

	/**
	 * @access public
	 */
	public function isHidden()
	{
		return $this->_hide;
	}

	/**
	 * @access public
	 */
	public function isRootCategory()
	{
		return $this->hasParent() === false;
	}

	/**
	 * @access public
	 */
	public function isParent($mediaCat)
	{
		if (is_int($mediaCat))
		{
			return $mediaCat == $this->getParentId();
		}
		elseif (OOMediaCategory :: isValid($mediaCat))
		{
			return $this->getParentId() == $mediaCat->getId();
		}
		return null;
	}

	/**
	 * @access public
	 */
	public static function isValid($mediaCat)
	{
		return is_object($mediaCat) && is_a($mediaCat, 'oomediacategory');
	}

	/**
	 * @access public
	 */
	public function hasParent()
	{
		return $this->getParentId() != 0;
	}

	/**
	 * @access public
	 */
	public function hasChildren()
	{
		return count($this->getChildren()) > 0;
	}

	/**
	 * @access public
	 */
	public function hasMedia()
	{
		return count($this->getMedia()) > 0;
	}

	/**
	 * @access public
	 * @return Returns <code>true</code> on success or <code>false</code> on error
	 */
	public function save()
	{
		$sql = rex_sql::getInstance();
		$sql->setTable($this->_getTableName());
		$sql->setValue('re_id', $this->getParentId());
		$sql->setValue('name', $this->getName());
		$sql->setValue('path', $this->getPath());
		$sql->setValue('hide', $this->isHidden());
		$sql->setValue('revision', $this->_revision);

		if ($this->getId() !== null)
		{
			$sql->addGlobalUpdateFields();
			$sql->setWhere('id=' . $this->getId() . ' LIMIT 1');
			return $sql->update();
		}
		else
		{
			$sql->addGlobalCreateFields();
			return $sql->insert();
		}
	}

	/**
	 * @access public
	 * @return Returns <code>true</code> on success or <code>false</code> on error
	 */
	public function delete($recurse = false)
	{
		// Rekursiv löschen?
		if(!$recurse && $this->hasChildren())
		{
			return false;
		}

		if ($recurse)
		{
			$childs = $this->getChildren();
			foreach ($childs as $child)
			{
				if(!$child->delete($recurse)) return false;
			}
		}

		// Alle Dateien löschen
		if ($this->hasMedia())
		{
			$files = $this->getMedia();
			foreach ($files as $file)
			{
				if(!$file->delete()) return false;
			}
		}

		$qry = 'DELETE FROM ' . $this->_getTableName() . ' WHERE id = ' . $this->getId() . ' LIMIT 1';
		$sql = rex_sql::getInstance();
		// $sql->debugsql = true;
		$sql->setQuery($qry);
		return !$sql->hasError() || $sql->getRows() != 1;
	}
}