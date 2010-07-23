<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * @package redaxo4
 */
class sly_A1_Export_Database
{
	protected $filename;

	public function __construct()
	{
		$this->filename = '';
	}

	public function export($filename)
	{
		global $REX, $I18N;

		$this->filename = $filename;

		$fp = @fopen($this->filename, 'wb');
		if (!$fp) return false;

		$sql        = new rex_sql();
		$tables     = $sql->getArray('SHOW TABLES LIKE "'.$REX['TABLE_PREFIX'].'%"');
		$tables     = array_map('reset', $tables);
		$nl         = "\n";
		$insertSize = 5000;

		rex_register_extension_point('A1_BEFORE_DB_EXPORT');

		// Versionsstempel hinzufügen

		fwrite($fp, '## Redaxo Database Dump Version '.$REX['VERSION'].$nl);
		fwrite($fp, '## Prefix '.$REX['TABLE_PREFIX'].$nl);
		fwrite($fp, '## charset '.$I18N->msg('htmlcharset').$nl.$nl);

		foreach ($tables as $table) {
			if (!$this->includeTable($table)) {
				continue;
			}
			// CREATE-Statement

			$create = reset($sql->getArray("SHOW CREATE TABLE `$table`"));
			$create = $create['Create Table'];

			fwrite($fp, "DROP TABLE IF EXISTS `$table`;\n");
			fwrite($fp, "$create;\n");

			// Daten-Export vorbereiten

			$fields = $this->getFields($sql, $table);
			$start  = 0;
			$max    = $insertSize;

			do {
				$sql->freeResult();
				$sql->setQuery("SELECT * FROM `$table` LIMIT $start,$max");

				if ($sql->getRows() > 0 && $start == 0) {
					fwrite($fp, "\n/*!40000 ALTER TABLE `$table` DISABLE KEYS */;");
				}
				elseif ($sql->getRows() == 0) {
					break;
				}

				$start += $max;
				$values = array();

				for ($i = 0; $i < $sql->rows; $i++, $sql->next()) {
					$values[] = $this->getRecord($sql, $fields);
				}

				if (!empty($values)) {
					$values = implode(',', $values);
					fwrite($fp, "\nINSERT INTO `$table` VALUES $values;");
					unset($values);
				}
			}
			while ($sql->getRows() >= $max);

			if ($start > 0) {
				fwrite($fp, "\n/*!40000 ALTER TABLE `$table` ENABLE KEYS */;");
			}
		}

		// Den Dateiinhalt geben wir nur dann weiter, wenn es unbedingt notwendig ist.

		$hasContent = true;

		if (rex_extension_is_registered('A1_AFTER_DB_EXPORT')) {
			fclose($fp);
			$hashContent = $this->handleExtensions($filename);
		}

	  return $hasContent;
	}

	protected function includeTable($table)
	{
		global $REX;

		$prefix = $REX['TABLE_PREFIX'];
		$tmp    = $REX['TEMP_PREFIX'];

		return
			strstr($table, $prefix) == $table &&                      // Nur Tabellen mit dem aktuellen Präfix
			$table != $prefix.'user' &&                               // User-Tabelle nicht exportieren
			substr($table, 0, strlen($prefix.$tmp)) != $prefix.$tmp; // Tabellen die mit rex_tmp_ beginnnen, werden nicht exportiert!
	}

	protected function getFields($sql, $table)
	{
		$fields = $sql->getArray("SHOW FIELDS FROM `$table`");

		foreach ($fields as &$field) {
			if (preg_match('#^(bigint|int|smallint|mediumint|tinyint|timestamp)#i', $field['Type'])) {
				$field = 'int';
			}
			elseif (preg_match('#^(float|double|decimal)#', $field['Type'])) {
				$field = 'double';
			}
			elseif (preg_match('#^(char|varchar|text|longtext|mediumtext|tinytext)#', $field['Type'])) {
				$field = 'string';
			}
		}

		return $fields;
	}

	protected function getRecord($sql, $fields)
	{
		$record = array();

		foreach ($fields as $idx => $type) {
			$column = $sql->getValue($idx);

			switch ($type) {
				case 'int':
					$record[] = intval($column);
					break;

				case 'double':
					$record[] = sprintf('%.10F', (double) $column);
					break;

				case 'string':
				default:
					$record[] = "'".mysql_real_escape_string($column)."'";
					break;
			}
		}

		return '('.implode(',', $record).')';
	}

	protected function handleExtensions($filename)
	{
		$content    = file_get_contents($filename);
		$hashBefore = md5($content);
		$content    = rex_register_extension_point('A1_AFTER_DB_EXPORT', $content);
		$hashAfter  = md5($content);

		if ($hashAfter != $hashBefore) {
			file_put_contents($filename, $content);
		}

		return !empty($content);
	}
}
