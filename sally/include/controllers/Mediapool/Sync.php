<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Mediapool_Sync extends sly_Controller_Mediapool {
	public function index() {
		global $I18N;

		$diff = $this->getFileDiff();

		if (empty($diff)) {
			$this->info = $I18N->msg('pool_sync_no_diffs');
			$this->render('views/mediapool/notices.phtml');
		}
		else {
			$this->render('views/mediapool/sync.phtml');
		}
	}

	public function sync() {
		global $I18N;

		$selected = sly_postArray('sync_files', 'string');
		$title    = sly_post('ftitle', 'string');
		$diff     = $this->getFileDiff();
		$cat      = $this->getCurrentCategory();

		foreach ($selected as $file) {
			$idx = array_search($file, $diff);
			if ($idx === false) continue;

			if ($this->syncMedium($file, $cat, $title)) {
				unset($diff[$idx]);
				$this->info = $I18N->msg('pool_sync_files_synced');
			}
		}

		$this->index();
	}

	protected function syncMedium($filename, $category, $title) {
		global $REX;

		$absFile = $REX['MEDIAFOLDER'].'/'.$filename;

		if (!file_exists($absFile)) {
			return false;
		}

		// create and save the file

		$file    = $this->createFileObject($absFile, null, $title, $category);
		$service = sly_Service_Factory::getService('Media_Medium');

		$service->save($file);

		// notify the system
		sly_Core::dispatcher()->notify('SLY_MEDIA_SYNCED', $file);

		// and we're done
		return true;
	}

	protected function getFilesFromFilesystem() {
		global $REX;

		$dir   = new sly_Util_Directory($REX['MEDIAFOLDER']);
		$files = array();
		$temp  = $REX['TEMP_PREFIX'];
		$tLen  = strlen($temp);

		foreach ($dir->listPlain(true, false) as $file) {
			// don't sync temporary files
			if (strlen($file) < $tLen || substr($file, 0, $tLen) != $temp) $files[] = $file;
		}

		return $files;
	}

	protected function getFilesFromDatabase() {
		$db    = sly_DB_Persistence::getInstance();
		$files = array();

		$db->select('file', 'filename');
		foreach ($db as $row) $files[] = $row['filename'];

		return $files;
	}

	protected function getFileDiff() {
		$database  = $this->getFilesFromDatabase();
		$filsystem = $this->getFilesFromFilesystem();

		return array_diff($filsystem, $database);
	}
}
