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
 * Base service-class for development files such as templates, modules,
 * actions. This class holds general methods, that are used in all
 * implementors.
 *
 * @copyright 2010 webvariants GbR
 * @ingroup service
 */
abstract class sly_Service_DevelopBase {

	private $data;
	private $lastRefreshTime;

	/**
	 * Get a list of files for this type of items
	 *
	 * @param  boolean  $absolute  true, if the paths should be absolute (default: true)
	 * @return array               Array of files
	 */
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

	/**
	 * Gets the folder where the development files can be found
	 *
	 * @return string  Folder path
	 */
	public function getFolder() {
		$dir = sly_Util_Directory::join(SLY_DEVELOPFOLDER, $this->getClassIdentifier());
		if (!is_dir($dir) && !@mkdir($dir, sly_Core::config()->get('DIRPERM'), true)) throw new sly_Exception('Konnte Verzeichnis '.$dir.' nicht erstellen.');
		return $dir;
	}

	/**
	 * Performs a refresh.
	 *
	 * Reads the parameters from the development files if changed.
	 * If force is enabled the refresh ist done for all files.
	 *
	 * @param boolean  $force  true enables force. default: false
	 */
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

			if (!$this->areParamsValid($name, $data, $newData, $basename, $type)) continue;

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

	/**
	 * Checks, if a refresh of the files is necessary
	 *
	 * @return boolean  true, when refresh is necessary
	 */
	protected function needsRefresh() {
		return true;
		$refresh = $this->getLastRefreshTime();
		if ($refresh == 0) return true;

		$files = $this->getFiles();
		$known = $this->getKnownFiles();

		return
			/* files?      */ count($files) > 0 &&
			/* new data?   */ (max(array_map('filemtime', $files)) > $refresh ||
			/* new files?  */ count(array_diff(array_map('basename', $files), $known)) > 0);
	}

	/**
	 * Check if the parameters of an development file are valid.
	 *
	 * Implementors may overload this method to make sure the
	 * development files have all necessary data set. If a file
	 * does not match the requirements a warning is thrown.
	 * (e.g. when required parameters are missing or when the
	 * syntax contains errors)
	 *
	 * @param  string  $name     Name of the develoment file
	 * @param  array   $params   Array with parameters parsed from the file
	 * @param  array   $newData  Array with the parameters of all previous iterated files
	 * @return boolean           true, when the parameters are valid. false for invalid.
	 */
	protected function areParamsValid($name, $params, $newData, $filename, $type) {
		$result = true;

		if ($name === null) {
			trigger_error($filename.' has no internal name and cannot be loaded.', E_USER_WARNING);
			$result = false;
		}

		if (isset($newData[$name][$type])) {
			trigger_error($filename.' has no unique name. (type: '.$type.')', E_USER_WARNING);
			$result = false;
		}

		if (preg_match('#[^a-z0-9_.-]#i', $name)) {
			trigger_error('The name '.$name.' contains invalid characters.', E_USER_WARNING);
			$result = false;
		}

		return $result;
	}

	/**
	 * Get the data array
	 *
	 * @return array  The parameters of all items as an associative array
	 */
	protected function getData() {
		if (!isset($this->data)) $this->data = sly_Core::config()->get($this->getClassIdentifier().'/data', array());
		return $this->data;
	}

	protected function setData($data) {
		sly_Core::config()->set($this->getClassIdentifier().'/data', $data);
		$this->data = $data;
	}

	/**
	 * Find a development file by attribute
	 *
	 * @param  string  $attribute  The attribute name
	 * @param  string  $value      The attribute value
	 * @param  string  $type       The filetype if necessary (default: null)
	 * @return string              Element name
	 */
	public function find($attribute, $value, $type = null) {
		$data = $this->getData();
		$type = $type === null ? $this->getFileType() : $type;

		foreach ($data as $name => $properties) {
			if (isset($properties[$type][$attribute]) && $properties[$type][$attribute] == $value) return $name;
		}

		return false;
	}

	/**
	 * Checks, when the data was refreshed last time
	 *
	 * @return int  Refresh timestamp
	 */
	protected function getLastRefreshTime() {
		if (!isset($this->lastRefreshTime)) {
			$this->lastRefreshTime = sly_Core::config()->get($this->getClassIdentifier().'/last_refresh', 0);
		}
		return $this->lastRefreshTime;
	}

	/**
	 * Reset the refresh time
	 *
	 * @param int $time  The new timestamp. Leave this null for the current timestamp time();
	 */
	protected function resetRefreshTime($time = null) {
		if ($time === null) $time = time();
		sly_Core::config()->set($this->getClassIdentifier().'/last_refresh', $time);
		$this->lastRefreshTime = $time;
	}

	/**
	 * Checks, if an item exists
	 *
	 * @param  string  $name  The name of the item
	 * @return boolean        true, if the item exists
	 */
	public function exists($name) {
		$data = $this->getData();
		return isset($data[$name]);
	}

	/**
	 * Get an array with all known files.
	 *
	 * @return array  Array with known files
	 */
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

	/**
	 * Get a parameter of a resource
	 *
	 * The parameters of the resource is directly returned. The key 'params'
	 * returns an array with all user defined parameters. User parameters may
	 * also be fetched directly by giving the name of the parameter.
	 *
	 * @param  string  $name     Name of the item
	 * @param  string  $key      Key of the desired parameter. null gets all. (default: null)
	 * @param  string  $default  Default value, if the desired parameter is not set (default: null)
	 * @param  string  $type     Filetype if necessary (default: null)
	 * @return mixed             array with all user defined parameters or string with the desired parameter
	 * @throws sly_Exception     When the resource with the given name is not available
	 */
	public function get($name, $key = null, $default = null, $type = null) {
		if ($key == 'name') return $name;

		$data = $this->getData();
		if (!isset($data[$name])) throw new sly_Exception('The development resource "'.$name.'" is not available.');
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

	/**
	 * Gets the content of an item
	 *
	 * @param  string  $name  Name of the item
	 * @param  string  $type  Type if necessary (default: null)
	 * @return string         Content of the file
	 * @throws sly_Exception  When item/file does not exist.
	 */
	public function getContent($name, $type = null) {
		$data = $this->getData();
		$type = $type === null ? $this->getFileType() : $type;
		if (!isset($data[$name][$type]['filename'])) throw new sly_Exception("Item '$name' does not exist.");

		$filename = sly_Util_Directory::join($this->getFolder(), $data[$name][$type]['filename']);
		if (!file_exists($filename)) throw new sly_Exception("File '$filename' does not exist.");
		return file_get_contents($filename);
	}


	/**
	 * Checks if a file matches the desired naming convention
	 *
	 * Implementors will be called, before the development files
	 * are read from their location to filter files, that do not match
	 * their naming convention.
	 *
	 * @param  string $filename  The Filename
	 * @return boolean           true, when the file is a valid development resource
	 */
	protected abstract function isFileValid($filename);

	/**
	 * Gets the filetype of the current file
	 *
	 * Implementors may discern between various file types (e.g. input and
	 * output files for modules). The difference is typically checked via a
	 * naming convention for the filename.
	 *
	 * Implementors must return a valid filetype for every imput. (e.g. a
	 * default filetype for a invalid input)
	 *
	 * @param  string  $filename  The filename (may be empty)
	 * @return string             The filetype for the current filename
	 */
	protected abstract function getFileType($filename = '');

	/**
	 * Gets a list with possible filetypes
	 *
	 * Implementors must return a list with their possible filetypes
	 *
	 * @return array  Array of strings with possible filetypes
	 */
	public abstract function getFileTypes();

	/**
	 * Get a unique identifier for this implementor
	 *
	 * This identifier is used for caching purposes. It should be the same
	 * as the folder name for the development resources in the /develop
	 * directory, where the files for this implementor are expected.
	 *
	 * @return string  The unique class identifier
	 */
	protected abstract function getClassIdentifier();

	/**
	 * Gets the implementor-specific data array from the parsed data from file
	 *
	 * Implementors should return an array of parameters. The default
	 * parameters that are used by the implementor should be in the first level
	 * of this array. The developer of a development resource should be able to
	 * enter own parameters. These parameters will be expected in a 'params'
	 * field.
	 * Furthermore the parameters 'name' and 'mtime' are required and 'title'
	 * is recommended.
	 *
	 * e.g.
	 *
	 * array(
	 *   'name'   => 'test',
	 *   'title'  => 'My template',
	 *   'mtime'  => 12345...,
	 *   'params' => array(
	 *     'user_param_1' => 'test',
	 *     'user_param_2' => 'test2'
	 *   )
	 * )
	 *
	 * @param  string  $filename  The filename of the current development resource
	 * @param  int     $mtime     The timestamp of the last change of this resource
	 * @param  array   $data      An associative array with all parameters, parsed from the resource
	 * @return array              The parameter structure for this specific resource
	 */
	protected abstract function buildData($filename, $mtime, $data);

	/**
	 * Clears the cache for a resource
	 *
	 * @param string  $name  Resource name or null for all resources. (default: null)
	 */
	protected abstract function flush($name = null);
}