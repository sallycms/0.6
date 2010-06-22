<?php

class sly_Layout_Sally extends sly_Layout_XHTML
{
	public function __construct()
	{
		global $REX;
		
		$config = sly_Core::config();
		
		$this->addCSSFile('media/css_import.css');
		$this->addCSSFile('scaffold/import_export/backend.css');
		$this->addCSSFile('media/css_ie_lte_7.css', 'all', 'if lte IE 7');
		$this->addCSSFile('media/css_ie_7.css', 'all', 'if IE 7');
		$this->addCSSFile('media/css_ie_lte_6.css', 'all', 'if lte IE 6');

		$this->addJavaScriptFile('media/jquery.min.js');
		$this->addJavaScriptFile('media/standard.min.js');

		$this->setTitle($config->get('SERVERNAME').' - ');

		$popups_arr = array('linkmap', 'mediapool');
		$config     = sly_Core::config();
		
		$body_id = str_replace('_', '-', $REX['PAGE']);
		$this->setBodyAttr('id', 'rex-page-'.$body_id);
		
		// Falls ein AddOn bereits in seiner config.inc.php auf das Layout
		// zugegriffen hat, ist $REX['PAGE'] noch nicht bekannt. Wir hängen uns
		// daher in PAGE_CHECKED, um den Wert später noch einmal zu validieren.
		
		rex_register_extension('PAGE_CHECKED', array($this, 'pageChecked'));

		if (in_array($body_id, $popups_arr)) {
			$this->setBodyAttr('class', 'rex-popup');
		}

		if ($config->get('PAGE_NO_NAVI')) {
			$this->setBodyAttr('onunload', 'closeAll()');
		}

		$this->addHttpMeta('Content-Type', 'text/html charset='.t('htmlcharset'));
	}
	
	public function pageChecked($params) {
		$body_id = str_replace('_', '-', $params['subject']);
		$this->setBodyAttr('id', 'rex-page-'.$body_id);
	}

	public function printHeader() {
		parent::printHeader();
		$this->renderView('views/layout/sally_top.phtml');
	}

	public function printFooter() {
		$this->renderView('views/layout/sally_bottom.phtml');
		parent::printFooter();
	}
}
