<?php
/*
 * Copyright (c) 2009, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

class sly_Table_Column
{
	protected $width;
	protected $sortkey;
	protected $direction;
	protected $htmlAttributes;
	protected $content;
	
	public function __construct($content, $width = '', $sortkey = '', $htmlAttributes = array())
	{
		$this->content        = $content;
		$this->width          = $width;
		$this->sortkey        = $sortkey;
		$this->htmlAttributes = $htmlAttributes;
		
		if (sly_get('sortby', 'string') == $sortkey) {
			$this->direction = sly_get('direction', 'string') == 'desc' ? 'desc' : 'asc';
		}
		else {
			$this->direction = 'none';
		}
	}
	
	public function setContent($content)
	{
		$this->content = $content;
	}
	
	public function render(sly_Table $table, $index)
	{
		global $REX;
		
		if (!empty($this->width)) {
			$this->htmlAttributes['style'] = 'width:'.$this->width;
		}
		
		include $REX['INCLUDE_PATH'].'/views/_table/table/column.phtml';
	}
}
