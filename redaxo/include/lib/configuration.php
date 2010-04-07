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

/**
 * Ist noch ein wrapper für $REX wird irgendwann mal umgebaut
 * 
 * @author zozi@webvariants.de
 *
 */
class Configuration{

    private $configuration;
    private static $instance;

    private function __construct() {
        global $REX;
        $this->configuration = &$REX;
    }

	/**
	 *
	 * @return Configuration
	 */
    public static function getInstance(){
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function get($path){
    	$path = explode('/', $path);
        $res = self::$instance->configuration;
        foreach($path as $step){
        	if(!isset($res[$step])) break;
            $res = $res[$step];
        }
        return $res;
    }
}
