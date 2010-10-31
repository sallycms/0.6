<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Dient zur Ausgabe des Sprachen-Blocks
 *
 * @package redaxo4
 */

// rechte einbauen
// admin[]
// clang[xx], clang[0]
// $REX['USER']->isValueOf("rights","csw[0]")

$langService = sly_Service_Factory::getService('Language');
$languages = $langService->find();

if (count($languages) > 1) {
	print '
<!-- *** OUTPUT OF CLANG-TOOLBAR - START *** -->
	<div id="rex-clang" class="rex-toolbar">
		<div class="rex-toolbar-content">
			<ul>
				<li>'.$I18N->msg('languages').' : </li>';

	$stop = false;
	$i    = 1;

	foreach ($languages as $language) {
		$clangID = $language->getId();
		if ($i == 1) {
			print '<li class="rex-navi-first rex-navi-clang-'.$clangID.'">';
		}
		else {
			print '<li class="rex-navi-clang-'.$clangID.'">';
		}

		$clangName = rex_translate($language->getName()); // enthält htmlspecialchars()

		if (!$REX['USER']->hasPerm('admin[]') && !$REX['USER']->hasPerm('clang[all]') && !$REX['USER']->hasPerm('clang['.$clangID.']')) {
			print '<span class="rex-strike">'.$clangName.'</span>';
			$stop |= $clang == $clangID;
		}
		else {
			$class = '';

			if ($clangID == $clang) {
				$class = ' class="rex-active"';
			}

			print '<a'.$class.' href="index.php?page='.$REX['PAGE'].'&amp;clang='.$clangID.$sprachen_add.'&amp;slot='.$slot.'"'.rex_tabindex().'>'.$clangName.'</a>';
		}

		print '</li>';
		++$i;
	}

	print '
		</ul>
	</div>
</div>
<!-- *** OUTPUT OF CLANG-TOOLBAR - END *** -->
';
	if ($stop) {
		$lang = $langService->findById($stop);
		throw new sly_Authorisation_Exception(sprintf(t('authorisation_exception_language_denied'), rex_translate($lang->getName())));
	}
}
else {
	$clang = 0;
}

unset($langService);
unset($languages);