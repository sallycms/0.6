<?php

class sly_View
{
	private static $instances = array();
	
	public static function factory($type = 'XHTML')
	{
		if (empty(self::$instances[$type])) {
			$className = 'sly_View_'.$type;
			self::$instances[$type] = new $className();
		}
		
		return self::$instances[$type];
	}
}
