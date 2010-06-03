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

class sly_AddOn
{
	protected $name;
	protected $dir;
	protected $config;
	
	public function __construct($addon)
	{
		global $REX;
		
		$addon = preg_replace('#[^a-z0-9_.,@-]#i', '_', $addon);
		$base  = $REX['INCLUDE_PATH'].'/addons/';
		
		if (!is_dir($base.$addon)) {
			throw new Exception('Konnte AddOn '.$addon.' nicht finden.');
		}
		
		$this->name   = $addon;
		$this->dir    = $base.$addon;
		$this->config = array();
		
		$this->loadConfig();
	}
	
	public function load()
	{
	}
	
	public function loadConfig()
	{
		$this->config = array();
		
		if (is_file($this->dir.'/config.ini')) {
			$this->config = parse_ini_file($this->dir.'/config.ini', true);
		}
		
		if (is_file($this->dir.'/version')) {
			$this->config['addon']['version'] = file_get_contents($this->dir.'/version');
		}
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function getVersion($default = '')
	{
		return $this->getConfig('addon/version', $default);
	}
}
