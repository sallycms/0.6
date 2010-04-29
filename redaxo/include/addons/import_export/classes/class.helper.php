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
		$dir     = rtrim($dir, '/\\');
		$entries = array_map('basename', glob($dir.'/{,.}*', GLOB_BRACE | GLOB_NOSORT));
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
