<?php

class sly_A1_Export_Files
{
	protected $directories;
	protected $tar;
	
	public function __construct($directories)
	{
		$this->directories = $directories;
	}
	
	public function export($filename)
	{
		// Archiv an einem temporären Ort erzeugen (Rekursion vermeiden)
		
		$tmpFile = tempnam(sys_get_temp_dir(), 'sly').'.gz';
		$ignores = array('redaxo/include/addons/import_export/backup');
		$tar     = new sly_A1_Archive_Tar($tmpFile);
		$tar     = rex_register_extension_point('SLY_A1_BEFORE_FILE_EXPORT', $tar);
		
		// Backups nicht rekursiv mit sichern!
		
		$tar->setIgnoreList($ignores);
		
		// Gewählte Verzeichnisse sichern
		
		chdir('../');
		$success = $tar->create($this->directories);
		chdir('redaxo');
		
		// Archiv ggf. nachträglich noch verändern

		$tar = rex_register_extension_point('SLY_A1_AFTER_FILE_EXPORT', $tar, array(
			'filename' => $filename,
			'tmp_file' => $tmpFile,
			'status'   => $success
		));
		
		// Archiv verschieben
		
		if ($success) rename($tmpFile, $filename);
		return $success;
	}
}
