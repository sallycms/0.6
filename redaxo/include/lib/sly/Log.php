<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Simple logging class
 *
 * This class provides an easy way to log data to the disk. ALl log files will
 * be placed in data/dyn/internal/sally/logs.
 *
 * @author christoph@webvariants.de
 * @since  0.1
 */
class sly_Log {
	protected $filename;    ///< string  the current target file
	protected $format;      ///< string  the line format to use

	private static $instances = array();

	const LEVEL_INFO    = 0;
	const LEVEL_WARNING = 1;
	const LEVEL_ERROR   = 2;

	const FORMAT_SIMPLE   = '[%date% %time%] %typename%: %message%';
	const FORMAT_EXTENDED = '[%date% %time%] %typename%: %message% (IP: %ip%, Hostname: %hostname%)';
	const FORMAT_CALLER   = '[%date% %time%] %typename%: %message% (%caller%, %called%)';

	/**
	 * Constructor
	 *
	 * @param string  $filename  the filename to use
	 */
	private function __construct($filename) {
		$this->filename = $filename;
		$this->format   = self::FORMAT_SIMPLE;
	}

	/**
	 * Get a logger instance
	 *
	 * This method returns the instance for a specific file. The extension ".log"
	 * will be appended automatically to the given $name.
	 *
	 * Only [a-z], [0-9], _ and - are allowed in $name.
	 *
	 * @param  string $name  the target file's basename (without extension)
	 * @return sly_Log       the logger instance
	 */
	public static function getInstance($name) {
		$name = preg_replace('#[^a-z0-9-_]#i', '_', $name);
		$dir  = SLY_DYNFOLDER.'/internal/sally/logs';

		if (!sly_Util_Directory::create($dir)) {
			throw new sly_Exception('Could not create logging directory in '.$dir.'.');
		}

		return new self($dir.'/'.$name.'.log');
	}

	/**
	 * Set the log format to use
	 *
	 * You can use the following placeholders in $format:
	 *
	 *  - %date%      (%Y-%m-%d)
	 *  - %time%      (%H:%M:%S)
	 *  - %typename%  (Info, Error, Warning or Dump)
	 *  - %message%   (the user defined message)
	 *  - %ip%        (the current user's IP or "<not set>" for cmdline calls)
	 *  - %hostname%  (the current user's hostname or "N/A" if not available)
	 *  - %caller%    (the function the called the logger instance)
	 *  - %called%    (the line number where the logger was called)
	 *
	 * Everything else in $format will be left as is.
	 *
	 * @param string $format  the new log format
	 */
	public function setFormat($format) {
		$this->format = trim($format);
	}

	public function info($text, $depth = 1)    { return $this->log(self::LEVEL_INFO,    $text, $depth + 1); }
	public function warning($text, $depth = 1) { return $this->log(self::LEVEL_WARNING, $text, $depth + 1); }
	public function error($text, $depth = 1)   { return $this->log(self::LEVEL_ERROR,   $text, $depth + 1); }

	public function log($level, $message, $depth = 1) {
		if ($level != self::LEVEL_INFO && $level != self::LEVEL_ERROR && $level != self::LEVEL_WARNING) {
			throw new sly_Exception('Unbekannter Nachrichtentyp: '.$level);
		}

		switch ($level) {
			case self::LEVEL_ERROR:
				$line = $this->replacePlaceholders($this->format, 'Error', $depth);
				break;

			case self::LEVEL_WARNING:
				$line = $this->replacePlaceholders($this->format, 'Warning', $depth);
				break;

			case self::LEVEL_INFO:
				$line = $this->replacePlaceholders($this->format, 'Info', $depth);
				break;
		}

		$line = str_replace('%message%', $message, $line);
		$line = trim($line);

		return file_put_contents($this->filename, $line."\n", FILE_APPEND) > 0;
	}

	public function dump($name, $value, $force_style = null, $depth = 1) {
		$line = $this->replacePlaceholders($this->format, 'Dump', $depth);

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

	protected function replacePlaceholders($line, $typename, $depth) {
		$ip   = empty($_SERVER['REMOTE_ADDR']) ? '<not set>' : $_SERVER['REMOTE_ADDR'];
		$line = str_replace('%ip%', $ip, $line);
		$line = str_replace('%date%', strftime('%Y-%m-%d'), $line);
		$line = str_replace('%time%', strftime('%H:%M:%S'), $line);
		$line = str_replace('%typename%', $typename, $line);

		if (strpos($line, '%hostname%') !== false) {
			$host = empty($_SERVER['REMOTE_ADDR']) ? 'N/A' : gethostbyaddr($_SERVER['REMOTE_ADDR']);
			$line = str_replace('%hostname%', $host, $line);
		}

		if (strpos($line, '%caller%') !== false || strpos($line, '%called%') !== false) {
			$caller = $this->getCaller($depth + 1);
			$file   = $caller['line'] == 0 ? '[PHPcore]' : basename($caller['file']).':'.$caller['line'];
			$line   = str_replace('%called%', $file, $line);

			if ($caller['caller'] != '[PHPcore]') {
				$line = str_replace('%caller%', $caller['caller'].'()', $line);
			}
			else {
				$line = str_replace('%caller%', $caller['caller'], $line);
			}
		}

		return $line;
	}

	protected function getCaller($depth) {
		$trace = array_slice(debug_backtrace(), $depth);

		// Wurde getCaller() nicht innerhalb einer Funktion aufgerufen?

		if (empty($trace)) {
			return array();
		}

		// OOP-Aufruf?

		if (isset($trace[0]['class'])) {
			$trace[0]['function'] = $trace[0]['class'].$trace[0]['type'].$trace[0]['function'];
		}

		if (isset($trace[1]['class'])) {
			$trace[1]['function'] = $trace[1]['class'].$trace[1]['type'].$trace[1]['function'];
		}

		// Im ersten Element steht, von wo aus die Funktion aufgerufen
		// wurde.

		$calledBy = array(
			'file'     => isset($trace[0]['file']) ? $trace[0]['file'] : '[PHPcore]',
			'line'     => isset($trace[0]['line']) ? $trace[0]['line'] : 0,
			'function' => $trace[0]['function'], // der Name der Fkt., die getCaller() aufgerufen hat
			'caller'   => '[PHPcore]',
			'byphp'    => false
		);

		// Falls wir noch ein Element im Array haben, dann wissen wir
		// sogar, wie die Funktion heißt, die die Funktion aufgerufen
		// hat.

		if (count($trace) >= 2) {
			$calledBy['caller'] = $trace[1]['function'];
		}

		// Falls die Funktion durch PHP aufgerufen wurde (array_walk o.ä.)
		// holen wir nicht den Aufruf durch array_walk (da wir das ja nicht
		// wissen können), sondern den Aufruf von array_walk.

		// Der Aufruf könnte auch von z.B. register_shutdown_function()
		// kommen. In dem Fall haben wir hingegen keine Angabe, wo
		// genau im Code register_shutdown_function aufgerufen
		// wurde.

		if ($calledBy['file'] == '[PHPcore]' && count($trace) >= 2) {
			$calledBy['file']  = $trace[1]['file'];
			$calledBy['line']  = $trace[1]['line'];
			$calledBy['byphp'] = true;
		}

		return $calledBy;
	}
}
