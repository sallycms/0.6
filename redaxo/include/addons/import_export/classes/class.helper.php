<?php

class sly_A1_Helper
{
	public static function getIteratedFilename($directory, $filename, $ext)
	{
		$directory = rtrim($directory, '/\\').'/';

		if (file_exists($directory.$filename.$ext)) {
			$i = 1;
			while (file_exists($directory.$filename.'_'.$i.$ext)) $i++;
			$filename = $filename.'_'.$i;
		}

		return $filename;
	}

	public static function readFolder($dir)
	{
		$dir = rtrim($dir, '/\\');
		$dir = new sly_Util_Directory($dir);
		$entries = array_map('basename', $dir->listPlain(true, false));
		sort($entries);
		return $entries;
	}

	public static function readFilteredFolder($dir, $suffix)
	{
		$folder   = self::readFolder($dir);
		$filtered = array();

		if (!$folder) return false;

		foreach ($folder as $file) {
			if (endsWith($file, $suffix)) $filtered[] = $file;
		}

		return $filtered;
	}

	public static function getDatabaseDumps($dir)
	{
		$files = array();
		$files = array_merge($files, self::readFilteredFolder($dir, '.sql'));
		$files = array_merge($files, self::readFilteredFolder($dir, '.sql.gz'));
		$files = array_merge($files, self::readFilteredFolder($dir, '.sql.bz2'));
		return $files;
	}

	public static function getFileArchives($dir)
	{
		$files = array();
		$files = array_merge($files, self::readFilteredFolder($dir, '.tar'));
		$files = array_merge($files, self::readFilteredFolder($dir, '.tar.gz'));
		$files = array_merge($files, self::readFilteredFolder($dir, '.tar.bz2'));
		return $files;
	}

	public static function getFileInfo($filename)
	{
		$result = array(
			'real_file'   => $filename,
			'filename'    => strtolower(basename($filename)),
			'exists'      => file_exists($filename),
			'compression' => '',
			'size'        => -1,
			'date'        => -1,
			'type'        => '',
			'description' => ''
		);

		if (!$result['exists']) {
			return $result;
		}

		$result['date'] = filectime($filename);
		$result['size'] = filesize($filename);

		// Komprimierung erkennen

		if (endsWith($filename, '.gz'))  $result['compression'] = 'gz';
		if (endsWith($filename, '.bz2')) $result['compression'] = 'bz2';

		// Komprimierung entfernen

		if (!empty($result['compression'])) {
			$result['filename'] = substr($result['filename'], 0, -strlen($result['compression']) - 1);
		}

		// Erweiterung finden

		$result['type']     = substr($result['filename'], strrpos($result['filename'], '.') + 1);
		$result['filename'] = substr($result['filename'], 0, strrpos($result['filename'], '.'));

		// Entspricht der Dateiname einem bekannten Muster?

		if (preg_match('#^(sly_\d{8})_(.*?)$#i', $result['filename'], $matches)) {
			$result['filename']    = $matches[1];
			$result['description'] = str_replace('_', ' ', $matches[2]);
		}
		elseif (preg_match('#^(sly_\d{8})_(.*?)_(\d+)$#i', $result['filename'], $matches)) {
			$result['filename']    = $matches[1].'_'.$matches[3];
			$result['description'] = str_replace('_', ' ', $matches[2]);
		}

		return $result;
	}

	protected static function matchFilename($filename)
	{


		return $filename;
	}

	public static function readFolderFiles($dir)
	{
		$dir    = rtrim($dir, '/\\');
		$folder = self::readFolder($dir);
		$files  = array();

		if (!$folder) return false;

		foreach ($folder as $file) {
			if (is_file($dir.'/'.$file)) $files[] = $file;
		}

		return $files;
	}

	public static function readSubFolders($dir, $ignoreDots = true)
	{
		$dir     = rtrim($dir, '/\\');
		$folder  = self::readFolder($dir);
		$folders = array();

		if (!$folder) return false;

		foreach ($folder as $file) {
			if ($ignoreDots && ($file == '.' || $file == '..')) {
				continue;
			}

			if (is_dir($dir.'/'.$file)) {
				$folders[] = $file;
			}
		}

		return $folders;
	}
}
