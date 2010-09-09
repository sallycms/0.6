<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Funktionensammlung für den Medienpool
 *
 * @package redaxo4
 */
function sly_mediapool_get_dimensions($width, $height, $maxWidth, $maxHeight)
{
	if ($width > $maxWidth) {
		$factor  = (float) $maxWidth / $width;
		$width   = $maxWidth;
		$height *= $factor;
	}

	if ($height > $maxHeight) {
		$factor  = (float) $maxHeight / $height;
		$height  = $maxHeight;
		$width  *= $factor;
	}

	return array(ceil($width), ceil($height));
}

/**
 * Erstellt einen Filename der eindeutig ist für den Medienpool
 * @param $filename Dateiname
 */
function rex_mediapool_filename($filename, $doSubindexing = true)
{
	global $REX;

	// ----- neuer filename und extension holen
	$newFilename = strtolower($filename);
	$newFilename = str_replace(array('ä','ö', 'ü', 'ß'), array('ae', 'oe', 'ue', 'ss'), $newFilename);
	$newFilename = preg_replace('/[^a-zA-Z0-9.\-\+]/', '_', $newFilename);

	if (strrpos($newFilename, '.') != '') {
		$newName = substr($newFilename, 0, strlen($newFilename)-(strlen($newFilename)-strrpos($newFilename, '.')));
		$newExt  = substr($newFilename, strrpos($newFilename,'.'), strlen($newFilename)-strrpos($newFilename, '.'));
	}
	else {
		$newName = $newFilename;
		$newExt  = '';
	}

	// ---- ext checken - alle Scriptendungen rausfiltern

	if (in_array($newExt, $REX['MEDIAPOOL']['BLOCKED_EXTENSIONS'])) {
		$newName .= $newExt;
		$newExt   = '.txt';
	}

	$newFilename = $newName.$newExt;

	if ($doSubindexing) {
		// ----- Datei schon vorhanden -> Namen ändern -> _1 ..

		if (file_exists($REX['MEDIAFOLDER'].'/'.$newFilename)) {
			$cnt = 0;

			do {
				++$cnt;
				$newFilename = $newName.'_'.$cnt.$newExt;
			}
			while (file_exists($REX['MEDIAFOLDER'].'/'.$newName.'_'.$cnt.$newExt));
		}
	}

	return $newFilename;
}

/**
 * Holt ein upgeloadetes File und legt es in den Medienpool
 * Dabei wird kontrolliert ob das File schon vorhanden ist und es
 * wird eventuell angepasst, weiterhin werden die Fileinformationen übergeben
 *
 * @param $FILE
 * @param $rex_file_category
 * @param $fileInfos  enthält bereits escapte Infos zur Datei wie den Titel
 * @param $userlogin
*/
function rex_mediapool_saveMedia($fileData, $rex_file_category, $fileInfos, $userlogin = null, $doSubindexing = true)
{
	global $REX, $I18N;

	$rex_file_category = (int) $rex_file_category;

	$id = rex_sql::fetch('id', 'file_category', 'id = '.$rex_file_category);

	if ($id === false) {
		$rex_file_category = 0;
	}

	$isFileUpload = isset($fileData['tmp_name']);

	if ($isFileUpload) {
		$doSubindexing = true;
	}

	$filename    = $fileData['name'];
	$filesize    = $fileData['size'];
	$filetype    = $fileData['type'];
	$newFilename = rex_mediapool_filename($filename, $doSubindexing);
	$message     = '';

	// ----- alter/neuer Dateiname

	$srcFile = $REX['MEDIAFOLDER'].'/'.$filename;
	$dstFile = $REX['MEDIAFOLDER'].'/'.$newFilename;
	$success = true;
	$level   = error_reporting(0);

	// Datei verschieben

	if ($isFileUpload) {
		if (!move_uploaded_file($fileData['tmp_name'], $dstFile)) {
			$message .= $I18N->msg('pool_file_movefailed');
			$success  = false;
		}
	}
	else { // Filesync?
		if (!rename($srcFile, $dstFile)) {
			$message .= $I18N->msg('pool_file_movefailed');
			$success  = false;
		}
	}

	// Datensatz anlegen

	if ($success) {
		chmod($dstFile, $REX['FILEPERM']);

		// Bildgröße ermitteln
		$size = getimagesize($dstFile);

		if ($filetype == '' && isset($size['mime'])) {
			$filetype = $size['mime'];
		}

		$sql = new rex_sql();
		$sql->setTable('file', true);
		$sql->setValue('filetype', $sql->escape($filetype));
		$sql->setValue('title', $fileInfos['title']); // Magic Quotes von REDAXO!
		$sql->setValue('filename', $sql->escape($newFilename));
		$sql->setValue('originalname', $sql->escape($filename));
		$sql->setValue('filesize', $filesize);
		$sql->setValue('category_id', $rex_file_category);

		if ($size) {
			$sql->setValue('width', $size[0]);
			$sql->setValue('height', $size[1]);
		}

		$sql->addGlobalCreateFields($userlogin);
		$sql->addGlobalUpdateFields($userlogin);
		$sql->insert();

		$message .= $I18N->msg('pool_file_added');
	}

	error_reporting($level);

	$return['title']        = $fileInfos['title'];
	$return['type']         = $filetype;
	$return['msg']          = $message;
	$return['ok']           = $success;
	$return['filename']     = $newFilename;
	$return['old_filename'] = $filename;
	$return['id']           = $sql->getLastId();

	if ($size) {
		$return['width']  = $size[0];
		$return['height'] = $size[1];
	}

	if ($success) {
		rex_register_extension_point('MEDIA_ADDED', '', $return);
	}

	return $return;
}


/**
 * Holt ein upgeloadetes File und legt es in den Medienpool
 * Dabei wird kontrolliert ob das File schon vorhanden ist und es
 * wird eventuell angepasst, weiterhin werden die Fileinformationen übergeben
 *
 * @param $fileData   ungenutzt, nur aus Kompatibilität zu REDAXO enthalten
 * @param $fileInfos  enthält bereits escapte Infos zur Datei wie den Titel
 * @param $userlogin
*/
function rex_mediapool_updateMedia($fileData, &$fileInfos, $userlogin = null)
{
	global $REX, $I18N;

	$return = array();

	$sql = new rex_sql();
	$sql->setTable('file', true);
	$sql->setWhere('file_id = '.intval($fileInfos['file_id']));
	$sql->setValue('title', $fileInfos['title']); // Magic Quotes von REDAXO!
	$sql->setValue('category_id', (int) $fileInfos['rex_file_category']);

	$msg     = '';
	$updated = false;
	$level   = error_reporting(0);
	$return['ok'] = true;

	if (!empty($_FILES['file_new']['name']) && $_FILES['file_new']['name'] != 'none') {
		$filename = $_FILES['file_new']['tmp_name'];
		$filetype = $_FILES['file_new']['type'];
		$filesize = (int) $_FILES['file_new']['size'];

		if ($filetype == $fileInfos['filetype'] || OOMedia::compareImageTypes($filetype, $fileInfos['filetype'])) {
			$targetFile = $REX['MEDIAFOLDER'].'/'.$fileInfos['filename'];

			if (move_uploaded_file($filename, $targetFile) || copy($filename, $targetFile)) {
				$return['msg']         = $I18N->msg('pool_file_changed');
				$fileInfos['filetype'] = addslashes($filetype); // Magic Quotes von REDAXO simulieren
				$fileInfos['filesize'] = $filesize;

				$sql->setValue('filetype', $fileInfos['filetype']); // Magic Quotes von REDAXO!
				$sql->setValue('filesize', (int) $fileInfos['filesize']);

				if ($size = getimagesize($REX['MEDIAFOLDER'].'/'.$fileInfos['filename'])) {
					$sql->setValue('width', $size[0]);
					$sql->setValue('height', $size[1]);
				}

				chmod($REX['MEDIAFOLDER'].'/'.$fileInfos['filename'], $REX['FILEPERM']);

				if (class_exists('Thumbnail')){
					Thumbnail::deleteCache($fileInfos['filename']);
				}

				$updated = true;
			}
			else {
				$return['msg'] = $I18N->msg('pool_file_upload_error');
				$return['ok']  = false;
			}
		}
		else {
			$return['msg'] = $I18N->msg('pool_file_upload_errortype');
			$return['ok']  = false;
		}
	}

	error_reporting($level);

	$sql->addGlobalUpdateFields();
	$sql->update();

	if (!isset($return['msg'])) {
		$return['msg']      = $I18N->msg('pool_file_infos_updated');
		$return['filename'] = $fileInfos['filename'];
		$return['filetype'] = $fileInfos['filetype'];
		$return['file_id']  = $fileInfos['file_id'];
	}

	return $return;
}

/**
 * Synchronisiert die Datei $physical_filename des Mediafolders in den
 * Medienpool
 *
 * @param $physical_filename
 * @param $category_id
 * @param $title
 * @param $filesize
 * @param $filetype
 */
function rex_mediapool_syncFile($physical_filename, $category_id, $title, $filesize = null, $filetype = null, $doSubindexing = false)
{
	global $REX;

	$abs_file = $REX['MEDIAFOLDER'].'/'.$physical_filename;

	if (!file_exists($abs_file)) {
		return false;
	}

	if (empty($filesize)) {
		$filesize = filesize($abs_file);
	}

	if (empty($filetype) && function_exists('mime_content_type')) {
		$filetype = mime_content_type($abs_file);
	}

	$file = array();
	$file['name'] = $physical_filename;
	$file['size'] = $filesize;
	$file['type'] = $filetype;

	$fileInfos = array();
	$fileInfos['title'] = $title;

	$return = rex_mediapool_saveMedia($file, $category_id, $fileInfos, null, false);
	return $return['ok'];
}

/**
 * Fügt einen rex_select Objekt die hierarchische Medienkategorien struktur
 * hinzu
 *
 * @param $select
 * @param $mediacat
 * @param $mediacat_ids
 * @param $groupName
 */
function rex_mediapool_addMediacatOptions(&$select, &$mediacat, &$mediacat_ids, $groupName = '')
{
	global $REX;

	if (empty($mediacat)) {
		return;
	}

	$mname = $mediacat->getName();

	if ($REX['USER']->hasPerm('advancedMode[]')) {
		$mname .= ' ['.$mediacat->getId().']';
	}

	$mediacat_ids[] = $mediacat->getId();
	$select->addOption($mname, $mediacat->getId(), $mediacat->getId(), $mediacat->getParentId());
	$children = $mediacat->getChildren();

	if (is_array($children)) {
		foreach ($children as $child) {
			rex_mediapool_addMediacatOptions($select, $child, $mediacat_ids, $mname);
		}
	}
}

/**
 * Fügt einem rex_select-Objekt die hierarchische Medienkategorienstruktur
 * unter Berücksichtigung der Medienkategorierechte hinzu
 *
 * @param $select
 * @param $mediacat
 * @param $mediacat_ids
 * @param $groupName
 */
function rex_mediapool_addMediacatOptionsWPerm( &$select, &$mediacat, &$mediacat_ids, $groupName = '')
{
	global $PERMALL, $REX;

	if (empty($mediacat)) {
		return;
	}

	$mname = $mediacat->getName();

	if ($REX['USER']->hasPerm('advancedMode[]')) {
		$mname .= ' ['.$mediacat->getId().']';
	}

	$mediacat_ids[] = $mediacat->getId();

	if ($PERMALL || $REX['USER']->hasPerm('media['.$mediacat->getId().']')) {
		$select->addOption($mname, $mediacat->getId(), $mediacat->getId(), $mediacat->getParentId());
	}

	$children = $mediacat->getChildren();

	if (is_array($children)) {
		foreach ($children as $child) {
			rex_mediapool_addMediacatOptionsWPerm($select, $child, $mediacat_ids, $mname);
		}
	}
}

/**
 * Ausgabe des Medienpool Formulars
 */
function rex_mediapool_Mediaform($form_title, $button_title, $rex_file_category, $file_chooser, $close_form)
{
	global $I18N, $REX, $subpage, $ftitle, $warning, $info;

	$s = '';

	$cats_sel = new rex_select();
	$cats_sel->setStyle('class="rex-form-select"');
	$cats_sel->setSize(1);
	$cats_sel->setName('rex_file_category');
	$cats_sel->setId('rex_file_category');
	$cats_sel->addOption($I18N->msg('pool_kats_no'), '0');

	$mediacat_ids = array();
	$rootCat      = 0;

	if ($rootCats = OOMediaCategory::getRootCategories()) {
		foreach ($rootCats as $rootCat) {
			rex_mediapool_addMediacatOptionsWPerm($cats_sel, $rootCat, $mediacat_ids);
		}
	}

	$cats_sel->setSelected($rex_file_category);

	if (!empty($warning)) {
		$s      .= rex_warning($warning);
		$warning = '';
	}

	if (!empty($info)) {
		$s   .= rex_info($info);
		$info = '';
	}

	if (!isset($ftitle)) {
		$ftitle = '';
	}

	$add_file  = '';
	$maxPOST   = rex_ini_get('post_max_size');
	$maxUpload = rex_ini_get('upload_max_filesize');
	$maxSize   = min(array($maxPOST, $maxUpload));

	if ($file_chooser) {
		$devInfos = '';

		if ($REX['USER']->hasPerm('advancedMode[]')) {
			$devInfos =
'<span class="rex-form-notice">
	'.$I18N->msg('phpini_settings').':<br />
	'.((rex_ini_get('file_uploads') == 0) ? '<span>'.$I18N->msg('pool_upload').':</span> <em>'.$I18N->msg('pool_upload_disabled').'</em><br />' : '').'
	<span>'.$I18N->msg('pool_max_uploadsize').':</span> '.OOMedia::_getFormattedSize($maxSize).'<br />
	<span>'.$I18N->msg('pool_max_uploadtime').':</span> '.rex_ini_get('max_input_time').'s
</span>';
		}

		$add_file = '
<div class="rex-form-row">
	<p class="rex-form-file">
		<label for="file_new">'.$I18N->msg('pool_file_file').'</label>
		<input type="hidden" name="MAX_FILE_SIZE" value="'.$maxSize.'" />
		<input class="rex-form-file" type="file" id="file_new" name="file_new" size="30" />
		'.$devInfos.'
	</p>
</div>';
	}

	$add_submit = '';

	if (rex_session('media[opener_input_field]') != '') {
		$add_submit = '<input type="submit" class="rex-form-submit" name="saveandexit" value="'.$I18N->msg('pool_file_upload_get').'" />';
	}

	$s .= '
<div class="rex-form" id="rex-form-mediapool-other">
	<form action="index.php?page=mediapool&amp;subpage='.$subpage.'&amp;media_method=add_file" method="post" enctype="multipart/form-data">
		<fieldset class="rex-form-col-1 num1">
			<legend>'.$form_title.'</legend>
			<div class="rex-form-wrapper">

				<div class="rex-form-row">
					<p class="rex-form-text">
						<label for="ftitle">'.$I18N->msg('pool_file_title').'</label>
						<input class="rex-form-text" type="text" size="20" id="ftitle" name="ftitle" value="'.htmlspecialchars(stripslashes($ftitle)).'" />
					</p>
				</div>

				<div class="rex-form-row">
					<p class="rex-form-select">
						<label for="rex_file_category">'.$I18N->msg('pool_file_category').'</label>
						'.$cats_sel->get().'
					</p>
				</div>

				<div class="rex-clearer"></div>';

	$s .= rex_register_extension_point('MEDIA_FORM_ADD', '');
	$s .= $add_file .'
				<div class="rex-form-row">
					<p class="rex-form-submit">
						<input class="rex-form-submit" type="submit" name="save" value="'.$button_title.'" />
						'.$add_submit.'
					</p>
				</div>

				<div class="rex-clearer"></div>
			</div>
		</fieldset>';

	if ($close_form) {
		$s .= '
	</form>
</div>
';
	}

	return $s;
}

/**
 * Ausgabe des Medienpool Upload-Formulars
 */
function rex_mediapool_Uploadform($rex_file_category)
{
	global $I18N;
	return rex_mediapool_Mediaform($I18N->msg('pool_file_insert'), $I18N->msg('pool_file_upload'), $rex_file_category, true, true);
}

/**
 * Ausgabe des Medienpool Sync-Formulars
 */
function rex_mediapool_Syncform($rex_file_category)
{
	global $I18N;
	return rex_mediapool_Mediaform($I18N->msg('pool_sync_title'), $I18N->msg('pool_sync_button'), $rex_file_category, false, false);
}
