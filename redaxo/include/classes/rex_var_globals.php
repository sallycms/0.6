<?php

/**
 * REX_MODULE_ID,
 * REX_SLICE_ID,
 * REX_CTYPE_ID
 *
 * @package redaxo4
 * @version svn:$Id$
 */

class rex_var_globals extends rex_var
{
  // --------------------------------- Actions

  function getACRequestValues($REX_ACTION)
  {
    // SLICE ID im Update Mode setzen
    if($this->isEditEvent())
    {
      $REX_ACTION['EVENT'] = 'EDIT';
      $REX_ACTION['SLICE_ID'] = rex_request('slice_id', 'int');
    }
    // SLICE ID im Delete Mode setzen
    elseif ($this->isDeleteEvent())
    {
      $REX_ACTION['EVENT'] = 'DELETE';
      $REX_ACTION['SLICE_ID'] = rex_request('slice_id', 'int');
    }
    // Im Add Mode 0 setze wg auto-increment
    else
    {
      $REX_ACTION['EVENT'] = 'ADD';
      $REX_ACTION['SLICE_ID'] = 0;
    }

    // Variablen hier einfuegen, damit sie in einer
    // Aktion abgefragt werden können
    $REX_ACTION['ARTICLE_ID'] = rex_request('article_id', 'int');
    $REX_ACTION['CLANG_ID'] = rex_request('clang', 'int');
    $REX_ACTION['CTYPE_ID'] = rex_request('ctype', 'int');
    $REX_ACTION['MODULE_ID'] = rex_request('module_id', 'int');

    return $REX_ACTION;
  }

  function getACDatabaseValues($REX_ACTION, $slice_id)
  {

	$artslice = OOArticleSlice::_getSliceWhere('slice_id = $slice_id');
	$slice = Service_Factory::getService('Slice')->findById($slice_id);

    // Variablen hier einfuegen, damit sie in einer
    // Aktion abgefragt werden können
	if($artslice && $slice){
		$REX_ACTION['ARTICLE_ID'] = $artslice->getArticleId();
		$REX_ACTION['CLANG_ID'] = $artslice->getClang();
		$REX_ACTION['CTYPE_ID'] = $artslice->getCtype();
		$REX_ACTION['MODULE_ID'] = $slice->getModuleId();
		$REX_ACTION['SLICE_ID'] = $artslice->getId();
	}

    return $REX_ACTION;
  }

  function setACValues($slice_id, $REX_ACTION, $escape = false, $prependTableName = true)
  {
//    $this->setValue($sql, 'id', $REX_ACTION['SLICE_ID'], $escape, $prependTableName);
//    $this->setValue($sql, 'ctype', $REX_ACTION['CTYPE_ID'], $escape, $prependTableName);
//    $this->setValue($sql, 'modultyp_id', $REX_ACTION['MODULE_ID'], $escape, $prependTableName);
  }

  // --------------------------------- Output

  function getBEOutput($slice_id, $content)
  {
    // Modulabhängige Globale Variablen ersetzen
    //$content = str_replace('REX_MODULE_ID', $this->getValue($sql, 'modultyp_id'), $content);
    //$content = str_replace('REX_SLICE_ID', $this->getValue($sql, 'id'), $content);
    //$content = str_replace('REX_CTYPE_ID', $this->getValue($sql, 'ctype'), $content);

    return $content;
  }
}