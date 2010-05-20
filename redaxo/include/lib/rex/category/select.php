<?php

################ Class Kategorie Select
class rex_category_select extends rex_select
{
  var $ignore_offlines;
  var $clang;
  var $check_perms;

  function rex_category_select($ignore_offlines = false, $clang = false, $check_perms = true, $add_homepage = true)
  {
    $this->ignore_offlines = $ignore_offlines;
    $this->clang = $clang;
    $this->check_perms = $check_perms;

    if($add_homepage)
      $this->addOption('Homepage', 0);

    if ($cats = OOCategory :: getRootCategories($ignore_offlines, $clang))
    {
      foreach ($cats as $cat)
      {
        $this->addCatOption($cat);
      }
    }

    parent::rex_select();
  }

  function addCatOption($cat)
  {
    global $REX;
    if (empty ($cat))
    {
      return;
    }

    if(!$this->check_perms ||
        $this->check_perms && $REX['USER']->hasCategoryPerm($cat->getId()))
    {
      $this->addOption($cat->getName(), $cat->getId(), $cat->getId(), $cat->getParentId());
      $childs = $cat->getChildren($this->ignore_offlines, $this->clang);
      if (is_array($childs))
      {
        foreach ($childs as $child)
        {
          $this->addCatOption($child);
        }
      }
    }
  }
}