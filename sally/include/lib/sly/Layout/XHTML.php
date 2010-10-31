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
class sly_Layout_XHTML extends sly_Layout {
	protected $isTransitional = false;

	protected function setTransitional() {
		$this->isTransitional = true;
	}

	protected function printCSSConcrete() {
		print "<style type=\"text/css\">$this->cssCode\n</style>\n";
	}

	protected function printCSSFilesConcrete() {
		foreach ($this->cssFiles as $group => $medias) {

			$isConditional = strtoupper(substr($group, 0, 3)) == 'IF ';

			if ($isConditional) print "<!--[if ".strtoupper(substr($group, 3))."]>\n";

			foreach ($medias as $media => $files) {
				foreach ($files as $file) {
					print "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$file['src']."\" media=\"".$media."\" />\n";
				}
			}

			if ($isConditional) print "<![endif]-->\n";
		}
	}

	protected function printJavaScriptConcrete() {
			print '<script type="text/javascript">
				/* <![CDATA[ */'
				.$this->javaScriptCode
				.'/* ]]> */
				</script>';
	}

	protected function printJavaScriptFilesConcrete() {
		foreach ($this->javaScriptFiles as $files) {
			print "<script type=\"text/javascript\" src=\"".join("\"></script>\n<script type=\"text/javascript\" src=\"" , $files)."\"></script>\n";
		}
	}

	protected function printBodyAttrs() {
		foreach($this->bodyAttrs as $name => $value) {
			print $name.'="'.sly_html($value).'"';
		}
	}

	protected function printMetas() {
		foreach ($this->metas as $name => $content) {
			print "<meta name=\"".sly_html($name)."\" content=\"".sly_html($content)."\" />\n";
		}
	}

	protected function printHttpMetas(){
		foreach($this->httpMetas as $name => $content) {
			print "<meta http-equiv=\"".sly_html($name)."\" content=\"".sly_html($content)."\" />\n";
		}
	}

	protected function printLink($attributes = array()) {
		print "<link ".sly_Util_HTML::buildAttributeString($attributes)."/>\n";
	}

	public function printHeader() {
		$this->renderView('views/layout/xhtml/head.phtml');
	}
}
