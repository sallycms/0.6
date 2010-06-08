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

/**
 * Log-Funktionen
 *
 * @author christoph@webvariants.de
 * @since  0.1
 */
class sly_Log
{
	protected $filename;
	protected $format;
	
	private static $instances = array();
	
	const LEVEL_INFO    = 0;
	const LEVEL_WARNING = 1;
	const LEVEL_ERROR   = 2;
	
	const FORMAT_SIMPLE   = '[%date% %time%] %typename%: %message%';
	const FORMAT_EXTENDED = '[%date% %time%] %typename%: %message% (IP: %ip%, Hostname: %hostname%)';
	
	private function __construct($filename)
	{
		$this->filename = $filename;
		$this->format   = self::FORMAT_SIMPLE;
	}
	
	public static function getInstance($name)
	{
		global $REX;
		
		$name = preg_replace('#[^a-z0-9-_]#i', '_', $name);
		$dir  = $REX['DYNFOLDER'].'/internal/sally/logs';
		
		if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
			throw new sly_Exception('Konnte Log-Verzeichnis '.$dir.' nicht erzeugen.');
		}
		
		return new self($dir.'/'.$name.'.log');
	}
	
	public static function setFormat($format)
	{
		$this->format = trim($format);
	}
	
	public function info($text)    { $this->log(self::LEVEL_INFO, $text);    }
	public function warning($text) { $this->log(self::LEVEL_WARNING, $text); }
	public function error($text)   { $this->log(self::LEVEL_ERROR, $text);   }
	
	public function log($level, $message)
	{
		if ($level != self::LEVEL_INFO && $level != self::LEVEL_ERROR && $level != self::LEVEL_WARNING) {
			throw new sly_Exception('Unbekannter Nachrichtentyp: '.$level);
		}
		
		$line = $this->format;
		$line = str_replace('%message%', $message, $line);
		$line = str_replace('%ip%', $_SERVER['REMOTE_ADDR'], $line);
		$line = str_replace('%date%', strftime('%Y-%m-%d'), $line);
		$line = str_replace('%time%', strftime('%H:%M:%S'), $line);
		
		if (strpos($line, '%hostname%') !== false) {
			$host = gethostbyaddr($_SERVER['REMOTE_ADDR']);
			$line = str_replace('%hostname%', $host, $line);
		}
		
		switch ($level) {
			case self::LEVEL_ERROR:
				$line = str_replace('%typename%', 'Error', $line);
				break;
				
			case self::LEVEL_WARNING:
				$line = str_replace('%typename%', 'Warning', $line);
				break;
				
			case self::LEVEL_INFO:
				$line = str_replace('%typename%', 'Info', $line);
				break;
		}
		
		$line = trim($line);
		return file_put_contents($this->filename, $line."\n", FILE_APPEND) > 0;
	}
	
	public function dump($name, $value, $force_style = null)
	{
		$line = $this->format;
		$line = str_replace('%ip%', $_SERVER['REMOTE_ADDR'], $line);
		$line = str_replace('%date%', strftime('%Y-%m-%d'), $line);
		$line = str_replace('%time%', strftime('%H:%M:%S'), $line);
		$line = str_replace('%typename%', 'Dump', $line);
		
		if ($force_style) {
			$value = $force_style($value);
		}
		else {
			switch (gettype($value)) {
				case 'integer':
					$value = $value;
					break;
					
				case 'string':
					$value = '"'.$value.'"';
					break;
					
				case 'boolean':
					$value = $value ? 'true' : 'false';
					break;
					
				case 'double':
					$value = str_replace('.', ',', round($value, 8));
					break;
				
				case 'array':
				case 'object':
					$value = print_r($value, true);
					break;
					
				case 'NULL':
					$value = 'null';
					break;
				
				case 'resource':
				default:
					$value = var_dump($value);
					break;
			}
		}
		
		$line = str_replace('%message%', '$'.$name.' = '.$value, $line);
		$line = trim($line);
		return file_put_contents($this->filename, $line."\n", FILE_APPEND) > 0;
	}
}
