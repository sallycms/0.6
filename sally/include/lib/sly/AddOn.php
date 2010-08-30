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
 * @ingroup core
 */
class sly_AddOn
{
	protected $name;
	protected $dir;
	protected $config;

	public function __construct($addon)
	{
		$addon = preg_replace('#[^a-z0-9_.,@-]#i', '_', $addon);
		$base  = SLY_INCLUDE_PATH.DIRECTORY_SEPARATOR.'addons';

		if (!is_dir($base.$addon)) {
			throw new Exception('Konnte AddOn '.$addon.' nicht finden.');
		}

		$this->name   = $addon;
		$this->dir    = $base.DIRECTORY_SEPARATOR.$addon;
		$this->config = array();

		$this->loadConfig();
	}

	public function load()
	{
	}

	public function loadConfig()
	{
		$this->config = array();

		if (is_file($this->dir.'/config.ini')) {
			$this->config = parse_ini_file($this->dir.'/config.ini', true);
		}

		if (is_file($this->dir.'/version')) {
			$this->config['addon']['version'] = file_get_contents($this->dir.'/version');
		}
	}

	public function getName()
	{
		return $this->name;
	}

	public function getVersion($default = '')
	{
		return $this->getConfig('addon/version', $default);
	}
}
