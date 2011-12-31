<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/*
 Make sure Doctrine 2.1+ is placed in your system's include_path and (this
 should go without saying) you're running PHP 5.3+.

 Remember to update the version number down in the end of this file when
 releasing a new 0.x release.
*/

namespace Doctrine;
use Doctrine\DBAL\Schema\Table as Table;
use Doctrine\DBAL\Types\Type as Type;
use Doctrine\DBAL\Schema\Schema as Schema;

////////////////////////////////////////////////////////////////////////////////
// some helpers :)

function serialCol(Table $table, $name) {
	intCol($table, $name, true, 11, null, true);
}

function revisionCol(Table $table) {
	intCol($table, 'revision', true, 11, 0);
}

function userCols(Table $table) {
	intCol($table,    'createdate');
	intCol($table,    'updatedate');
	stringCol($table, 'createuser');
	stringCol($table, 'updateuser');
}

function intCol(Table $table, $name, $unsigned = true, $precision = 11, $default = null, $autoincrement = false) {
	$table->addColumn($name, 'integer', compact('unsigned', 'precision', 'default', 'autoincrement'));
}

function stringCol(Table $table, $name, $length = 255) {
	$table->addColumn($name, 'string', compact('length'));
}

function charCol(Table $table, $name, $length) {
	$table->addColumn($name, 'string', array('columndefinition' => "CHAR($length)"));
}

function boolCol(Table $table, $name) {
	$table->addColumn($name, 'boolean');
}

function blobCol(Table $table, $name) {
	return customCol($table, $name, 'BLOB NOT NULL');
}

function textCol(Table $table, $name, $null = false) {
	customCol($table, $name, 'TEXT '.($null ? 'NULL' : 'NOT NULL'));
}

function customCol(Table $table, $name, $def) {
	return $table->addColumn($name, 'string', array('columndefinition' => $def));
}

function createTable(Schema $schema, $name) {
	$table = $schema->createTable($name);

	$table->addOption('engine', 'MyISAM');
	$table->addOption('charset', 'utf8');

	return $table;
}

function trimSemicolon($str) {
	return rtrim($str, ';');
}

////////////////////////////////////////////////////////////////////////////////
// init Doctrine

require 'Doctrine/Common/ClassLoader.php';
$classLoader = new Common\ClassLoader('Doctrine');
$classLoader->register();

////////////////////////////////////////////////////////////////////////////////
// our schema

$schema = new Schema();

////////////////////////////////////////////////////////////////////////////////
// sly_article

$table = createTable($schema, 'sly_article');

intCol($table,    'id');
intCol($table,    're_id');
stringCol($table, 'name');
stringCol($table, 'catname');
intCol($table,    'catpos');
textCol($table,   'attributes');
boolCol($table,   'startpage');
intCol($table,    'pos');
stringCol($table, 'path');
intCol($table,    'status', true, 1);
stringCol($table, 'type', 64);
intCol($table,    'clang');
userCols($table);
revisionCol($table);

$table->setPrimaryKey(array('id', 'clang'));

////////////////////////////////////////////////////////////////////////////////
// sly_article_slice

$table = createTable($schema, 'sly_article_slice');

intCol($table,    'id');
intCol($table,    'clang');
stringCol($table, 'slot', 64);
intCol($table,    'pos', true, 5);
intCol($table,    'slice_id', true, 20, 0);
intCol($table,    'article_id');
userCols($table);
revisionCol($table);

$table->setPrimaryKey(array('id'));
$table->addIndex(array('article_id', 'clang'), 'find_article');

////////////////////////////////////////////////////////////////////////////////
// sly_clang

$table = createTable($schema, 'sly_clang');

serialCol($table, 'id');
stringCol($table, 'name');
stringCol($table, 'locale', 5);
revisionCol($table);

$table->setPrimaryKey(array('id'));

////////////////////////////////////////////////////////////////////////////////
// sly_clang

$table = createTable($schema, 'sly_file');

serialCol($table, 'id');
intCol($table,    're_file_id');
intCol($table,    'category_id');
textCol($table,   'attributes', true);
stringCol($table, 'filetype');
stringCol($table, 'filename');
stringCol($table, 'originalname');
intCol($table,    'filesize');
intCol($table,    'width');
intCol($table,    'height');
stringCol($table, 'title');
userCols($table);
revisionCol($table);

$table->setPrimaryKey(array('id'));
$table->addIndex(array('filename'), 'filename');

////////////////////////////////////////////////////////////////////////////////
// sly_file_category

$table = createTable($schema, 'sly_file_category');

serialCol($table, 'id');
stringCol($table, 'name');
intCol($table,    're_id');
stringCol($table, 'path');
textCol($table,   'attributes', true);
userCols($table);
revisionCol($table);

$table->setPrimaryKey(array('id'));

////////////////////////////////////////////////////////////////////////////////
// sly_user

$table = createTable($schema, 'sly_user');

serialCol($table, 'id');
customCol($table, 'name', 'VARCHAR(255) NULL');
customCol($table, 'description', 'VARCHAR(255) NULL');
stringCol($table, 'login', 50);
charCol($table,   'psw', 40);
boolCol($table,   'status');
textCol($table,   'rights');
intCol($table,    'lasttrydate', true, 11, 0);
customCol($table, 'timezone', 'VARCHAR(64) NULL');
userCols($table);
revisionCol($table);

$table->setPrimaryKey(array('id'));

////////////////////////////////////////////////////////////////////////////////
// sly_slice

$table = createTable($schema, 'sly_slice');

serialCol($table, 'id');
stringCol($table, 'module', 64);

$table->setPrimaryKey(array('id'));

////////////////////////////////////////////////////////////////////////////////
// sly_slice_value

$table = createTable($schema, 'sly_slice_value');

serialCol($table, 'id');
intCol($table,    'slice_id');
stringCol($table, 'type', 50);
stringCol($table, 'finder', 50);
textCol($table,   'value');

$table->setPrimaryKey(array('id'));
$table->addIndex(array('slice_id'), 'slice_id');

////////////////////////////////////////////////////////////////////////////////
// sly_registry

$table = createTable($schema, 'sly_registry');

stringCol($table, 'name');
$valueColumn = blobCol($table, 'value'); // we need this later on

$table->setPrimaryKey(array('name'));

////////////////////////////////////////////////////////////////////////////////
// create the actual SQL files

$platforms = array(
	'mysql'  => new DBAL\Platforms\MySqlPlatform(),
	'sqlite' => new DBAL\Platforms\SqlitePlatform(),
	'pgsql'  => new DBAL\Platforms\PostgreSqlPlatform(),
	'oci'    => new DBAL\Platforms\OraclePlatform()
);

$header = <<<HEADER
-- Sally Database Dump Version 0.6
-- Prefix sly_
HEADER;

$footer = <<<INSERT
-- populate database with some initial data
INSERT INTO sly_clang (name, locale) VALUES ('deutsch', 'de_DE');
INSERT;

foreach ($platforms as $name => $platform) {
	if ($name === 'pgsql') {
		$valueColumn->setColumnDefinition('BYTEA NOT NULL');
	}
	else {
		$valueColumn->setColumnDefinition('BLOB NOT NULL');
	}

	$queries = $schema->toSql($platform);
	$queries = array_map('Doctrine\\trimSemicolon', $queries);
	$queries = implode(";\n", $queries);

	file_put_contents("../sally/core/install/$name.sql", "$header\n\n$queries;\n\n$footer\n");
}
