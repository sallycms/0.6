<?php

abstract class sly_View_Base
{
	protected $content = '';
	
	public function openBuffer()
	{
		ob_start();
	}
	
	public function closeBuffer()
	{
		$this->content = ob_get_clean();
	}
	
	public function closeAllBuffers()
	{
		while (ob_get_level()) ob_end_clean();
	}
	
	public function render()
	{
		return $this->content;
	}
	
	protected function renderView($filename, $params = array())
	{
		global $SLY, $I18N;
		
		// Die Parameternamen $params und $filename sind zu kurz, als dass
		// man sie zuverlässig nutzen könnte. Wenn $params durch extract()
		// während der Ausführung überschrieben wird kann das unvorhersehbare
		// Folgen haben. Darum wird $filename und $params in kryptische
		// Variablen verpackt und aus dem Kontext entfernt.
		$filenameHtuG50hNCdikAvf7CZ1F = $filename;
		$paramsHtuG50hNCdikAvf7CZ1F = $params;
		unset($filename);
		unset($params);
		extract($paramsHtuG50hNCdikAvf7CZ1F);

		ob_start();
		include $SLY['INCLUDE_PATH'].DIRECTORY_SEPARATOR.$filenameHtuG50hNCdikAvf7CZ1F;
		print ob_get_clean();
	}
}
