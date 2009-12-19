<?php
class Core {
	private static $instance;
	private $cache;
	
	private function __construct(){
	
	}
	
	public static function getInstance() {
		if(!self::$instance){
			self::$instance = new Core();
		}
		return self::$instance;
	}
	
	public function setCache(ICache $cache) {
		$this->cache = $cache;
	}
	
	public function getCache(){
		return $this->cache;
	}
	
	public function hasCache(){
		return !self::getInstance()->getCache() == null;
	}

} 