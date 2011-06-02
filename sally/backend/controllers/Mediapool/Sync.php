<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Controller_Mediapool_Sync extends sly_Controller_Mediapool {
	public function index() {
		$diff = $this->getFileDiff();

		if (empty($diff)) {
			$this->info = $this->t('sync_no_diffs');
			$this->render('mediapool/notices.phtml');
		}
		else {
			$this->render('mediapool/sync.phtml', array('diffFiles' => $diff));
		}
	}

	public function sync() {
		$selected = sly_postArray('sync_files', 'string');
		$title    = sly_post('ftitle', 'string');
		$diff     = $this->getFileDiff();
		$cat      = $this->getCurrentCategory();

		foreach ($selected as $file) {
			$idx = array_search($file, $diff);
			if ($idx === false) continue;

			if ($this->syncMedium($idx, $cat, $title)) {
				unset($diff[$idx]);
				$this->info = $this->t('sync_files_synced');
			}
		}

		$this->index();
	}

	protected function syncMedium($filename, $category, $title) {
		$absFile = SLY_MEDIAFOLDER.DIRECTORY_SEPARATOR.$filename;
		if (!file_exists($absFile)) {
			return false;
		}
		//get cleaned filename
		$newName = SLY_MEDIAFOLDER.DIRECTORY_SEPARATOR.$this->createFilename($filename, false);
		//move file to cleaned filename
		rename($absFile, $newName);

		// create and save the file

		$file    = $this->createFileObject($newName, null, $title, $category);
		$service = sly_Service_Factory::getService('Media_Medium');

		$service->save($file);

		// notify the system
		sly_Core::dispatcher()->notify('SLY_MEDIA_SYNCED', $file);

		// and we're done
		return true;
	}

	protected function getFilesFromFilesystem() {
		$dir = new sly_Util_Directory(SLY_MEDIAFOLDER);
		return $dir->listPlain(true, false);
	}

	protected function getFilesFromDatabase() {
		$db    = sly_DB_Persistence::getInstance();
		$files = array();

		$db->select('file', 'filename');
		foreach ($db as $row) $files[] = $row['filename'];

		return $files;
	}

	protected function getFileDiff() {
		$database   = $this->getFilesFromDatabase();
		$filesystem = $this->getFilesFromFilesystem();
		$diff       = array_diff($filesystem, $database);
		$res        = array();

		// possibly broken encoded filename + utf8 filename
		foreach ($diff as $filename) {
			$res[$filename] = $this->correctEncoding($filename);
		}

		return $res;
	}
}
