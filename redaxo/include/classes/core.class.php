<?php
class Core {
	private static $instance;
	private $cache;
	
	private function __construct(){
	
	}
	
	public static function getInstance() {
		if(!sself::$instance){
			self::$instance = new Core();
		}
		return self::$instance;
	}
	
	public function setCache(ICache $cache) {
		$this->cache = $cache;
	}

} 