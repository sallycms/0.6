<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

class sly_Layout_XHTML extends sly_Layout {
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

	public function __construct() {

	}

	public function setFavIcon($iconPath) {
		$this->favIcon = $iconPath;
	}

	/**
	 * Erweitert den Inhalt den title-Attributs
	 *
	 * @param string $title
	 */
	public function appendToTitle($title) {
		$this->title .= $title;
	}

	public function addFeedFile($feedFile, $type = '') {
		$type = strtolower($type);

		if (!in_array($type, array('rss', 'rss1', 'rss2', 'atom'))) {
			$type = 'feed';
		}

		$this->feedFiles[$type] = $feedFile;
	}

	public function addCSS($css) {
		$this->cssCode .= "\n".trim($css);
	}

	/**
	 *
	 * @param string $cssFile  Pfad zur CSS Datei
	 * @param string $media    media Attribut für den CSS Link
	 */
	public function addCSSFile($cssFile, $media = 'all', $group = 'default') {
		foreach ($this->cssFiles as $files) {
			foreach ($files as $list) {
				foreach ($list as $file) {
					if ($file['src'] == $cssFile) return false;
				}
			}
		}

		$this->cssFiles[$group][$media][] = array('src' => $cssFile);
		return true;
	}

	public function addJavaScript($javascript) {
		$this->javaScriptCode .= "\n".trim($javascript);
	}

	public function addJavaScriptFile($javascriptFile, $group = 'default') {
		foreach ($this->javaScriptFiles as $files) {
			if (in_array($javascriptFile, $files)) return false;
		}

		$this->javaScriptFiles[$group][] = $javascriptFile;
		return true;
	}

	public function setBodyAttr($name, $value) {
		if (sly_startsWith($name, 'on')) {
			$this->addJavaScript('window.'.$name.' = function() { '.$value.' }');
		}
		else {
			$this->bodyAttrs[$name] = $value;
		}
	}

	public function addMeta($name, $content)	{
		$this->metas[$name] = $content;
	}

	public function addHttpMeta($name, $content) {
		$this->httpMetas[$name] = $content;
	}

	public function addLink($rel, $href) {
		$this->links[$rel] = $href;
	}

	protected function printFeedFiles() {
		static $types  = array('rss' => 'rss', 'rss1' => 'rss', 'rss2' => 'rss', 'atom' => 'atom');
		static $titles = array('rss' => 'RSS-Feed', 'rss1' => 'RSS-Feed 1.0', 'rss2' => 'RSS-Feed 2.0', 'atom' => 'Atom-Feed');

		foreach ($this->feedFiles as $type => $file) {
			print "\t";
			printf('<link rel="alternate" type="application/%s+xml" title="%s" href="%s" />', $types[$type], $titles[$type], $file);
			print "\n";
		}
	}

	protected function printCSS() {
		$this->cssCode = rex_register_extension_point('HEADER_CSS', $this->cssCode);

		if (!empty($this->cssCode)) {
			print "\t<style type=\"text/css\">\n".trim($this->cssCode)."\n</style>\n";
		}
	}

	protected function printCSSFiles() {
		$this->cssFiles = rex_register_extension_point('HEADER_CSS_FILES', $this->cssFiles);

		foreach ($this->cssFiles as $group => $medias) {
			$isConditional = false;

			if (strtoupper(substr($group, 0, 3)) == 'IF ') {
				print "\t<!--[if ".strtoupper(substr($group, 3))."]>\n";
				$isConditional = true;
			}

			foreach ($medias as $media => $files) {
				foreach ($files as $file) {
					print "\t<link rel=\"stylesheet\" type=\"text/css\" href=\"".$file['src']."\" media=\"".$media."\" />\n";
				}
			}

			if ($isConditional) print "\t<![endif]-->\n";
		}
	}

	protected function printJavaScript() {
		$this->javaScriptCode = rex_register_extension_point('HEADER_JAVASCRIPT', $this->javaScriptCode);

		if (!empty($this->javaScriptCode)) {
			print '<script type="text/javascript">
				/* <![CDATA[ */'
				.trim($this->javaScriptCode)
				.'/* ]]> */
				</script>';
		}
	}

	protected function printJavaScriptFiles() {
		$this->javaScriptFiles = rex_register_extension_point('HEADER_JAVASCRIPT_FILES', $this->javaScriptFiles);

		foreach ($this->javaScriptFiles as $files) {
			$this->printHeadElements("\t".'<script type="text/javascript" src="%2$s"></script>'."\n", $files);
		}
	}

	protected function printBodyAttrs() {
		$this->printHeadElements(' %s="%s"', $this->bodyAttrs);
	}

	protected function printMetas() {
		$this->printHeadElements("\t".'<meta name="%s" content="%s" />'."\n", $this->metas);
	}

	protected function printHttpMetas() {
		$this->printHeadElements("\t".'<meta http-equiv="%s" content="%s" />'."\n", $this->httpMetas);
	}

	protected function printLinks() {
		$this->printHeadElements("\t".'<link rel="%s" href="%s" />'."\n", $this->links);
	}

	public function printHeader() {
		$this->renderView('views/layout/xhtml/head.phtml');
	}

	private function printHeadElements($format, $data) {
		foreach ($data as $key => $value) {
			printf($format, sly_html(trim($key)), sly_html(trim($value)));
		}
	}
}
