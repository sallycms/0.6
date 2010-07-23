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
 * Sally synchronisation script
 *
 * Author: Gregor Aisch / Dave Gööck / Christoph Mewes
 *
 * SUMMARY
 *
 * synchronizes database-stored templates and modules with files
 *
 * INSTALL NOTES
 *
 *   you have to set a global variable in your Eclipse environment
 *   > window > preferences > run/debug > string substitution
 *   variable = "PHP_PATH", value = "path/to/your/php/installation/"
 *   variable = "PHP_INI", value = "path/to/your/php.ini"
 *
 *   (Attention: WAMP uses wamp\Apache\bin\php.ini, not wamp\php\php.ini!)
 *
 * OPEN TODOs:
 *
 * - handling of nasty templatenames
 * - storage of meta-information for templates:
 *   + createuser
 * - storage of meta-information for modules:
 *   + active
 *   + attributes (such as ctypes)
 *   + createuser
 */

RedaxoSync::initialize();
RedaxoSync::synchronize();

class RedaxoSync
{
	private static $verbose        = false;
	private static $rebuild_cache  = false;
	private static $REX            = null;
	private static $metaInfosCache = array();

	// directories
	const REDAXO_DIR   = '/redaxo/';
	const TEMPLATE_DIR = '/develop/templates/';
	const ACTIONS_DIR  = '/develop/actions/';
	const MODULES_DIR  = '/develop/modules/';

	// patterns
	const TEMPLATE_SUFFIX         = '.template.php';
	const MODULES_IN_SUFFIX       = '.input.module.php';
	const MODULES_OUT_SUFFIX      = '.output.module.php';
	const ACTIONS_PREVIEW_SUFFIX  = '.preview.action.php';
	const ACTIONS_PRESAVE_SUFFIX  = '.presave.action.php';
	const ACTIONS_POSTSAVE_SUFFIX = '.postsave.action.php';

	public static function initialize()
	{
		if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
			// suppress errors in REDAXO
			error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);
		}

		$configFile = 'data/dyn/internal/sally/config/sly_local.php';

		if (file_exists($configFile)) {
			include_once $configFile;

			self::$REX = $config;
			self::debug('sly_local.php successfully included', true);
			self::openDBConnection();
		}
		else {
			self::debug('couldn\'t find Sally\'s sly_local.php');
			exit(-1);
		}
	}

	public static function synchronize()
	{
		self::$rebuild_cache = false; // article-cache will be regenerated in case of any changes

		self::synchronizeDir(self::ACTIONS_DIR, self::ACTIONS_PREVIEW_SUFFIX);
		self::synchronizeDir(self::ACTIONS_DIR, self::ACTIONS_PRESAVE_SUFFIX);
		self::synchronizeDir(self::ACTIONS_DIR, self::ACTIONS_POSTSAVE_SUFFIX);
		self::synchronizeDir(self::MODULES_DIR, self::MODULES_IN_SUFFIX);
		self::synchronizeDir(self::MODULES_DIR, self::MODULES_OUT_SUFFIX);
		self::synchronizeDir(self::TEMPLATE_DIR, self::TEMPLATE_SUFFIX);

		if (self::$rebuild_cache) self::clearRedaxoCache();

		self::debug('rex_sync end');
	}

	/*
	 * private functions
	 */

	private static function debug($msg, $verboseOnly = false)
	{
		if (empty($_SERVER['REMOTE_ADDR'])) $nl = PHP_EOL;
		else $nl = "<br />";
		if (self::$verbose || !$verboseOnly) print $msg.$nl;
	}

	private static function openDBConnection()
	{
		$connection = mysql_connect(self::$REX['DATABASE']['HOST'], self::$REX['DATABASE']['LOGIN'], self::$REX['DATABASE']['PASSWORD']);

		if (!$connection) {
			self::debug('error while connecting to database: '.mysql_error());
			exit(-1);
		}
		else {
			mysql_select_db(self::$REX['DATABASE']['NAME']);
			self::debug('successfully connected to database', true);
		}
	}

	private static function synchronizeDir($dirname, $suffix)
	{
		$location = dirname(__FILE__).$dirname;

		if (is_dir($location)) {
			$files = glob("$location/*$suffix");

			foreach ($files as $file) {
				self::debug('found file '.$file, true);
				self::processFile($location, $suffix, basename($file));
			}
		}
		else {
			self::debug($location.' is not a directory');
		}
	}

	private static function processFile($location, $suffix, $filename)
	{
		$objectName = substr($filename, 0, -strlen($suffix));
		if (empty($objectName)) return;

		$content = file_get_contents($location.$filename);
		if (empty($content)) return;

		$type = self::getType($suffix);
		if (empty($type)) return;

		$subtype = self::getSubType($suffix, $type);

		// try to get module id from meta-attributes
		$id = self::getMetaInfo($content, 'param', 'id');
		// try to get object-id from object-name
		if (is_null($id)) $id = self::findID($objectName, $type);
		if (is_null($id)) {
			self::debug('unable to identify input data from '.$filename);
			return;
		}

		// fetch data from db
		$res = mysql_query('SELECT * FROM '.self::$REX['DATABASE']['TABLE_PREFIX'].$type.' WHERE id = '.$id);
		if (!$res) {
			self::debug(mysql_error());
			return;
		}

		$contentField = self::getDBContentFieldName($type, $subtype);
		if ($data = mysql_fetch_assoc($res)) {
			if (!empty($contentField)) {
				if ($content != $data[$contentField]) {
					self::updateData($type, $subtype, $content, $contentField, $objectName, $id);
				}
			}
		}
		else {
			self::insertData($type, $subtype, $content, $contentField, $objectName, $id);
		}
	}

	private static function updateData($type, $subtype, $content, $contentField, $objectName, $id)
	{
		$res = true;
		if ($type == 'action')   $res = self::updateAction($subtype, $content, $contentField, $objectName, $id);
		if ($type == 'module')   $res = self::updateModule($subtype, $content, $contentField, $objectName, $id);
		if ($type == 'template') $res = self::updateTemplate($subtype, $content, $contentField, $objectName, $id);

		if (is_string($res)) {
			self::debug('error while updating '.$type.' ('.$subtype.'): "'.$objectName.'":   '.$res);
		}
		elseif (!$res) {
			self::debug('error while updating '.$type.' ('.$subtype.'): "'.$objectName.'"');
			self::debug(mysql_error());
		}
		else {
			self::debug('updated '.$type.' ('.$subtype.'): "'.$objectName.'"');
			self::$rebuild_cache = true;
		}
	}

	private static function updateModule($subtype, $content, $contentField, $objectName, $id)
	{
		$title   = self::getMetaInfo($content, 'param', 'name');
		$actions = self::getMetaInfo($content, 'param', 'actions');
		if (empty($title)) $title = $objectName;

		ob_start();

		if (false && eval("return true; ?> $content") == false) {
			$error = ob_get_clean();
			$error = trim(str_replace("Parse error: syntax error, ", "", $error));
			$error = preg_replace("/in .:[^:]+ : eval\\(\\)'d code/s","in $objectName.module.php", $error);
			return $error;
		}

		ob_end_clean();

		$res = mysql_query(
			'UPDATE '.self::$REX['DATABASE']['TABLE_PREFIX'].'module '.
			'SET '.$contentField.' = "'.addslashes($content).'", '.
			'updatedate = UNIX_TIMESTAMP() '.
			(!empty($title) ? ', name = "'.trim($title).'" ' : '').
			'WHERE id = '.$id
		);

		if ($res) $res = self::updateModuleActions($actions, $id);

		return $res;
	}

	private static function updateModuleActions($actions, $id)
	{
		$oldActions = array();
		$res        = mysql_query('SELECT action_id, id FROM '.self::$REX['DATABASE']['TABLE_PREFIX'].'module_action WHERE module_id = '.$id.'');

		if (!$res) {
			self::debug(mysql_error());
			return;
		}

		while ($action = mysql_fetch_assoc($res)) {
			$oldActions[$action['action_id']] = $action['id'];
		}

		if (is_array($actions)) {
			foreach ($actions as $action) {
				if (!isset($oldActions[$action])) {
					$res = mysql_query('INSERT INTO '.self::$REX['DATABASE']['TABLE_PREFIX'].'module_action (module_id, action_id) VALUES ('.$id.', '.$action.')');
				}
				else {
					unset($oldActions[$action]);
				}
			}
		}

		$res = true;

		foreach ($oldActions as $action => $module) {
			$res &= mysql_query('DELETE FROM '.self::$REX['DATABASE']['TABLE_PREFIX'].'module_action WHERE module_id = '.$id.' AND action_id = '.$action);
			if (!$res) {
				self::debug('error while updating '.type.' / '.$subtype.': "'.$name.'"');
				self::debug(mysql_error());
			}
		}

		return $res;
	}

	private static function updateTemplate($subtype, $content, $contentField, $objectName, $id)
	{
		$title  = self::getMetaInfo($content, 'param', 'name');
		$active = self::getMetaInfo($content, 'param', 'active');
		$ctype  = self::getMetaInfo($content, 'attribute', 'ctype');
		if (empty($title)) $title = $objectName;

		$attributes = array();
		if (is_array($ctype)) $attributes['ctype'] = $ctype;
		else $attributes['ctype'] = array();

		ob_start();

		if (false && eval("return true; ?> $content") == false) {
			$error = ob_get_clean();
			$error = trim(str_replace("Parse error: syntax error, ", "", $error));
			$error = preg_replace("/in .:[^:]+ : eval\\(\\)'d code/s","in $objectName.template.php", $error);
			return $error;
		}

		ob_end_clean();

		return mysql_query(
			'UPDATE '.self::$REX['DATABASE']['TABLE_PREFIX'].'template '.
			'SET name = "'.trim($title).'", '.
			'content = "'.addslashes($content).'", '.
		    'updatedate = UNIX_TIMESTAMP()'.
			(isset($active) ? ', active = '.intval($active).' ' : '') .
			(!empty($attributes) ? ', attributes = "'.addslashes(serialize($attributes)).'" ' : '') .
			'WHERE id = '.$id
		);
	}

	private static function updateAction($subtype, $content, $contentField, $objectName, $id)
	{
		$title  = self::getMetaInfo($content, 'param', 'name');
		$add    = intval(self::getMetaInfo($content, 'event', 'ADD'));
		$edit   = intval(self::getMetaInfo($content, 'event', 'EDIT'));
		$delete = intval(self::getMetaInfo($content, 'event', 'DELETE'));

		if (empty($title)) $title = $objectName;
		$bitmask = ($add == 1 ? 1 : 0) + ($edit == 1 ? 2 : 0) + ($delete == 1 ? 4 : 0);

		return mysql_query(
			'UPDATE '.self::$REX['DATABASE']['TABLE_PREFIX'].'action '.
			'SET '.$contentField.' = "'.addslashes($content).'", updatedate = UNIX_TIMESTAMP() '.
			(!empty($title) ? ', name = "'.trim($title).'" ' : '').
			', '.$contentField.'mode = "'.$bitmask.'" '.
			'WHERE id = '.$id
		);
	}

	private static function insertData($type, $subtype, $content, $contentField, $objectName, $id)
	{
		$res = true;
		if ($type == 'action')   $res = self::insertAction($subtype, $content, $contentField, $objectName, $id);
		if ($type == 'module')   $res = self::insertModule($subtype, $content, $contentField, $objectName, $id);
		if ($type == 'template') $res = self::insertTemplate($subtype, $content, $contentField, $objectName, $id);

		if (is_string($res)) {
			self::debug('error while updating '.$type.' ('.$subtype.'): "'.$objectName.'":   '.$res);
		}
		elseif (!$res) {
			self::debug('error while creating '.$type.' ('.$subtype.'): "'.$objectName.'"');
			self::debug(mysql_error());
		}
		else {
			self::debug('created '.$type.' ('.$subtype.'): "'.$objectName.'"');
			self::$rebuild_cache = true;
		}

	}

	private static function insertModule($subtype, $content, $contentField, $objectName, $id)
	{
		$title   = self::getMetaInfo($content, 'param', 'name');
		$actions = self::getMetaInfo($content, 'param', 'actions');
		if (empty($title)) $title = $objectName;

		ob_start();

		if (false && eval("return true; ?> $content") == false) {
			$error = ob_get_clean();
			$error = trim(str_replace("Parse error: syntax error, ", "", $error));
			$error = preg_replace("/in .:[^:]+ : eval\\(\\)'d code/s","in $objectName.module.php", $error);
			return $error;
		}

		ob_end_clean();

		$res = mysql_query(
			'INSERT INTO '.self::$REX['DATABASE']['TABLE_PREFIX'].'module ' .
			'(id, name, '.$contentField.', createdate, createuser) VALUES '.
			'('.$id.', "'.trim($title).'", "'.addslashes($content).'", UNIX_TIMESTAMP(), "admin")'
		);

		if ($res) $res = self::updateModuleActions($actions, $id);

		return $res;
	}

	private static function insertTemplate($subtype, $content, $contentField, $objectName, $id)
	{
		$title  = self::getMetaInfo($content, 'param', 'name');
		$active = self::getMetaInfo($content, 'param', 'active');
		$ctype  = self::getMetaInfo($content, 'attribute', 'ctype');
		if (empty($title)) $title = $objectName;

		$attributes = array();
		if (is_array($ctype)) $attributes['ctype'] = $ctype;
		else $attributes['ctype'] = array();

		ob_start();

		if (false && eval("return true; ?> $content") == false) {
			$error = ob_get_clean();
			$error = trim(str_replace("Parse error: syntax error, ", "", $error));
			$error = preg_replace("/in .:[^:]+ : eval\\(\\)'d code/s","in $objectName.template.php", $error);
			return $error;
		}

		ob_end_clean();

		$attributesString = (!empty($attributes) ? addslashes(serialize($attributes)) : '');

		return mysql_query(
			'INSERT INTO '.self::$REX['DATABASE']['TABLE_PREFIX'].'template ' .
			'(id, name, content, createdate, createuser, active, label, attributes) VALUES ' .
			'('.$id.', "'.trim($title).'", "'. addslashes($content) .'", NOW(), "admin", '.(isset($active) ? intval($active) : 0).', "", "'.$attributesString.'")'
		);
	}

	private static function insertAction($subtype, $content, $contentField, $objectName, $id)
	{
		$title  = self::getMetaInfo($content, 'param', 'name');
		$add    = intval(self::getMetaInfo($content, 'event', 'ADD'));
		$edit   = intval(self::getMetaInfo($content, 'event', 'EDIT'));
		$delete = intval(self::getMetaInfo($content, 'event', 'DELETE'));

		if (empty($title)) $title = $objectName;
		$bitmask = ($add == 1 ? 1 : 0) + ($edit == 1 ? 2 : 0) + ($delete == 1 ? 4 : 0);

		return mysql_query(
			'INSERT INTO '.self::$REX['DATABASE']['TABLE_PREFIX'].'action ' .
			'(id, name, '.$contentField.', '.$contentField.'mode, createdate, createuser) VALUES '.
			'('.$id.', "'.trim($title).'", "'.addslashes($content).'", '.$bitmask.', UNIX_TIMESTAMP(), "admin")'
		);
	}

	private static function getDBContentFieldName($type, $subtype)
	{
		if ($type == 'template') return 'content';

		if ($type == 'module') {
			if ($subtype == 'input') return 'eingabe';
			if ($subtype == 'output') return 'ausgabe';
		}

		if ($type == 'action') {
			if (!empty($subtype)) return $subtype;
		}

		return null;
	}

	private static function findID($onjectName, $type)
	{
		if (empty($type)) return null;

		$id  = null;
		$res = mysql_query('SELECT id FROM '.self::$REX['DATABASE']['TABLE_PREFIX'].$type.' WHERE name = "'.$onjectName.'"');

		if (!$res) {
			self::debug(mysql_error());
		}
		elseif ($row = mysql_fetch_assoc($res)) {
			$id = $row['id'];
			mysql_free_result($res);
		}

		return $id;
	}

	private static function getType($suffix)
	{
		if (preg_match('/.*(template|module|action)\.php/', $suffix, $result) > 0) {
			return $result[1];
		}

		return null;
	}

	private static function getSubType($suffix, $type)
	{
		if (preg_match('/.*(postsave|presave|preview|input|output)\.'.$type.'.php/', $suffix, $result) > 0) {
			return $result[1];
		}

		return null;
	}

	private static function clearRedaxoCache()
	{
		$path = dirname(__FILE__).'/data/dyn/internal/sally/templates/';
		self::removeAllFiles($path, 'template');

		$path = dirname(__FILE__).'/data/dyn/internal/sally/articles/';
		self::removeAllFiles($path, 'alist');
		self::removeAllFiles($path, 'clist');
		self::removeAllFiles($path, 'article');
		self::removeAllFiles($path, 'content');
		self::removeAllFiles($path, 'slice');

		self::debug('cleared templates and articles cache');
	}

	private static function removeAllFiles($path, $ext)
	{
		if (is_dir($path)) {
			$files = glob("$path/*.$ext");
			array_map('unlink', $files);
		}
	}

	private static function getMetaInfo($content, $token, $param)
	{
		$hash = md5($content);

		if (!isset(self::$metaInfosCache[$hash])) {
			self::$metaInfosCache[$hash] = self::getAllMetaInfos($content);
		}

		if (isset(self::$metaInfosCache[$hash][$token][$param])) {
			return self::$metaInfosCache[$hash][$token][$param];
		}

		return null;
	}

	private static function getAllMetaInfos($content)
	{
		static $regex = '/@rex_(\w+)\s+(\w+)\s+(.+)/';

		$infos = array();
		$lines = explode("\n", $content);

		foreach ($lines as $line) {
			if (preg_match($regex, $line, $result) > 0) {
				if ($result[1] == 'attribute' || $result[2] == 'actions') eval('$r = '.$result[3].';');
				else $r = $result[3];
				$infos[$result[1]][$result[2]] = $r;
			}
		}

		return $infos;
	}
}
