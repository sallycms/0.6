<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

class sly_Layout_XHTML extends sly_Layout
{
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
	 * Erweitert den Inhalt den title Attibuts
	 *
	 * @param string $title
	 */
	public function appendToTitle($title) {
		$this->title .= $title;
	}

	public static function addFeedFile($feedFile, $type = '') {
		if (!in_array($type, array('rss', 'rss1', 'rss2', 'atom'))) {
			$type = 'feed';
		}

		$this->feedFiles[$type] = $feedFile;
	}

	public function addCSS($css) {
		$this->cssCode .= "\n$css";
	}

	/**
	 *
	 * @param string $cssFile Pfad zur CSS Datei
	 * @param string $media media Attribut für den CSS Link
	 *
	 */
	public function addCSSFile($cssFile, $media = 'all', $group = 'default') {
		$this->cssFiles[$group][$media][] = array('src' => $cssFile);
	}

	public function addJavaScript($javascript) {
		$this->javaScriptCode .= "\n$javascript";
	}

	public function addJavaScriptFile($javascriptFile, $group = 'default') {
		$this->javaScriptFiles[$group][] = $javascriptFile;
	}

	public function setBodyAttr($name, $value) {
		if(sly_startsWith($name, 'on')) {
			$this->addJavaScript('window.'.$name.' = function() { '.$value.' }');
		}else{
			$this->bodyAttrs[$name] = $value;
		}
	}

	public static function addMeta($name, $content)	{
		$this->metas[$name] = $content;
	}

	public function addHttpMeta($name, $content) {
		$this->httpMetas[$name] = $content;
	}

	public function addLink($rel, $href) {
		$this->links[$rel] = $href;
	}


	
	protected function printFeedFiles() {
		foreach ($this->feedFiles as $type => $file) {
			
			$link = "<link rel=\"alternate\" type=\"application/";
			if ($type != 'atom') $link .= "rss";
			else $link .= $type;
			$link .= "+xml\" title=\"";
			switch ($type) {
				case 'rss1':
					$link .= "RSS-Feed 1.0";
					break;
				case 'rss2':
					$link .= "RSS-Feed 2.0";
					break;
				case 'atom':
					$link .= "Atom-Feed";
					break;
					
				default:
					$link .= "RSS-Feed";
					break;
			}
			$link .= "\" href=\"".$file."\" />\n";
			
			print $link;
		}
	}


	protected function printCSS() {
		$this->cssCode =  rex_register_extension_point('HEADER_CSS', $this->cssCode);

		if (!empty($this->cssCode)) {
			print "<style type=\"text/css\">\n".trim($this->cssCode)."\n</style>\n";
		}
	}

	protected function printCSSFiles() {

		$this->cssFiles =  rex_register_extension_point('HEADER_CSS_FILES', $this->cssFiles);

		foreach ($this->cssFiles as $group => $medias) {
			$isConditional = false;
			
			if (strtoupper(substr($group, 0, 3)) == 'IF ') {
				print "<!--[if ".strtoupper(substr($group, 3))."]>\n";
				$isConditional = true;
			}

			foreach($medias as $media => $files){
				foreach ($files as $file) {
					print "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$file['src']."\" media=\"".$media."\" />\n";
				}
			}
			
			if ($isConditional) print "<![endif]-->\n";
		}
	}


	protected function printJavaScript() {

		$this->javaScriptCode =  rex_register_extension_point('HEADER_JAVASCRIPT', $this->javaScriptCode);

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
			foreach($files as $file){
				print "<script type=\"text/javascript\" src=\"".sly_html(trim($file))."\"></script>\n";
			}
		}
	}

	protected function printBodyAttrs() {
		foreach($this->bodyAttrs as $name => $value) {
			print trim($name).'="'.sly_html(trim($value)).'"';
		}
	}

	protected function printMetas() {
		foreach ($this->metas as $name => $content) {
			print "<meta name=\"".sly_html(trim($name))."\" content=\"".sly_html(trim($content))."\" />\n";
		}
	}

	protected function printHttpMetas(){
		foreach($this->httpMetas as $name => $content) {
			print "<meta http-equiv=\"".sly_html(trim($name))."\" content=\"".sly_html(trim($content))."\" />\n";
		}
	}

	protected function printLinks() {
		foreach ($this->links as $rel => $href) {
			print "<link rel=\"".sly_html(trim($rel))."\" href=\"".sly_html(trim($href))."\" />\n";
		}
	}

	public function printHeader() {
		$this->renderView('views/layout/xhtml/head.phtml');
	}
}
