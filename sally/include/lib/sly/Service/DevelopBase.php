<?php

abstract class sly_Service_DevelopBase {

	private $data;
	private $lastRefreshTime;

	public function getFiles($absolute = true) {
		$dir    = new sly_Util_Directory($this->getFolder());
		$files  = $dir->listPlain(true, false, false, $absolute);
		$retval = array();

		foreach ($files as $filename) {
			if ($this->isFileValid($filename)) $retval[] = $filename;
		}

		natsort($retval);
		return $retval;
	}

	public function getFolder() {
		$dir = sly_Util_Directory::join(SLY_DEVELOPFOLDER, $this->getClassIdentifier());
		if (!is_dir($dir) && !@mkdir($dir, sly_Core::config()->get('DIRPERM'), true)) throw new sly_Exception('Konnte Verzeichnis '.$dir.' nicht erstellen.');
		return $dir;
	}

	public function refresh($force = false) {
		$refresh = $force || $this->needsRefresh();
		if (!$refresh) return true;

		$files    = $this->getFiles();
		$newData  = array();
		$oldData  = $this->getData();
		$modified = false;

		foreach ($files as $file) {
			$basename = basename($file);
			$mtime    = filemtime($file);
			$type     = $this->getFileType($basename);

			// Wenn sich die Datei nicht geändert hat, können wir die bekannten
			// Daten einfach 1:1 übernehmen.

			$known = $this->find('filename', $basename, $this->getFileType($basename));

			if ($known && $oldData[$known][$type]['mtime'] == $mtime) {
				$newData[$known][$type] = $oldData[$known][$type];
				continue;
			}

			$parser = new sly_Util_ParamParser($file);
			$data   = $parser->get();
			if (empty($data) && $known) $modified = true;
			$name   = $parser->get('name', null);

			if ($name === null) {
				trigger_error($basename.' has no internal name an cannot be loaded.', E_USER_WARNING);
				continue;
			}

			if (isset($newData[$name][$type])) {
				trigger_error($basename.' has no unique name. (type: '.$type.')', E_USER_WARNING);
				continue;
			}

			if (preg_match('#[^a-z0-9_.-]#i', $name)) {
				trigger_error('The name '.$basename.' contains invalid characters.', E_USER_WARNING);
				continue;
			}

			$newData[$name][$type] = $this->buildData($basename, $mtime, $parser->get());

			$this->flush($name);
			$modified = true;
		}

		// Wir müssen die Daten erst aus der Konfiguration entfernen, falls sich
		// der Datentyp geändert hat. Ansonsten wird sich sly_Configuration z. B.
		// weigern, aus einem Skalar ein Array zu machen.
		if ($modified) {
			sly_Core::config()->remove($this->getClassIdentifier());
			$this->setData($newData);
			$this->resetRefreshTime();
		}
	}

	protected function needsRefresh() {
		$refresh = $this->getLastRefreshTime();
		if ($refresh == 0) return true;

		$files = $this->getFiles();
		$known = $this->getKnownFiles();

		return
			/* files?      */ count($files) > 0 &&
			/* new data?   */ (max(array_map('filemtime', $files)) > $refresh ||
			/* new files?  */ count(array_diff(array_map('basename', $files), $known)) > 0);
	}

	protected function getData() {
		if (!isset($this->data)) $this->data = sly_Core::config()->get($this->getClassIdentifier().'/data', array());
		return $this->data;
	}

	protected function setData($data) {
		sly_Core::config()->set($this->getClassIdentifier().'/data', $data);
		$this->data = $data;
	}

	public function find($attribute, $value, $type = null) {
		$data = $this->getData();
		$type = $type === null ? $this->getFileType() : $type;

		foreach ($data as $name => $properties) {
			if (isset($properties[$type][$attribute]) && $properties[$type][$attribute] == $value) return $name;
		}

		return false;
	}

	protected function getLastRefreshTime() {
		if (!isset($this->lastRefreshTime)) {
			$this->lastRefreshTime = sly_Core::config()->get($this->getClassIdentifier().'/last_refresh', 0);
		}
		return $this->lastRefreshTime;
	}

	protected function resetRefreshTime($time = null) {
		if ($time === null) $time = time();
		sly_Core::config()->set($this->getClassIdentifier().'/last_refresh', $time);
		$this->lastRefreshTime = $time;
	}

	public function exists($name) {
		$data = $this->getData();
		return isset($data[$name]);
	}

	public function getKnownFiles() {
		$known = array();

		$data = $this->getData();
		foreach ($data as $item) {
			foreach ($item as $itemType) {
				if (!empty($itemType['filename'])) $known[] = $itemType['filename'];
			}
		}

		natsort($known);
		return $known;
	}

	public function get($name, $key = null, $default = null, $type = null) {
		if ($key == 'name') return $name;

		$this->refresh();

		// module exists?
		$data = $this->getData();
		if (!isset($data[$name])) return false;
		if ($type !== null && !isset($data[$name][$type])) return $default;

		// get default type if necessary
		if ($type === null) {
			foreach ($this->getFileTypes() as $fileType) {
				if (isset($data[$name][$fileType])) {
					$type = $fileType;
					break;
				}
			}
		}

		// return all data?
		$result = $data[$name][$type];
		if ($key === null) return $result;

		// check for standard params first, then for custom params.
		return (isset($result[$key]) ? $result[$key] : (isset($result['params'][$key]) ? $result['params'][$key] : $default));
	}

	public function getContent($name, $type = null) {
		$data = $this->getData();
		$type = $type === null ? $this->getFileType() : $type;
		if (!isset($data[$name][$type])) return false;

		$filename = sly_Util_Directory::join($this->getFolder(), $data[$name][$type]['filename']);
		return file_exists($filename) ? file_get_contents($filename) : false;
	}


	protected abstract function isFileValid($filename);

	protected abstract function getFileType($filename = '');

	public abstract function getFileTypes();

	protected abstract function getClassIdentifier();

	protected abstract function buildData($filename, $mtime, $data);

	protected abstract function flush($name = null);
}