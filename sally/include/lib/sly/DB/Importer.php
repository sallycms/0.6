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
 * @ingroup database
 */
class sly_DB_Importer
{
	protected $filename;
	protected $returnValues;
	protected $content;
	protected $prefix;
	protected $charset;
	protected $queries;

	public function __construct()
	{
		$this->reset();
	}

	protected function reset($filename = '')
	{
		$this->returnValues['state']   = false;
		$this->returnValues['message'] = '';

		$this->content  = '';
		$this->prefix   = '';
		$this->charset  = '';
		$this->queries  = array();
		$this->filename = $filename;
	}

	public function import($filename)
	{
		global $REX, $I18N;

		$this->reset($filename);

		// Vorbedingungen abtesten

		if (!$this->prepareImport()) {
			return $this->returnValues;
		}

		$msg   = '';
		$error = array();

		// Extensions auslösen

		$filesize = filesize($filename);
		$msg      = rex_register_extension_point('SLY_DB_IMPORTER_BEFORE', $msg, array(
			'content'  => $this->content,
			'filename' => $filename,
			'filesize' => $filesize
		));

		// Import durchführen

		$error = $this->executeQueries();

		if (!empty($error)) {
			$this->returnValues['message'] = implode("<br />\n", $error);
			return $this->returnValues;
		}

		$msg .= $I18N->msg('importer_database_imported').'. '.$I18N->msg('importer_entry_count', count($this->queries)).'<br />';

		// User-Tabelle ggf. anlegen, falls nicht vorhanden

		$dbObject = $this->checkForUserTable();

		if ($dbObject instanceof rex_sql && $dbObject->hasError()) {
			$msg   = '';
			$error = $dbObject->getError();
		}

		// Cache erneuern, wenn alles OK lief

		if (empty($error)) {
			$msg = $this->regenerateCache($msg);
		}

		$this->returnValues['message'] = $msg;
		return $this->returnValues;
	}

	protected function prepareImport()
	{
		try {
			$this->checkFilename();

			$this->content = file_get_contents($this->filename);

			$this->checkVersion();
			$this->checkPrefix();
			$this->checkCharset();
			$this->replacePrefix();

			return true;
		}
		catch (Exception $e) {
			return false;
		}
	}

	protected function checkFilename()
	{
		global $I18N;

		if (empty($this->filename) || substr($this->filename, -4) != '.sql') {
			$this->returnValues['message'] = $I18N->msg('importer_no_import_file_chosen_or_wrong_version').'<br />';
			throw new Exception('bad filename');
		}
	}

	protected function checkVersion()
	{
		global $REX, $I18N;

		// ## Redaxo Database Dump Version x.x

		$version = strpos($this->content, '## Sally Database Dump Version '.$REX['VERSION']);

		if ($version === false) {
			$this->returnValues['message'] = $I18N->msg('importer_no_valid_import_file').'. [## Redaxo Database Dump Version '.$REX['VERSION'].'] is missing.<br />';
			throw new Exception('bad version');
		}

		$this->content = trim(str_replace('## Sally Database Dump Version '.$REX['VERSION'], '', $this->content));
	}

	protected function checkPrefix()
	{
		global $REX, $I18N;

		// ## Prefix xxx_

		if (preg_match('/^## Prefix ([a-zA-Z0-9\_]*)/', $this->content, $matches) && isset($matches[1])) {
			$this->prefix  = $matches[1];
			$this->content = trim(str_replace('## Prefix '.$this->prefix, '', $this->content));
		}
		else {
			$this->returnValues['message'] = $I18N->msg('importer_no_valid_import_file').'. [## Prefix '. $REX['DATABASE']['TABLE_PREFIX'] .'] is missing.<br />';
			throw new Exception('bad prefix');
		}
	}

	protected function checkCharset()
	{
		global $REX, $I18N;

		if (preg_match('/^## charset ([a-zA-Z0-9\_\-]*)/', $this->content, $matches) && isset($matches[1])) {
			$this->charset = $matches[1];
			$this->content = trim(str_replace('## charset '. $this->charset, '', $this->content));

			if ($I18N->msg('htmlcharset') != $this->charset) {
				$this->returnValues['message'] = $I18N->msg('importer_no_valid_charset').'. '.$I18N->msg('htmlcharset').' != '.$this->charset.'<br />';
				throw new Exception('bad charset');
			}
		}

		// Charset ist im Moment nicht zwingend notwendig

//		else {
//			$this->returnValues['message'] = $I18N->msg('importer_no_valid_import_file').'. [## Charset '. $I18N->msg('htmlcharset') .'] is missing]';
//			throw new Exception('no charset');
//		}
	}

	protected function replacePrefix()
	{
		global $REX;

		if ($REX['DATABASE']['TABLE_PREFIX'] != $this->prefix) {
			// Hier case-insensitiv ersetzen, damit alle möglich Schreibweisen (TABLE TablE, tAblE,..) ersetzt werden
			// Dies ist wichtig, da auch SQLs innerhalb von Ein/Ausgabe der Module vom rex-admin verwendet werden
			$this->content = preg_replace('/(TABLE `?)'.preg_quote($this->prefix, '/').'/i',  '$1'.$REX['DATABASE']['TABLE_PREFIX'], $this->content);
			$this->content = preg_replace('/(INTO `?)'.preg_quote($this->prefix, '/').'/i',   '$1'.$REX['DATABASE']['TABLE_PREFIX'], $this->content);
			$this->content = preg_replace('/(EXISTS `?)'.preg_quote($this->prefix, '/').'/i', '$1'.$REX['DATABASE']['TABLE_PREFIX'], $this->content);
		}
	}

	protected function splitToQuries()
	{
		$this->splitFile();

		foreach ($this->queries as $idx => $line) {
			$this->queries[$idx] = $line['query'];
		}
	}

	protected function executeQueries()
	{
		$this->splitToQuries();

		$sql   = new rex_sql();
		$error = array();

		foreach ($this->queries as $qry) {
			$sql->setQuery($qry);

			if ($sql->hasError()) {
				$error[] = $sql->getError();
			}
		}

		return $error;
	}

	protected function checkForUserTable()
	{
		global $REX;

		$tables = rex_sql::showTables();

		if (!in_array($REX['DATABASE']['TABLE_PREFIX'].'user', $tables)) {
			$createStmt = file_get_contents(SLY_INCLUDE_PATH.'/install/user.sql');
			$createStmt = str_replace('%PREFIX%', $REX['DATABASE']['TABLE_PREFIX'], $createStmt);

			$db = new rex_sql();
			$db->setQuery($createStmt);
			return $db;
		}

		return true;
	}

	protected function regenerateCache($msg)
	{
		$msg = rex_register_extension_point('SLY_DB_IMPORTER_AFTER', $msg, array(
			'content'  => $this->content,
			'filename' => $this->filename,
			'filesize' => strlen($this->content)
		));

		$this->returnValues['state'] = true;
		return $msg.rex_generateAll();
	}

	protected function splitFile()
	{
		// do not trim, see bug #1030644
		//$sql          = trim($this->content);
		$sql          = rtrim($this->content, "\n\r");
		$sql_len      = strlen($this->content);
		$char         = '';
		$string_start = '';
		$in_string    = false;
		$nothing      = true;
		$time0        = time();

		for ($i = 0; $i < $sql_len; ++$i) {
			$char = $sql[$i];

			// We are in a string, check for not escaped end of strings except for
			// backquotes that can't be escaped
			if ($in_string) {
				for (;;) {
					$i = strpos($sql, $string_start, $i);
					// No end of string found -> add the current substring to the
					// returned array
					if (!$i) {
						$this->queries[] = $sql;
						return true;
					}
					// Backquotes or no backslashes before quotes: it's indeed the
					// end of the string -> exit the loop
					elseif ($string_start == '`' || $sql[$i -1] != '\\') {
						$string_start = '';
						$in_string    = false;
						break;
					}
					// one or more Backslashes before the presumed end of string...
					else {
						// ... first checks for escaped backslashes
						$j = 2;
						$escaped_backslash = false;
						while ($i - $j > 0 && $sql[$i - $j] == '\\') {
							$escaped_backslash = !$escaped_backslash;
							$j ++;
						}
						// ... if escaped backslashes: it's really the end of the
						// string -> exit the loop
						if ($escaped_backslash) {
							$string_start = '';
							$in_string    = false;
							break;
						}
						// ... else loop
						else {
							$i ++;
						}
					} // end if...elseif...else
				} // end for
			} // end if (in string)

			// lets skip comments (/*, -- and #)
			elseif (($char == '-' && $sql_len > $i+2 && $sql[$i+1] == '-' && $sql[$i+2] <= ' ') || $char == '#' || ($char == '/' && $sql_len > $i+1 && $sql[$i+1] == '*')) {
				$i = strpos($sql, $char == '/' ? '*/' : "\n", $i);
				// didn't we hit end of string?
				if ($i === false) break;
				if ($char == '/') $i ++;
			}

			// We are not in a string, first check for delimiter...
			elseif ($char == ';') {
				// if delimiter found, add the parsed part to the returned array
				$this->queries[] = array('query' => substr($sql, 0, $i), 'empty' => $nothing);

				$nothing = true;
				$sql     = ltrim(substr($sql, min($i + 1, $sql_len)));
				$sql_len = strlen($sql);

				if ($sql_len) {
					$i = -1;
				}
				else {
					// The submited statement(s) end(s) here
					return true;
				}
			} // end else if (is delimiter)

			// ... then check for start of a string,...
			elseif ($char == '"' || $char == '\'' || $char == '`') {
				$in_string    = true;
				$nothing      = false;
				$string_start = $char;
			} // end else if (is start of string)

			elseif ($nothing) {
				$nothing = false;
			}

			// loic1: send a fake header each 30 sec. to bypass browser timeout
			$time1 = time();
			if ($time1 >= $time0 + 30) {
				$time0 = $time1;
				header('X-pmaPing: Pong');
			} // end if
		} // end for

		// add any rest to the returned array
		if (!empty ($sql) && preg_match('@[^[:space:]]+@', $sql)) {
			$this->queries[] = array('query' => $sql, 'empty' => $nothing);
		}

		return true;
	}
}
