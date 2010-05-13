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
 * Stellt eine PDO Verbindung zur Datenbank her und hält sie vor.  
 * 
 * @author zozi@webvariants.de
 *
 */
class DB_PDO_Connection {
	
	private static $instance;
	
	private $connection;
	private $transrunning = false;
	private $helper;
	
	private function __construct(){
		
		$conf = Configuration::getInstance()->get('DB/1');
		$connString     = $this->getConnectionString($conf);
        $this->connection = new PDO($connString, $conf['LOGIN'], $conf['PSW']);
        $helpername = 'DB_PDO_Helper_'.strtoupper($conf['DRIVER']);
        $this->helper = new $helpername(); 
        
	}
	
	/**
	 * 
	 * @return DB_PDO_Connection instance 
	 */
	public static function getInstance(){
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }
	
    /**
     * 
     * @return PDO instance
     */
	public function getConnection(){
		return $this->connection;
	} 
	
	public function getHelper(){
		return $this->helper;
	}
	
	public function transRunning(){
		return $this->transrunning; 
	}
	
	public function setTransRunning($bool){
		$this->transrunning = $bool; 
	}
	
	/**
	 *  
	 * @param string Connection string für PDO
	 */
	private function getConnectionString(&$conf){
		return sprintf('%s:host=%s;dbname=%s', strtolower($conf['DRIVER']), $conf['HOST'], $conf['NAME']);
	}

}