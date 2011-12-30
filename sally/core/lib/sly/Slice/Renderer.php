<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Slice_Renderer {

	private $moduleName;
	private $values;

	/**
	 * @param array $values
	 */
	public function __construct($moduleName, $values = array()) {
		$this->moduleName = $moduleName;
		$this->setValues($values);
	}

	/**
	 * sets the values that can be displayed
	 *
	 * @param array $values
	 */
	public function setValues($values) {
		if(!sly_Util_Array::isAssoc($values)) {
			throw new sly_Exception('Values must be assoc array!');
		}
		$this->values = $values;
	}

	protected function value($id, $default) {
		if(!array_key_exists($id, $this->values)) return $default;
		return $this->values[$id];
	}

	public function renderInput() {
		$service                      = sly_Service_Factory::getModuleService();
		$filenameHtuG50hNCdikAvf7CZ1F = $service->getFolder().DIRECTORY_SEPARATOR.$service->getInputFilename($this->moduleName);
		unset($service);
		$form = new sly_Form('', '', '');
		ob_start();
		include $filenameHtuG50hNCdikAvf7CZ1F;
		print $form->render(true);
		return ob_get_clean();
	}

	public function renderOutput() {
		$service                      = sly_Service_Factory::getModuleService();
		$filenameHtuG50hNCdikAvf7CZ1F = $service->getFolder().DIRECTORY_SEPARATOR.$service->getOutputFilename($this->moduleName);
		unset($service);
		ob_start();
		include $filenameHtuG50hNCdikAvf7CZ1F;
		$output = ob_get_clean();
		$output = $this->replaceLinks($output);
		return $output;
	}

	/**
	 * Replaces sally://ARTICLEID and sally://ARTICLEID-CLANGID in
	 * the slice content by article http URLs.
	 *
	 * @param string $content
	 * @return string
	 */
	protected function replaceLinks($content) {
		preg_match_all('#(?:redaxo|sally)://([0-9]+)(?:-([0-9]+))?/?#', $content, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

		$skew = 0;

		foreach ($matches as $match) {
			$complete = $match[0];
			$length   = strlen($complete[0]);
			$offset   = $complete[1];
			$id       = (int) $match[1][0];
			$clang    = isset($match[2]) ? (int) $match[2][0] : null;
			$repl     = sly_Util_Article::getUrl($id, $clang);

			// replace the match
			$content = substr_replace($content, $repl, $offset + $skew, $length);

			// ensure the next replacements get the correct offset
			$skew += strlen($repl) - $length;
		}

		return $content;
	}

	protected function getForm() {

	}

}
