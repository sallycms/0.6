<?php

abstract class sly_Layout
{
	protected $title = '';


	protected $content;

	public function openBuffer() {
		ob_start();
	}

	public function closeBuffer() {
		$this->content = ob_get_clean();
	}

	public function closeAllBuffers() {
		while (ob_get_level()) ob_end_clean();
	}

	public function render() {
		ob_start();
		$this->printHeader();
       	print $this->content;
		$this->printFooter();
       	return ob_get_clean();
	}

	protected function renderView($filename, $params = array()) {
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

	/**
	 * Setzt den Inhalt den title Attibuts
	 *
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	public function printHeader(){
		print '<html><head><title>'.$this->title.'</title></head><body>';
	}

	public function printFooter(){
		print '</body></html>';
	}
}
