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

class sly_Form_Text extends sly_Form_ElementBase implements sly_Form_IElement
{
	protected $content;
	protected $isHTML;
	
	public function __construct($label, $text, $id = null)
	{
		$id = $id === null ? 'a'.uniqid() : $id;
		parent::__construct('', $label, '', $id, array('class', 'style', 'id'));
		$this->content = $text;
		$this->isHTML  = false;
	}
	
	public function render($redaxo)
	{
		if ($redaxo == 41) {
			$this->setAttribute('style', 'line-height:16px');
		}
		elseif ($redaxo == 42) {
			$this->setAttribute('style', 'line-height:21px');
		}
		
		$content = $this->isHTML ? $this->content : nl2br(wv_html($this->content));
		return '<span '.$this->getAttributeString().'>'.$content.'</span>';
	}
	
	public function setIsHTML($isHTML)
	{
		$this->isHTML = $isHTML ? true : false;
	}
}
