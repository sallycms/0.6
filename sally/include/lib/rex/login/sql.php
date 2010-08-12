<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Klasse zum Handling des Login/Logout-Mechanismus
 *
 * @package redaxo4
 */
class rex_login_sql extends rex_sql
{
	public function isValueOf($field, $prop)
	{
		if (empty($prop)) return true;

		if ($field == 'rights') $prop = '#'.$prop.'#';
		return strpos($this->getValue($field), $prop) !== false;
	}

	public function getUserLogin()
	{
		return $this->getValue('login');
	}

	public function isAdmin()
	{
		return $this->hasPerm('admin[]');
	}

	public function hasPerm($perm)
	{
		return $this->isValueOf('rights', $perm);
	}

	public function hasCategoryPerm($category_id)
	{
		$category_id = (int) $category_id;
		return $this->isAdmin() || $this->hasPerm('csw[0]') || $this->hasPerm('csr['.$category_id.']') || $this->hasPerm('csw['.$category_id.']');
	}

	public function hasStructurePerm()
	{
		return $this->isAdmin() || strpos($this->getValue('rights'), '#csw[') !== false || strpos($this->getValue('rights'), '#csr[') !== false;
	}
}
