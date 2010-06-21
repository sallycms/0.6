<?php

/**
 * Sprachobjekt zur Internationalisierung (I18N)
 * 
 * @package redaxo4
 * @version svn:$Id$
 */

class i18n
{
	protected $locales;
	protected $searchpath;
	protected $locale;
	protected $text;
	protected $text_loaded;

	/*
	 * Constructor
	 * the locale must of the common form, eg. de_de, en_us or just plain en, de.
	 * the searchpath is where the language files are located
	 */
	public function __construct($locale = 'de_de', $searchpath)
	{
		$this->searchpath  = $searchpath;
		$this->text        = array();
		$this->locale      = $locale;
		$this->locales     = array();
		$this->text_loaded = false;
	}

	/*
	 * Lädt alle Übersetzungen der aktuellen Sprache aus dem Sprachpfad und fügt diese dem Katalog hinzu.
	 */
	public function loadTexts()
	{
		if ($this->appendFile($this->searchpath)) {
			$this->text_loaded = TRUE;
		}
	}

	/**
	 * Sucht im angegebenden Ordner nach eine Sprachdatei der aktuellen Sprache und f�gt diese dem Sprachkatalog an
	 *  
	 * @param string $searchPath  Pfad in dem die Sprachdatei gesucht werden soll
	 */
	public function appendFile($searchPath)
	{
		$filename = $searchPath.'/'.$this->locale.'.lang';
		
		if (is_readable($filename)) {
			$lines = array_map('trim', file($filename));
			
			foreach ($lines as $line) {
				if (preg_match('#^(\w*)\s*=\s*(.*)$#', $line, $matches)) {
					$this->addMsg($matches[1], trim($matches[2]));
				}
			}
			
			return true;
		}

		return false;
	}

	/**
	 * Durchsucht den Sprachkatalog nach einem Schlüssel und gibt die dazugehörige Übersetzung zurück
	 * 
	 * @param string $key  zu suchender Schlüssel
	 */
	public function msg($key)
	{
		global $REX;

		/*
		// Warum hier umschalten der Sprache!?
		if (isset($REX['LOGIN']) && is_object($REX['LOGIN']) && $REX['LOGIN']->getLanguage() != $this->locale) {
			$this->locale = $REX['LOGIN']->getLanguage();
			$this->text_loaded = FALSE;
		}
		*/

		if (!$this->text_loaded) {
			$this->loadTexts();
		}

		if ($this->hasMsg($key)) {
			$msg = $this->text[$key];
		}
		else {
			$msg = '[translate:'.$key.']';
		}

		$patterns     = array();
		$replacements = array();

		$argv = func_get_args();
		$argc = count($argv);
		
		// Wir überspringen $key -> $i = 1
		for ($i = 1; $i < $argc; ++$i) {
			$patterns[]     = '/\{'.($i-1).'\}/';
			$replacements[] = $argv[$i];
		}

		return preg_replace($patterns, $replacements, $msg);
	}

	/**
	 * Fügt dem Sprachkatalog unter dem gegebenen Schlüssel eine neue Übersetzung hinzu 
	 *  
	 * @param string $key  Schlüssel unter dem die Übersetzung abgelegt wird
	 * @param string $msg  übersetzter Text
	 */
	public function addMsg($key, $msg) {
		$this->text[$key] = $msg;
	}

	/**
	 * Prüft ob der Sprachkatalog zu dem gegebenen Schlüssel eine Übersetzung beinhaltet
	 * 
	 * @param  string $key zu suchender Schlüssel
	 * @return boolean     true wenn der Schlüssel gefunden wurde, sonst false
	 */
	public function hasMsg($key)
	{
		return isset($this->text[$key]);
	}

	/**
	 * Durchsucht den Searchpath nach allen verfügbaren Sprachdateien und gibt diese zurück
	 * 
	 * @param  string $searchpath  zu duruchsuchender Ordner
	 * @return array               Array von gefundenen Sprachen (locales)
	 */
	public function getLocales($searchpath)
	{
		if (empty($this->locales) && is_readable($searchpath)) {
			$this->locales = array();

			$files = glob($searchpath.'/*.lang');
			
			foreach ($files as $filename) {
				// von 'C:\foo\bar.lang' nur 'bar' speichern
				$this->locales[] = substr(basename($filename), 0, -5);
			}
		}

		return $this->locales;
	}
}
