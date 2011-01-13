<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

/**
 * Business Model Klasse für Templates
 *
 * @author christoph@webvariants.de
 */
class sly_Model_Template extends sly_Model_Base {
	protected $updateuser;
	protected $name;
	protected $createdate;
	protected $label;
	protected $content;
	protected $updatedate;
	protected $createuser;
	protected $active;
	protected $attributes;
	protected $revision;

	protected $_attributes = array(
		'updateuser' => 'string', 'name' => 'string', 'createdate' => 'int',
		'label' => 'string', 'content' => 'string', 'updatedate' => 'int',
		'createuser' => 'string', 'active' => 'int', 'attributes' => 'string',
		'revision' => 'int'
	);

	public function getUpdateUser() { return $this->updateuser; }
	public function getName()       { return $this->name;       }
	public function getCreateDate() { return $this->createdate; }
	public function getLabel()      { return $this->label;      }
	public function getContent()    { return $this->content;    }
	public function getUpdateDate() { return $this->updatedate; }
	public function getCreateUser() { return $this->createuser; }
	public function getActive()     { return $this->active;     }
	public function getAttributes() { return $this->attributes; }
	public function getRevision()   { return $this->revision;   }

	public function setUpdateUser($updateuser) { $this->updateuser = $updateuser; }
	public function setName($name)             { $this->name       = $name;       }
	public function setCreateDate($createdate) { $this->createdate = $createdate; }
	public function setLabel($label)           { $this->label      = $label;      }
	public function setContent($content)       { $this->content    = $content;    }
	public function setUpdateDate($updatedate) { $this->updatedate = $updatedate; }
	public function setCreateUser($createuser) { $this->createuser = $createuser; }
	public function setActive($active)         { $this->active     = $active;     }
	public function setAttributes($attributes) { $this->attributes = $attributes; }
	public function setRevision($revision)     { $this->revision   = $revision;   }
}
