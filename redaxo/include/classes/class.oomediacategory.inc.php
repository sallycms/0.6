<?php


/**
 * Object Oriented Framework: Bildet eine Kategorie im Medienpool ab
 * @package redaxo4
 * @version svn:$Id$
 */

class OOMediaCategory
{
  // id
  var $_id = "";
  // re_id
  var $_parent_id = "";

  // name
  var $_name = "";
  // path
  var $_path = "";

  // createdate
  var $_createdate = "";
  // updatedate
  var $_updatedate = "";

  // createuser
  var $_createuser = "";
  // updateuser
  var $_updateuser = "";

  // child categories
  var $_children = "";
  // files (media)
  var $_files = "";

  /**
  * @access protected
  */
  function OOMediaCategory($id = null)
  {
    $this->getCategoryById($id);
  }

  /**
   * @access protected
   */
  function _getTableName()
  {
    global $REX;
    return $REX['TABLE_PREFIX'] . 'file_category';
  }

  /**
   * @access public
   */
  function & getCategoryById($id)
  {
    $id = (int) $id;
    if (!is_numeric($id))
    {
      return null;
    }

    $query = 'SELECT * FROM ' . OOMediaCategory :: _getTableName() . ' WHERE id = ' . $id;

    $sql = new rex_sql();
    //        $sql->debugsql = true;
    $result = $sql->getArray($query);

    if (count($result) == 0)
    {
      // Zuerst einer Variable zuweisen, da RETURN BY REFERENCE
      $return = null;
      return $return;
    }

    $result = $result[0];
    $cat = & new OOMediaCategory();

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
  function & getRootCategories()
  {
    $qry = 'SELECT id FROM ' . OOMediaCategory :: _getTableName() . ' WHERE re_id = 0 order by name';
    $sql = new rex_sql();
    $sql->setQuery($qry);
    $result = $sql->getArray();

    $rootCats = array ();
    if (is_array($result))
    {
      foreach ($result as $line)
      {
        $rootCats[] = & OOMediaCategory :: getCategoryById($line['id']);
      }
    }

    return $rootCats;
  }

  /**
   * @access public
   */
  function & getCategoryByName($name)
  {
    $query = 'SELECT id FROM ' . OOMediaCategory :: _getTableName() . ' WHERE name = "' . $name . '"';
    $sql = new rex_sql();
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
  function toString()
  {
    return 'OOMediaCategory, "' . $this->getId() . '", "' . $this->getName() . '"' . "<br/>\n";
  }

  /**
   * @access public
   */
  function getId()
  {
    return $this->_id;
  }

  /**
   * @access public
   */
  function getName()
  {
    return $this->_name;
  }

  /**
   * @access public
   */
  function getPath()
  {
    return $this->_path;
  }

  /**
   * @access public
   */
  function getUpdateUser()
  {
    return $this->_updateuser;
  }

  /**
   * @access public
   */
  function getUpdateDate()
  {
    return $this->_updatedate;
  }

  /**
   * @access public
   */
  function getCreateUser()
  {
    return $this->_createuser;
  }

  /**
   * @access public
   */
  function getCreateDate()
  {
    return $this->_createdate;
  }

  /**
   * @access public
   */
  function getParentId()
  {
    return $this->_parent_id;
  }

  /**
   * @access public
   */
  function getParent()
  {
    return OOMediaCategory :: getCategoryById($this->getParentId());
  }
  
  /**
   * @access public
   * Get an array of all parentCategories.
   * Returns an array of OORedaxo objects sorted by $prior.
   * 
   */
  function getParentTree()
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
  function inParentTree($anObj)
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
  function getChildren()
  {
    if ($this->_children === null)
    {
      $this->_children = array ();
      $qry = 'SELECT id FROM ' . OOMediaCategory :: _getTableName() . ' WHERE re_id = ' . $this->getId() . ' ORDER BY name ';
      $sql = new rex_sql();
      $sql->setQuery($qry);
      $result = $sql->getArray();
      if (is_array($result))
      {
        foreach ($result as $row)
        {
          $id = $row['id'];
          $this->_children[] = & OOMediaCategory :: getCategoryById($id);
        }
      }
    }

    return $this->_children;
  }

  /**
   * @access public
   */
  function countChildren()
  {
    return count($this->getChildren());
  }

  /**
   * @access public
   */
  function getMedia()
  {
    if ($this->_files === null)
    {
      $this->_files = array ();
      $qry = 'SELECT file_id FROM ' . OOMedia :: _getTableName() . ' WHERE category_id = ' . $this->getId();
      $sql = new rex_sql();
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
  function countMedia()
  {
    return count($this->getFiles());
  }

  /**
   * @access public
   */
  function isHidden()
  {
    return $this->_hide;
  }

  /**
   * @access public
   */
  function isRootCategory()
  {
    return $this->hasParent() === false;
  }

  /**
   * @access public
   */
  function isParent($mediaCat)
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
  function isValid($mediaCat)
  {
    return is_object($mediaCat) && is_a($mediaCat, 'oomediacategory');
  }

  /**
   * @access public
   */
  function hasParent()
  {
    return $this->getParentId() != 0;
  }

  /**
   * @access public
   */
  function hasChildren()
  {
    return count($this->getChildren()) > 0;
  }

  /**
   * @access public
   */
  function hasMedia()
  {
    return count($this->getMedia()) > 0;
  }

  /**
   * @access public
   * @return Returns <code>true</code> on success or <code>false</code> on error
   */
  function save()
  {
    $sql = new rex_sql();
    $sql->setTable($this->_getTableName());
    $sql->setValue('re_id', $this->getParentId());
    $sql->setValue('name', $this->getName());
    $sql->setValue('path', $this->getPath());
    $sql->setValue('hide', $this->isHidden());

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
  function delete($recurse = false)
  {
    // Rekursiv l�schen?
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
    
    // Alle Dateien l�schen
    if ($this->hasMedia())
    {
      $files = $this->getMedia();
      foreach ($files as $file)
      {
        if(!$file->delete()) return false;
      }
    }

    $qry = 'DELETE FROM ' . $this->_getTableName() . ' WHERE id = ' . $this->getId() . ' LIMIT 1';
    $sql = new rex_sql(); 
    // $sql->debugsql = true;
    $sql->setQuery($qry);
    return !$sql->hasError() || $sql->getRows() != 1;
  }

  /**
   * @access public
   * @deprecated 4.2 - 17.05.2008
   */
  function countFiles()
  {
    return $this->countMedia();
  }

  /**
   * @access public
   * @deprecated 4.2 - 17.05.2008
   */
  function hasFiles()
  {
    return $this->hasMedia();
  }

  /**
   * @access public
   * @deprecated 4.2 - 17.05.2008
   */
  function getFiles()
  {
    return $this->getMedia();
  }
}