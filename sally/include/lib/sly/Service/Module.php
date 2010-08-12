<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Service-Klasse für Module
 *
 * @author christoph@webvariants.de
 */
class sly_Service_Module {
	protected $list    = null;
	protected $refresh = null;

	public function __construct() {
		$config        = sly_Core::config();
		$this->list    = $config->get('MODULES/list');
		$this->refresh = $config->get('MODULES/last_refresh');

		if ($this->list === null)    $this->list    = array();
		if ($this->refresh === null) $this->refresh = 0;
	}

	public function getModules() {
		$result = array();

		foreach ($this->list as $name => $params) {
			$result[$name] = isset($params['input']) ? $params['input']['title'] : $params['output']['title'];
		}

		return $result;
	}

	public function getFolder() {
		$dir = sly_Util_Directory::join(SLY_BASE, 'develop/modules');
		if (!is_dir($dir) && !@mkdir($dir, 0777, true)) throw new sly_Exception('Konnte Modul-Verzeichnis '.$dir.' nicht erstellen.');
		return $dir;
	}

	public function exists($name) {
		return isset($this->list[$name]);
	}

	public function getModuleFiles($absolute = true) {
		$dir    = new sly_Util_Directory($this->getFolder());
		$files  = $dir->listPlain(true, false, false, $absolute);
		$retval = array();

		foreach ($files as $filename) {
			if (!preg_match('#^\.(?:input|output)\.php$#i', $filename, $matches)) {
				$retval[] = $filename;
			}
		}

		natsort($retval);
		return $retval;
	}

	public function getKnownModuleFiles() {
		$known = array();

		foreach ($this->list as $data) {
			if (!empty($data['input']))  $known[] = $data['input']['filename'];
			if (!empty($data['output'])) $known[] = $data['output']['filename'];
		}

		natsort($known);
		return $known;
	}

	public function getTitle($name, $default = '') {
		return $this->get($name, 'title', $default);
	}

	public function getActions($name) {
		return sly_makeArray($this->get($name, 'actions', array()));
	}

	public function getInputFilename($name, $fullPath) {
		return $this->get($name, 'filename', false, 'input');
	}

	public function getOutputFilename($name, $fullPath) {
		return $this->get($name, 'filename', false, 'output');
	}

	public function get($name, $key = null, $default = null, $type = null) {
		if ($key == 'name') return $name;

		$this->refresh();

		// Modul vorhanden?

		if (!isset($this->list[$name])) return false;
		if ($type !== null && !isset($this->list[$name][$type])) return $default;

		// Passenden Typ ermitteln, input hat Vorrang

		if ($type === null) {
			$type = isset($this->list[$name]['input']) ? 'input' : 'output';
		}

		// Alle Daten zurückgeben?

		$data = $this->list[$name][$type];
		if ($key === null) return $data;

		// Erst auf Standard-Parameter testen, dann die custom params testen.

		return (isset($data[$key]) ? $data[$key] : (isset($data['params'][$key]) ? $data['params'][$key] : $default));
	}

	public function getContent($name, $type) {
		if ($type != 'input' && $type != 'output') return false;
		if (!isset($this->list[$name][$type])) return false;

		$filename = sly_Util_Directory::join($this->getFolder(), $this->list[$name][$type]['filename']);
		return file_exists($filename) ? file_get_contents($filename) : false;
	}

	public function needsRefresh() {
		if ($this->refresh == 0) return true;

		$files = $this->getModuleFiles();
		$known = $this->getKnownModuleFiles();

		return
			/* Dateien?      */ count($files) > 0 &&
			/* neuere Daten? */ (max(array_map('filemtime', $files)) > $this->refresh ||
			/* neue Dateien? */ count(array_diff(array_map('basename', $files), $known)) > 0);
	}

	public function refresh($force = false) {
		$refresh = $force || $this->needsRefresh();
		if (!$refresh) return true;

		$files    = $this->getModuleFiles();
		$newData  = array();
		$oldData  = $this->list;
		$modified = false;

		foreach ($files as $file) {
			$basename = basename($file);
			$mtime    = filemtime($file);
			$type     = substr($basename, -10) == '.input.php' ? 'input' : 'output';

			// Wenn sich die Datei nicht geändert hat, können wir die bekannten
			// Daten einfach 1:1 übernehmen.

			$known = $this->findModule($basename, $oldData);

			if ($known && $oldData[$known][$type]['mtime'] == $mtime) {
				$newData[$known][$type] = $oldData[$known][$type];
				continue;
			}

			$parser = new sly_Util_ParamParser($file);
			$name   = $parser->get('name', null);

			if ($name === null) {
				//trigger_error('Modul '.$basename.' enthält keinen internen Namen und kann daher nicht geladen werden.', E_USER_WARNING);
				continue;
			}

			if (isset($newData[$name][$type])) {
				//trigger_error('Modul '.$basename.' enthält keinen eindeutigen Namen.', E_USER_WARNING);
				continue;
			}

			if (preg_match('#[^a-z0-9_.-]#i', $name)) {
				trigger_error('Der Name des Moduls '.$basename.' enthält ungültige Zeichen.', E_USER_WARNING);
				continue;
			}

			$data = $parser->get();
			unset($data['name'], $data['title'], $data['actions']);

			$newData[$name][$type] = array(
				'filename' => $basename,
				'title'    => $parser->get('title', basename($file)),
				'actions'  => $parser->get('actions', array()),
				'params'   => $data,
				'mtime'    => $mtime
			);

			$this->deleteSliceCache($name);
			$modified = true;
		}

		$this->list    = $newData;
		$this->refresh = time();

		// Wir müssen die Daten erst aus der Konfiguration entfernen, falls sich
		// der Datentyp geändert hat. Ansonsten wird sich sly_Configuration z. B.
		// weigern, aus einem Skalar ein Array zu machen.

		if ($modified) {
			$config = sly_Core::config();
			$config->remove('MODULES');
			$config->setLocal('MODULES/list', $this->list);
			$config->setLocal('MODULES/last_refresh', $this->refresh);
		}
	}

	protected function deleteSliceCache($moduleName) {
		$sql = sly_DB_Persistence::getInstance();
		$sql->select('article_slice', 'slice_id', array('module' => $moduleName));

		foreach ($sql as $row) {
			include_once SLY_INCLUDE_PATH.'/functions/function_rex_generate.inc.php';
			rex_deleteCacheSliceContent((int) $row['slice_id']);
		}
	}

	public function findModule($filename, $data = null) {
		$data     = $data === null ? $this->list : $data;
		$filename = basename($filename);

		foreach ($data as $name => $properties) {
			if (isset($properties['input']) && $properties['input']['filename'] == $filename) return $name;
			if (isset($properties['output']) && $properties['output']['filename'] == $filename) return $name;
		}

		return false;
	}
}
