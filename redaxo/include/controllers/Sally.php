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

abstract class sly_Controller_Sally extends sly_Controller_Base
{
	public function dispatch()
	{
		$layout = sly_Core::getLayout('Sally');
		
		$layout->openBuffer();
		parent::dispatch();
		$layout->closeBuffer();
		return $layout->render();
	}
}
