<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Database dump
 *
 * This class wraps a single database dump and can be used to split it up and
 * import it again. Database dumps contain the Sally version and table prefix of
 * the system they were created in. These information needs to match when trying
 * to import them. They are encoded in the comments in the top of the file.
 *
 * This class is primarily used when installing Sally, but also by the
 * Import/Export addOn. It was moved here so that Sally can be installed without
 * having the addOn installed.
 *
 * @ingroup database
 * @author  Christoph
 * @since   0.3
 */
class sly_DB_Dump {
	protected $filename;  ///< string
	protected $headers;   ///< array
	protected $prefix;    ///< string
	protected $version;   ///< string
	protected $queries;   ///< array

	private $content;     ///< string

	/**
	 * Constructor
	 *
	 * Constructs the object, but does not yet do any work (like reading the
	 * file).
	 *
	 * @throws sly_Exception     if the file was not found
	 * @param  string $filename  the full path to the dump file
	 */
	public function __construct($filename) {
		if (!file_exists($filename)) {
			throw new sly_Exception(t('file', $filename));
		}

		$this->filename = $filename;
		$this->headers  = null;
		$this->prefix   = null;
		$this->version  = null;
		$this->queries  = null;
	}

	/**
	 * Get the headers
	 *
	 * This method will read the headers from the dump file and return it.
	 * Headers are the comments at the beginning of the dump file.
	 *
	 * @return array  the found headers
	 */
	public function getHeaders() {
		return $this->getProperty('headers');
	}

	/**
	 * Get the version
	 *
	 * This method will read the version from the dump file and return it.
	 *
	 * @return string  the found version
	 */
	public function getVersion() {
		return $this->getProperty('version');
	}

	/**
	 * Get the prefix
	 *
	 * This method will read the table prefix from the dump file and return it.
	 *
	 * @return string  the found prefix
	 */
	public function getPrefix() {
		return $this->getProperty('prefix');
	}

	/**
	 * Get the queries
	 *
	 * This method will split the dump file up into individual queries and
	 * return them. When $replaceVariables is set to true, placeholders like
	 * %TABLE_PREFIX% or %USER% are replaced. Use this only when you're sure
	 * that this will not damage the dump's content.
	 *
	 * @param  boolean $replaceVariables  see description
	 * @return array                      array of queries
	 */
	public function getQueries($replaceVariables = false) {
		$queries = $this->getProperty('queries');
		if ($replaceVariables === false) return $queries;

		foreach ($queries as $idx => $qry) {
			$queries[$idx] = self::replaceVariables($qry);
		}

		return $queries;
	}

	/**
	 * Get the dump's content
	 *
	 * This method will read the dump file and return the content. Nothing more.
	 *
	 * @return string  the content
	 */
	public function getContent() {
		return file_get_contents($this->filename);
	}

	/**
	 * Get a property
	 *
	 * A property is one of the class fields. When called for the first time, it
	 * will parse the file and extract all infos. Later on, this will just return
	 * the field without doing any more work. It's just a convenience wrapper to
	 * not have to parse to file over and over again.
	 *
	 * @return mixed  the property
	 */
	protected function getProperty($name) {
		if ($this->$name === null) $this->parse();
		return $this->$name;
	}

	/**
	 * Parse the dump
	 *
	 * This method will coordinate the main work. It reads the headers, extracts
	 * version and prefix, gets the queries and so on.
	 *
	 * @return boolean  true if successful, else false
	 */
	protected function parse() {
		try {
			$this->readHeaders();

			$this->version = $this->findHeader('#^sally database dump version ([0-9.]+)#i');
			$this->prefix  = $this->findHeader('#^prefix ([a-z0-9_]+)#i');

			$this->content = $this->getContent();
			$this->replacePrefix();
			$this->readQueries();
			$this->content = '';

			return true;
		}
		catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Read the file headers
	 *
	 * This method will read the file line by line, reading the headers. When the
	 * first non-comment is encountered, the parsing stops (so in most cases,
	 * this will read at most 2 or 3 lines).
	 *
	 * Comments can be either '--' style or '##' style.
	 */
	protected function readHeaders() {
		$f = fopen($this->filename, 'r');
		$this->headers = array();

		for (;;) {
			$line  = fgets($f, 256);
			$start = substr($line, 0, 2);

			if ($start !== '##' && $start !== '--') {
				break;
			}

			$this->headers[] = trim(substr($line, 2));
		}

		fclose($f);
	}

	/**
	 * Find a header
	 *
	 * Since headers can come in any order and have no identification, they have
	 * to be found by giving a regex and going through all of them until one is
	 * found.
	 *
	 * This method will return the contents of the first group of the given
	 * regex.
	 *
	 * @param  string  the regex to find (must contain at least one group)
	 * @return string  the first group, if found, else false
	 */
	protected function findHeader($regex) {
		foreach ($this->headers as $header) {
			if (preg_match($regex, $header, $match)) {
				return trim($match[1]);
			}
		}

		return false;
	}

	/**
	 * Replace table prefix
	 *
	 * This method will attempt to replace the table prefix for the complete
	 * file, making it compatible when importing. It does so by performing some
	 * regex magic, so it will also replace the prefix inside of the actual
	 * database contents. In many cases, this is desirable (for example, when
	 * queries are stored), but be aware that this might lead to problems with
	 * user defined (user meaning a visitor) content.
	 */
	protected function replacePrefix() {
		$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');

		if ($this->prefix && $prefix !== $this->prefix) {
			$quoted = preg_quote($this->prefix, '/');

			$this->content = preg_replace('/(TABLE `?)'.$quoted.'/i',  '$1'.$prefix, $this->content);
			$this->content = preg_replace('/(INTO `?)'.$quoted.'/i',   '$1'.$prefix, $this->content);
			$this->content = preg_replace('/(EXISTS `?)'.$quoted.'/i', '$1'.$prefix, $this->content);
		}
	}

	/**
	 * Replace placeholders
	 *
	 * This method will replace some special placeholders inside of a query.
	 *
	 *  - %USER% will be replaced with the currently logged in user's login
	 *  - %TIME% will be replaced with the current unix timestamp
	 *  - %TABLE_PREFIX% will be replaced with the table prefix
	 *
	 * @param  string $query  the query to work with
	 * @return string         the result
	 */
	public static function replaceVariables($query) {
		static $prefix = null;

		$user = sly_Util_User::getCurrentUser();

		if ($prefix === null) {
			$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
		}

		$query = str_replace('%USER%', $user ? $user->getLogin() : '', $query);
		$query = str_replace('%TIME%', time(), $query);
		$query = str_replace('%TABLE_PREFIX%', $prefix, $query);

		return $query;
	}

	/**
	 * Split up the dump file
	 *
	 * This method will perform the actual parsing of the dump. It's a copy from
	 * Adminer (before Sally 0.5, this was a copy from phpMyAdmin, but was
	 * replaced with this code since PMA uses GPLv2).
	 *
	 * @author  Adminer
	 * @license Apache License, Version 2.0
	 */
	protected function readQueries() {
		$space     = "(?:\\s|/\\*.*\\*/|(?:#|-- )[^\n]*\n|--\n)";
		$delimiter = ';';
		$offset    = 0;
		$parse     = '[\'`"]|/\\*|-- |#'; //! ` and # not everywhere
		$query     = $this->content;

		while ($query !== '') {
			if (!$offset && preg_match("~^$space*DELIMITER\\s+(.+)~i", $query, $match)) {
				$delimiter = $match[1];
				$query     = substr($query, strlen($match[0]));
			}
			else {
				preg_match('('.preg_quote($delimiter)."|$parse|\$)", $query, $match, PREG_OFFSET_CAPTURE, $offset); // should always match

				$found  = $match[0][0];
				$offset = $match[0][1] + strlen($found);

				if (!$found && rtrim($query) === '') {
					break;
				}

				if ($found && $found !== $delimiter) { // find matching quote or comment end
					while (preg_match('(' . ($found == '/*' ? '\\*/' : ($found == '[' ? ']' : (preg_match('~^(-- |#)~', $found) ? "\n" : preg_quote($found) . "|\\\\."))) . '|$)s', $query, $match, PREG_OFFSET_CAPTURE, $offset)) { //! respect sql_mode NO_BACKSLASH_ESCAPES
						$s      = $match[0][0];
						$offset = $match[0][1] + strlen($s);

						if ($s[0] != "\\") {
							break;
						}
					}
				}
				else { // end of a query
					$q      = substr($query, 0, $match[0][1]);
					$query  = substr($query, $offset);
					$offset = 0;

					$this->queries[] = trim($q);
				}
			}
		}
	}
}
