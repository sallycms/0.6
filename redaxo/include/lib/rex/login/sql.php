<?php


/**
 * Klasse zum handling des Login/Logout-Mechanismuses
 *
 * @package redaxo4
 * @version svn:$Id$
 */

class rex_login_sql extends rex_sql
{
  function isValueOf($feld, $prop)
  {
    if ($prop == '')
    {
      return true;
    }
    else
    {
      if ($feld == 'rights')
        return strpos($this->getValue($feld), '#' . $prop . '#') !== false;
      else
        return strpos($this->getValue($feld), $prop) !== false;
    }
  }

  function getUserLogin()
  {
    return $this->getValue('login');
  }

  function isAdmin()
  {
    return $this->hasPerm('admin[]');
  }

  function hasPerm($perm)
  {
    return $this->isValueOf('rights', $perm);
  }

  function hasCategoryPerm($category_id)
  {
    return $this->isAdmin() || $this->hasPerm('csw[0]') || $this->hasPerm('csr[' . $category_id . ']') || $this->hasPerm('csw[' . $category_id . ']');
  }
  
  function hasStructurePerm()
  {
    return $this->isAdmin() || strpos($this->getValue("rights"), "#csw[") !== false || strpos($this->getValue("rights"), "#csr[") !== false;
  }
  
}
