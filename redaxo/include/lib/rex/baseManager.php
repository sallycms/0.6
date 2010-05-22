<?php

/**
 * Managerklasse zum handeln von rexAddons
 *
 * @deprecated
 */
abstract class rex_baseManager
{
	protected $i18nPrefix;
	protected $service;

	/**
	 * Konstruktor
	 *
	 * @param $i18nPrefix Sprachprefix aller I18N Sprachschlüssel
	 */
	public function __construct($i18nPrefix)
	{
		$this->i18nPrefix = $i18nPrefix;
	}

	public function install($component, $installDump = true)
	{
		return $this->service->install($this->makeComponent($component), $installDump);
	}
	
	public function uninstall($component)
	{
		return $this->service->uninstall($this->makeComponent($component));
	}
	
	public function activate($component)
	{
		return $this->service->activate($this->makeComponent($component));
	}
	
	public function deactivate($component)
	{
		return $this->service->deactivate($this->makeComponent($component));
	}
	
	public function delete($component)
	{
		return $this->service->delete($this->makeComponent($component));
	}
	
	abstract protected function makeComponent($component);
}
