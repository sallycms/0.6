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
 * Business Model Klasse für Mediumkategorien
 *
 * @author christoph@webvariants.de
 */
class sly_Model_Media_Category extends sly_Model_Base {
	protected $name;
	protected $re_id;
	protected $path;
	protected $createuser;
	protected $createdate;
	protected $updateuser;
	protected $updatedate;
	protected $attributes;
	protected $revision;

	protected $_attributes = array(
		'name' => 'string', 're_id' => 'int', 'path' => 'string', 'createuser' => 'string',
		'createdate' => 'int', 'updateuser' => 'string', 'updatedate' => 'int',
		'attributes' => 'string', 'revision' => 'int'
	);

	public function setName($name)             { $this->name       = $name;       }
	public function setParentId($re_id)        { $this->re_id      = $re_id;      }
	public function setPath($path)             { $this->path       = $path;       }
	public function setCreateDate($createdate) { $this->createdate = $createdate; }
	public function setUpdateDate($updatedate) { $this->updatedate = $updatedate; }
	public function setCreateUser($createuser) { $this->createuser = $createuser; }
	public function setUpdateUser($updateuser) { $this->updateuser = $updateuser; }
	public function setAttributes($attributes) { $this->attributes = $attributes; }
	public function setRevision($revision)     { $this->revision   = $revision;   }

	public function getName()       { return $this->name;       }
	public function getParentId()   { return $this->re_id;      }
	public function getPath()       { return $this->path;       }
	public function getCreateDate() { return $this->createdate; }
	public function getUpdateDate() { return $this->updatedate; }
	public function getCreateUser() { return $this->createuser; }
	public function getUpdateUser() { return $this->updateuser; }
	public function getAttributes() { return $this->attributes; }
	public function getRevision()   { return $this->revision;   }
}
