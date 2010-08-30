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
 * @ingroup layout
 */
abstract class sly_Layout {
	protected $title = '';
	protected $cssCode         = '';
	protected $javaScriptCode  = '';
	protected $favIcon         = null;
	protected $cssFiles        = array();
	protected $javaScriptFiles = array();
	protected $feedFiles       = array();
	protected $bodyAttrs       = array();
	protected $httpMetas       = array();
	protected $metas           = array();
	protected $links           = array();

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
		global $REX, $I18N;

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
		include SLY_INCLUDE_PATH.DIRECTORY_SEPARATOR.$filenameHtuG50hNCdikAvf7CZ1F;
		print ob_get_clean();
	}

	/**
	 * Setzt den Inhalt den title Tags
	 *
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Erweitert den Inhalt des title Tags.
	 *
	 * @param string $title
	 */
	public function appendToTitle($title) {
		$this->title .= $title;
	}

	/**
	 * Setzt den pfad zu favicon
	 *
	 * @param string $iconPath
	 */
	public function setFavIcon($iconPath) {
		$this->favIcon = trim($iconPath);
	}

	/**
	 * Füegt den übergebenen String dem css code des layouts zu.
	 *
	 * @param string $css
	 */
	public function addCSS($css) {
		$css = trim($css);
		$this->cssCode .= "\n$css";
	}

	/**
	 * Fügt dem layout eine css datei zu. CSS Dateien werden Gruppen zugeordnet,
	 * Funktionen der Gruppen siehe printCSSFiles().
	 *
	 * @param string $cssFile Pfad zur CSS Datei
	 * @param string $media media Attribut für den CSS Link
	 *
	 */
	public function addCSSFile($cssFile, $media = 'all', $group = 'default') {
		$this->cssFiles[trim($group)][trim($media)][] = array('src' => trim($cssFile));
	}

	/**
	 * Füegt den übergebenen String dem javascript code des layouts zu.
	 *
	 * @param string $javascript
	 */
	public function addJavaScript($javascript) {
		$javascript = trim($javascript);
		$this->javaScriptCode .= "\n$javascript";
	}

	/**
	 * Fügt dem layout eine Javascript datei zu. Javascript Dateien werden Gruppen zugeordnet,
	 * Funktionen der Gruppen siehe printJavaScriptFiles().
	 *
	 * @param string $cssFile Pfad zur CSS Datei
	 * @param string $media media Attribut für den CSS Link
	 *
	 */
	public function addJavaScriptFile($javascriptFile, $group = 'default') {
		$this->javaScriptFiles[trim($group)][] = trim($javascriptFile);
	}

	/**
	 * Fügt dem body tag des Layouts HTML Attribute hinzu.
	 * Attrributnamen, die mit 'on' beginnen werden statt inline gesetzt zu werden,
	 * als funktion window.on... dem Jvascript Code des Layouts zugefügt.
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function setBodyAttr($name, $value) {
		$name = trim($name);
		$value = trim($value);
		if(sly_startsWith($name, 'on')) {
			$this->addJavaScript('window.'.$name.' = function() { '.$value.' }');
		}else{
			$this->bodyAttrs[$name] = $value;
		}
	}

	/**
	 * Fügt dem Layout eine Metainformation hinzu.
	 *
	 * @param string $name
	 * @param string $content
	 */
	public function addMeta($name, $content)	{
		$this->metas[trim($name)] = trim($content);
	}

	/**
	 * Fügt dem Layout eine HTTP Metainformation hinzu.
	 *
	 * @param string $name
	 * @param string $content
	 */
	public function addHttpMeta($name, $content) {
		$this->httpMetas[trim($name)] =trim($content);
	}

	/**
	 * Fügt dem Layout einen Link hinzu.
	 *
	 * @param string $rel
	 * @param string $href
	 * @param string $type
	 */
	public function addLink($rel, $href, $type = '', $title= '') {
		$this->links[] = array('rel' => trim($rel), 'href' => trim($href), 'type' => trim($type), 'title' => trim($title));
	}

	/**
	 * Fügt dem Layout einen Feed Link hinzu.
	 * Momentan werden RSS und Atom Feeds unterstützt
	 *
	 * @param string $feedFile
	 * @param string $type
	 */
	public static function addFeedFile($feedFile, $type = '') {
		switch ($type) {
			case 'rss1':
				$title .= "RSS-Feed 1.0";
				break;
			case 'rss2':
				$title .= "RSS-Feed 2.0";
				break;
			case 'atom':
				$title .= "Atom-Feed";
				break;
			default:
				$title .= "RSS-Feed";
				break;
		}

		if ($type != 'atom') $type .= "rss";
		$type = 'application/'.$type.'+xml';

		$this->addLink('alternate', $feedFile, $type, $title);
	}

	/**
	 * Schreibt css Styles in den Header
	 * Ruft den Extension Point HEADER_CSS auf und übergibt
	 * den CSS Code. Der vom Extension Point zurückgegebne
	 * Wert wird in das <style> Tag geschrieben. Zum schreiben wird
	 * die abstrakte Methode printCSSConcrete() aufgerufen.
	 *
	 */
	protected function printCSS() {
		$this->cssCode = rex_register_extension_point('HEADER_CSS', $this->cssCode);
		if (!empty($this->cssCode)) {
			$this->printCSSConcrete();
		}
	}

	/**
	 * Schreint den inhalt von $this->cssCode in den Header.
	 * Dabei werden layoutspezifische eigenheiten beachtet.
	 */
	protected abstract function printCSSConcrete();

	/**
	 * Schreibt CSS Files in den Header
	 * Ruft den Extension Point HEADER_CSS_FILES auf und übergibt
	 * die zugefügten CSS Dateien. Die vom Extension Point zurückgegebenen
	 * Dateien wird in den Header geschrieben. Zum Schreiben wird
	 * die abstrakte Methode printCSSFilesConcrete() aufgerufen.
	 *
	 */
	protected function printCSSFiles() {
		$this->cssFiles = rex_register_extension_point('HEADER_CSS_FILES', $this->cssFiles);
		$this->printCSSFilesConcrete();
	}

	/**
	 * Schreibt CSS Files ($this->cssFiles) in den Header
	 */
	protected abstract function printCSSFilesConcrete();

	/**
	 * Schreibt Javascript Code in den Header
	 * Ruft den Extension Point HEADER_JAVASCRIPT auf und übergibt
	 * den Javascript Code. Der vom Extension Point zurückgegebne
	 * Wert wird in den HJeader. Zum schreiben wird
	 * die abstrakte Methode printJavaScriptConcrete() aufgerufen.
	 *
	 */
	protected function printJavaScript() {
		$this->javaScriptCode = rex_register_extension_point('HEADER_JAVASCRIPT', $this->javaScriptCode);
		if (!empty($this->javaScriptCode)) {
			$this->printJavaScriptConcrete();
		}
	}

	/**
	 * Schreint den inhalt von $this->javaScriptCode in den Header.
	 * Dabei werden layoutspezifische eigenheiten beachtet.
	 */
	protected abstract function printJavaScriptConcrete();

	/**
	 * Schreibt Javascript Files in den Header
	 * Ruft den Extension Point HEADER_JAVASCRIPT_FILES auf und übergibt
	 * die zugefügten JS Dateien. Die vom Extension Point zurückgegebenen
	 * Dateien wird in den Header geschrieben. Zum Schreiben wird
	 * die abstrakte Methode printJavascriptFilesConcrete() aufgerufen.
	 *
	 */
	protected function printJavaScriptFiles() {
		$this->javaScriptFiles = rex_register_extension_point('HEADER_JAVASCRIPT_FILES', $this->javaScriptFiles);
		$this->printJavaScriptFilesConcrete();
	}
	/**
	 * Schreibt Javascript Files ($this->javaScriptFiles) in den Header
	 */
	protected abstract function printJavaScriptFilesConcrete();

	/**
	 * Schreibt die zugefügten body Attribute
	 */
	protected abstract function printBodyAttrs();

	/**
	 * Schreibt Metainformationen in den Header
	 */
	protected abstract function printMetas();

	/**
	 * Schreibt HTTP Metainformationen in den Header
	 */
	protected abstract function printHttpMetas();

	/**
	 * Schreibt die zugefügten Links in den Header
	 *
	 */
	protected function printLinks() {
		foreach ($this->links as $link) {
			$this->printLink($link);
		}
	}

	/**
	 * Schreibt einen Link in den Header
	 *
	 * @param array $link ein Hash mit attributen ($name => $value)
	 */
	protected abstract function printLink($attributes = array());

	/**
	 * Schreibt den Header
 	 */
	public function printHeader(){
		print '<html><head><title>'.$this->title.'</title></head><body>';
	}

	/**
	 * Schreibt den Footer
	 */
	public function printFooter(){
		print '</body></html>';
	}
}
