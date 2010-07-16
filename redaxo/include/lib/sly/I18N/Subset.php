<?php

class sly_I18N_Subset implements sly_I18N_Base {
	public function __construct($i18nContainer, $prefix) {
		$this->container = $i18nContainer;
		$this->prefix    = $prefix;
	}

	public static function create($prefix) {
		global $I18N;
		return new self($I18N, $prefix);
	}

	public function msg($key)          { return $this->container->msg($this->prefix.$key);          }
	public function addMsg($key, $msg) { return $this->container->addMsg($this->prefix.$key, $msg); }
	public function hasMsg($key)       { return $this->container->hasMsg($this->prefix.$key);       }
}
