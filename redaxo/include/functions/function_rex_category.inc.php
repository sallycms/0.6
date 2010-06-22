<?php

/**
 * Regelt die Rechte an den einzelnen Kategorien und gibt den Pfad aus
 * Kategorien = Startartikel und Bezüge
 * @package redaxo4
 * @version svn:$Id$
 */

$KATPATH = '|'; // Standard für path Eintragungen in DB
$KATPERM = $REX['USER']->hasPerm('csw[0]') || $REX['USER']->hasPerm('admin[]');

if (!isset($KATout)) {
	$KATout = ''; // Variable definiert und vorbelegt wenn nicht existent
}

$KAT = new rex_sql();
$KAT->setQuery('SELECT catname, path FROM '.$REX['DATABASE']['TABLE_PREFIX'].'article WHERE id = '.$category_id.' AND startpage = 1 AND clang = '.$clang);

if ($KAT->getRows() != 1) {
	// Kategorie existiert nicht
	
	if ($category_id != 0) {
		$category_id = 0;
		$article_id  = 0;
	}
}
else {
	$pathElements = trim($KAT->getValue('path'), '|');
	$pathElements = empty($pathElements) ? array(): explode('|', $pathElements);
	
	// Informationen über den Pfad sammeln
	
	if (!empty($pathElements)) {
		$path         = implode(',', $pathElements);
		$query        = 'SELECT id, catname FROM '.$REX['DATABASE']['TABLE_PREFIX'].'article WHERE id IN ('.$path.') AND startpage = 1 AND clang = '.$clang;
		$pathElements = rex_sql::getArrayEx($query);
		
		foreach ($pathElements as $catID => $catName) {
			$catName = str_replace(' ', '&nbsp;', htmlspecialchars($catName));

			if ($KATPERM || $REX['USER']->hasPerm('csw['.$catID.']') || $REX['USER']->hasPerm('csr['.$catID.']'))
			{
				$KATout  .= '<li> : <a href="index.php?page=structure&amp;category_id='.$catID.'&amp;clang='.$clang.'"'.rex_tabindex().'>'.$catName.'</a></li>';
				$KATPATH .= $catID.'|';

				if ($REX['USER']->hasPerm('csw['.$catID.']')) {
					$KATPERM = true;
				}
			}
		}
	}
	
	$pathElements = null;
	unset($pathElements);

	if ($KATPERM || $REX['USER']->hasPerm('csw['.$category_id.']') || $REX['USER']->hasPerm('csr['.$category_id.']')) {
		$catName = str_replace(' ', '&nbsp;', htmlspecialchars($KAT->getValue('catname')));

		$KATout  .= '<li> : <a href="index.php?page=structure&amp;category_id='.$category_id.'&amp;clang='.$clang.'"'.rex_tabindex().'>'.$catName.'</a></li>';
		$KATPATH .= $category_id.'|';

		if ($REX['USER']->hasPerm('csw['.$category_id.']')) {
			$KATPERM = true;
		}
	}
	else {
		$category_id = 0;
		$article_id  = 0;
	}
}

$KATout = '
<!-- *** OUTPUT OF CATEGORY-TOOLBAR - START *** -->
<ul id="rex-navi-path">
	<li>'.$I18N->msg('path').'</li>
	<li> : <a href="index.php?page=structure&amp;category_id=0&amp;clang='.$clang.'"'.rex_tabindex().'>Homepage</a></li>
	'.$KATout.'
</ul>
<!-- *** OUTPUT OF CATEGORY-TOOLBAR - END *** -->
';

$KAT = null;
unset($KAT);
