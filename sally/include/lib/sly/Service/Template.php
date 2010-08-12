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
 * Service-Klasse für Templates
 *
 * @author christoph@webvariants.de
 */
class sly_Service_Template {
	protected $list    = null;
	protected $refresh = null;

	public function __construct() {
		$config        = sly_Core::config();
		$this->list    = $config->get('TEMPLATES/list');
		$this->refresh = $config->get('TEMPLATES/last_refresh');

		if ($this->list === null)    $this->list    = array();
		if ($this->refresh === null) $this->refresh = 0;
	}

	public function getTemplates() {
		$result = array();
		foreach ($this->list as $name => $params) $result[$name] = $params['title'];
		return $result;
	}

	public function getFolder() {
		$dir = sly_Util_Directory::join(SLY_BASE, 'develop/templates');
		if (!is_dir($dir) && !@mkdir($dir, 0777, true)) throw new sly_Exception('Konnte Template-Verzeichnis '.$dir.' nicht erstellen.');
		return $dir;
	}

	public function getCacheFolder() {
		$dir = sly_Util_Directory::join(SLY_DYNFOLDER, 'internal/sally/templates');
		if (!is_dir($dir) && !@mkdir($dir, 0777, true)) throw new sly_Exception('Konnte Cache-Verzeichnis '.$dir.' nicht erstellen.');
		return $dir;
	}

	public function isGenerated($name) {
		if (!$this->exists($name)) return false;
		return file_exists($this->getCacheFile($name));
	}

	public function findById($id) {
		$id = (int) $id;

		foreach ($this->list as $name => $data) {
			if ($this->get($name, 'id', null) == $id) return $name;
		}

		return null;
	}

	public function generate($name) {
		if (!$this->exists($name)) return false;

		$content = $this->getContent($name);
		if ($content === false) return false;

		foreach (sly_Core::getVarTypes() as $var) {
			$content = $var->getTemplate($content);
		}

		$templateFile = $this->getCacheFile($name);
		return file_put_contents($templateFile, $content) > 0;
	}

	public function getCacheFile($name) {
		return sly_Util_Directory::join($this->getCacheFolder(), $name.'.php');
	}

	public function flush($name = null) {
		if ($name === null) {
			$dir   = new sly_Util_Directory($this->getCacheFolder());
			$files = $dir->listPlain(true, false, false, true, '');
		}
		elseif ($this->exists($name)) {
			$files = array(sly_Util_Directory::join($this->getFolder(), $this->getFilename($name)));
		}
		else {
			return false;
		}

		array_map('unlink', $files);
		return true;
	}

	public function exists($name) {
		return isset($this->list[$name]);
	}

	public function getTemplateFiles($absolute = true) {
		$dir = new sly_Util_Directory($this->getFolder());
		return $dir->listPlain(true, false, false, $absolute);
	}

	public function getKnownTemplateFiles() {
		$known = array();
		foreach ($this->list as $data) $known[] = $data['filename'];
		return $known;
	}

	public function getTitle($name, $default = '') {
		return $this->get($name, 'title', $default);
	}

	public function getClass($name, $default = '') {
		return $this->get($name, 'class', $default);
	}

	public function getSlots($name) {
		$slots = $this->get($name, 'slots', array());
		$slots = sly_makeArray($slots);
		if (empty($slots)) $slots = array(0 => 'default');
		return $slots;
	}

	public function getModules($name) {
		return $this->get($name, 'modules');
	}

	public function getFilename($name, $fullPath) {
		return $this->get($name, 'filename');
	}

	public function get($name, $key = null, $default = null) {
		if ($key == 'name') return $name;

		$this->refresh();

		// Template vorhanden?

		if (!isset($this->list[$name])) return false;

		// Alle Daten zurückgeben?

		$data = $this->list[$name];
		if ($key === null) return $data;

		// Erst auf Standard-Parameter testen, dann die custom params testen.

		return (isset($data[$key]) ? $data[$key] : (isset($data['params'][$key]) ? $data['params'][$key] : $default));
	}

	public function getContent($name) {
		if (!isset($this->list[$name])) return false;
		$filename = sly_Util_Directory::join($this->getFolder(), $this->list[$name]['filename']);
		return file_exists($filename) ? file_get_contents($filename) : false;
	}

	public function needsRefresh() {
		if ($this->refresh == 0) return true;

		$files = $this->getTemplateFiles();
		$known = $this->getKnownTemplateFiles();

		return
			/* Dateien?      */ count($files) > 0 &&
			/* neuere Daten? */ (max(array_map('filemtime', $files)) > $this->refresh ||
			/* neue Dateien? */ count(array_diff(array_map('basename', $files), $known)) > 0);
	}

	public function refresh($force = false) {
		$refresh = $force || $this->needsRefresh();
		if (!$refresh) return true;

		$files    = $this->getTemplateFiles();
		$newData  = array();
		$oldData  = $this->list;
		$modified = true;

		foreach ($files as $file) {
			$basename = basename($file);
			$mtime    = filemtime($file);

			// Wenn sich die Datei nicht geändert hat, können wir die bekannten
			// Daten einfach 1:1 übernehmen.

			$known = $this->findTemplate($basename, $oldData);

			if ($known && $oldData[$known]['mtime'] == $mtime) {
				$newData[$known] = $oldData[$known];
				continue;
			}

			$parser = new sly_Util_ParamParser($file);
			$name   = $parser->get('name', null);

			if ($name === null) {
				//trigger_error('Template '.$basename.' enthält keinen internen Namen und kann daher nicht geladen werden.', E_USER_WARNING);
				continue;
			}

			if (isset($newData[$name])) {
				//trigger_error('Template '.$basename.' enthält keinen eindeutigen Namen. Template '.$newData[$name]['filename'].' heißt bereits '.$name.'.', E_USER_WARNING);
				continue;
			}

			if (preg_match('#[^a-z0-9_.-]#i', $name)) {
				trigger_error('Der Name des Templates '.$basename.' enthält ungültige Zeichen.', E_USER_WARNING);
				continue;
			}

			$data = $parser->get();
			unset($data['name'], $data['title'], $data['class'], $data['slots'], $data['modules']);

			$newData[$name] = array(
				'filename' => $basename,
				'title'    => $parser->get('title', basename($file)),
				'class'    => $parser->get('class', null),
				'slots'    => sly_makeArray($parser->get('slots', 1)),
				'modules'  => $parser->get('modules', 'all'),
				'params'   => $data,
				'mtime'    => $mtime
			);

			if ($this->isGenerated($name)) unlink($this->getCacheFile($name));
			$modified = true;
		}

		$this->list    = $newData;
		$this->refresh = time();

		// Wir müssen die Daten erst aus der Konfiguration entfernen, falls sich
		// der Datentyp geändert hat. Ansonsten wird sich sly_Configuration z. B.
		// weigern, aus einem Skalar ein Array zu machen.

		if ($modified) {
			$config = sly_Core::config();
			$config->remove('TEMPLATES');
			$config->setLocal('TEMPLATES/list', $this->list);
			$config->setLocal('TEMPLATES/last_refresh', $this->refresh);
		}
	}

	public function findTemplate($filename, $data = null) {
		$data     = $data === null ? $this->list : $data;
		$filename = basename($filename);

		foreach ($data as $name => $properties) {
			if ($properties['filename'] == $filename) return $name;
		}

		return false;
	}

	public function hasModule($template, $ctype, $module) {
		if (!$this->exists($template)) return false;

		$modules = $this->getModules($template);
		$modules = sly_makeArray($modules);

		return
			/* keine Angabe -> alle erlaubt */ !isset($modules[$ctype]) ||
			/* 'all' oder [all] angegeben   */ sly_makeArray($modules[$ctype]) == array('all') ||
			/* Modulkennung angegeben       */ in_array($module, sly_makeArray($modules[$ctype]));
	}
}
