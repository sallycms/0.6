<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup util
 */
class sly_Util_Pager {
	const FIRST_ACTIVE   = -1;
	const FIRST_INACTIVE = -2;
	const PREV_ACTIVE    = -3;
	const PREV_INACTIVE  = -4;
	const NEXT_ACTIVE    = -5;
	const NEXT_INACTIVE  = -6;
	const LAST_ACTIVE    = -7;
	const LAST_INACTIVE  = -8;
	const ELLIPSIS_LEFT  = -9;
	const ELLIPSIS_RIGHT = -10;

	protected $currentPage;
	protected $totalElements;
	protected $perPage;
	protected $maxLinks;
	protected $linksLeftRight;
	protected $linksOnEnd;
	private $pages;

	public function __construct($currentPage, $totalElements, $perPage = 10, $maxLinks = 10, $linksLeftRight = 2, $linksOnEnds = 2) {
		$this->currentPage    = abs((int) $currentPage);
		$this->totalElements  = abs((int) $totalElements);
		$this->perPage        = abs((int) $perPage);
		$this->maxLinks       = abs((int) $maxLinks);
		$this->linksLeftRight = abs((int) $linksLeftRight);
		$this->linksOnEnd     = abs((int) $linksOnEnds);

		$this->pages = ceil($this->totalElements / $this->perPage);

		if ($this->currentPage > $this->pages-1) $this->currentPage = $this->pages - 1;
		if ($this->currentPage < 0) $this->currentPage = 0;
		if ($this->maxLinks < 5) $this->maxLinks = 5;
	}

	public function getPaginationData() {
		$result = array();

		if ($this->currentPage > 0) {
			$result[] = self::FIRST_ACTIVE;
			$result[] = self::PREV_ACTIVE;
		}
		else {
			$result[] = self::FIRST_INACTIVE;
			$result[] = self::PREV_INACTIVE;
		}

		if ($this->pages <= $this->maxLinks) {
			for ($i = 0; $i < $this->pages; ++$i) $result[] = $i;
			if ($this->pages == 0) $result[] = 0;
		}
		else {
			// Links am Anfang

			$result[] = 0;
			for ($i = 0; $i < $this->linksOnEnd; ++$i) $result[] = $i+1;

			// Links um die aktuelle Seite herum

			$begin = $this->currentPage - $this->linksLeftRight;
			$end   = $this->currentPage + $this->linksLeftRight;

			if ($begin-1 > $this->linksOnEnd) $result[] = self::ELLIPSIS_LEFT;

			for ($i = $begin; $i <= $end; ++$i) {
				if ($i > 0 && $i < $this->pages) $result[] = $i;
			}

			if ($end < ($this->pages - $this->linksOnEnd - 2)) $result[] = self::ELLIPSIS_RIGHT;

			// Links am Ende

			for ($i = $this->linksOnEnd; $i > 0; --$i) $result[] = $this->pages - $i - 1;
			$result[] = $this->pages - 1;

			// Doppelte entfernen

			$result = array_unique($result);
		}

		if ($this->currentPage < $this->pages - 1) {
			$result[] = self::NEXT_ACTIVE;
			$result[] = self::LAST_ACTIVE;
		}
		else {
			$result[] = self::NEXT_INACTIVE;
			$result[] = self::LAST_INACTIVE;
		}

		return $result;
	}

	public function getRawLinks($getParams, $pageParamName = 'p', $filename = 'index.php', $specialSymbols = array()) {
		// Standardwerte für die Spezialsymbole

		if (!isset($specialSymbols['first_active']))   $specialSymbols['first_active']   = '|&laquo;';
		if (!isset($specialSymbols['first_inactive'])) $specialSymbols['first_inactive'] = '|&laquo;';
		if (!isset($specialSymbols['prev_active']))    $specialSymbols['prev_active']    = '&laquo;';
		if (!isset($specialSymbols['prev_inactive']))  $specialSymbols['prev_inactive']  = '&laquo;';
		if (!isset($specialSymbols['next_active']))    $specialSymbols['next_active']    = '&raquo;';
		if (!isset($specialSymbols['next_inactive']))  $specialSymbols['next_inactive']  = '&raquo;';
		if (!isset($specialSymbols['last_active']))    $specialSymbols['last_active']    = '&raquo;|';
		if (!isset($specialSymbols['last_inactive']))  $specialSymbols['last_inactive']  = '&raquo;|';
		if (!isset($specialSymbols['ellipsis']))       $specialSymbols['ellipsis']       = '&hellip;';

		// Auf geht's!

		$data = $this->getPaginationData();
		unset($getParams[$pageParamName]);
		$links = array();

		foreach ($data as $pageCode) {
			$url     = '';
			$text    = '';
			$attribs = array();

			switch ($pageCode) {
				case self::FIRST_ACTIVE:
					$url     = $this->getURL($filename, $getParams, $pageParamName, 0);
					$text    = $specialSymbols['first_active'];
					$attribs = array('class' => 'first');
					break;

				case self::FIRST_INACTIVE:
					$url     = '';
					$text    = $specialSymbols['first_inactive'];
					$attribs = array('class' => 'first');
					break;

				case self::PREV_ACTIVE:
					$url     = $this->getURL($filename, $getParams, $pageParamName, $this->currentPage-1);
					$text    = $specialSymbols['prev_active'];
					$attribs = array('class' => 'prev');
					break;

				case self::PREV_INACTIVE:
					$url     = '';
					$text    = $specialSymbols['prev_inactive'];
					$attribs = array('class' => 'prev');
					break;

				case self::NEXT_ACTIVE:
					$url     = $this->getURL($filename, $getParams, $pageParamName, $this->currentPage+1);
					$text    = $specialSymbols['next_active'];
					$attribs = array('class' => 'next');
					break;

				case self::NEXT_INACTIVE:
					$url     = '';
					$text    = $specialSymbols['next_inactive'];
					$attribs = array('class' => 'next');
					break;

				case self::LAST_ACTIVE:
					$url     = $this->getURL($filename, $getParams, $pageParamName, $this->pages - 1);
					$text    = $specialSymbols['last_active'];
					$attribs = array('class' => 'last');
					break;

				case self::LAST_INACTIVE:
					$url     = '';
					$text    = $specialSymbols['last_inactive'];
					$attribs = array('class' => 'last');
					break;

				case self::ELLIPSIS_LEFT:
				case self::ELLIPSIS_RIGHT:
					$url     = '';
					$text    = $specialSymbols['ellipsis'];
					$attribs = array('class' => 'ellipsis');
					break;

				case $this->currentPage:
					$url     = '';
					$text    = $pageCode + 1;
					$attribs = array('class' => 'active page'.$this->currentPage);
					break;

				default:
					$url       = $this->getURL($filename, $getParams, $pageParamName, $pageCode);
					$text      = $pageCode + 1;
					$direction = $pageCode < $this->currentPage ? 'before' : 'after';
					$attribs   = array('class' => 'normal page'.($pageCode+1).' '.$direction);
					break;
			}

			$links[] = array(
				'url'        => $url,
				'text'       => $text,
				'attributes' => $attribs
			);
		}

		return $links;
	}

	public function getHTMLString($getParams, $pageParamName = 'p', $filename = 'index.php', $specialSymbols = array()) {
		$links = $this->getRawLinks($getParams, $pageParamName, $filename, $specialSymbols);

		foreach ($links as $idx => $data) {
			if (!empty($data['url'])) {
				$data['attributes']['href'] = $data['url'];
			}

			foreach ($data['attributes'] as $name => $value) {
				$data['attributes'][$name] = $name.'="'.$value.'"';
			}

			$attributes  = implode(' ', $data['attributes']);
			$attributes  = empty($attributes) ? '' : " $attributes";
			$tagName     = empty($data['url']) ? 'span' : 'a';
			$links[$idx] = '<'.$tagName.$attributes.'>'.$data['text'].'</'.$tagName.'>';
		}

		return "\n".implode("\n", $links)."\n";
	}

	public function getHTMLList($tag = 'ul', $getParams, $pageParamName = 'p', $filename = 'index.php', $specialSymbols = array()) {
		$links  = $this->getRawLinks($getParams, $pageParamName, $filename, $specialSymbols);
		$result = "\n<$tag class=\"pager\">";

		foreach ($links as $idx => $data) {
			foreach ($data['attributes'] as $name => $value) {
				$data['attributes'][$name] = $name.'="'.$value.'"';
			}

			$attributes = implode(' ', $data['attributes']);
			$attributes = empty($attributes) ? '' : " $attributes";
			$link       = empty($data['url']) ? $data['text'] : '<a href="'.$data['url'].'">'.$data['text'].'</a>';
			$result    .= "\n<li$attributes>$link</li>";
		}

		$result .= "\n</$tag>\n";
		return $result;
	}

	public function getCurrentElements() {
		$elements = array();
		$base     = $this->currentPage * $this->perPage;
		for ($i = 0; $i < $this->perPage; ++$i) {
			$elements[] = $base + $i;
		}
		return $elements;
	}

	protected function getURL($filename, $getParams, $pageParamName = 'p', $page = 0) {
		$link = $filename;
		if ($page > 0) $getParams[$pageParamName] = $page;
		$getString = http_build_query($getParams, '', '&amp;');
		if (!empty($getString)) $link .= '?'.$getString;
		return $link;
	}

	public static function isEllipsis($code) {
		return $code == self::ELLIPSIS_LEFT || $code == self::ELLIPSIS_RIGHT;
	}
}
