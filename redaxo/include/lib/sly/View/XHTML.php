<?php

class sly_View_XHTML extends sly_View_Base
{
	protected $title = '';
	protected $subtitle = '';
	protected $charset = '';
	
	protected $params          = array();
	protected $header          = '';
	protected $isPluggedIn     = false;

	
	public function __construct()
	{
		global $I18N;
		
		$this->addCSSFile('media/css_import.css');
		$this->addJavaScriptFile('media/jquery.min.js');
		$this->addJavaScriptFile('media/standard.min.js');
		
		$this->charset = $I18N->msg('htmlcharset');
	}
	
	public function render()
	{
		ob_start();
		$this->renderView('views/_view/xhtml.phtml');
		return ob_get_clean();
	}
	
	/**
	 * prints a valid xhtml header
	 * 
	 * @params	Array	array of parameters
	 * 
	 * The array may have various parameters:
	 * $params['title'] (Mandatory)
	 * 		The title of the current html-page.
	 * 
	 * $params['links']['MoreCSS'] (Optional)
	 * 		Array of MoreCSS-files if MoreCSS is used in this project. The MoreCSS-files are no normal CSS files
	 * 
	 * $params['links']['prefetch'] (Optional)
	 * 		Array of Files or Sites that should be prefetched. (complete site-relative path required)
	 * 
	 * $params['body'] (Optional)
	 * 		Array of body parameters. Available parameters are:
	 * 		$params['body']['onload'] - JavaScript code that is executed on site load.
	 * 		$params['body']['onresize'] - JavaScript code that is executed on sites resize.
	 * 		$params['body']['class'] - Body's class attribute
	 * 		$params['body']['id'] - Body's id attribute
	 * 		$params['body']['style'] - Body's style attribute
	 * 
	 * $params['metas'] (Optional)
	 * 		Associative array of meta-infos. Usage example:
	 * 		$params['metas']['language'] = 'German, de, deutsch, at, ch';
	 * 
	 * $params['iewarning'] (Optional)
	 * 		Determines whether the page a warning text will be shown, when a
	 * 		user with some crappy browser comes by. Set this to true to
	 * 		get the default warning text. Set this to a string to override
	 * 		this default text. Don't set it (or set it false) to disable the
	 * 		warning. The warning may contain HTML as is printed unfiltered.
	 * 
	 * $params['httpmetas'] (Optional)
	 * 		Associative array of meta-infos. Usage example:
	 * 		$params['httpmetas']['content-type'] = 'text/html; charset=UTF-8';
	 * 
	 * $params['favIcon'] (Optional)
	 * 		The shortcut icon for bookmarking or tab-identification. Usually this 
	 * 		is a .ico file in the websites root directory, called favico.ico
	 */
	public static function printHeader($params)
	{
		extract($params);
		header('Content-Type: '.$httpmetas['content-type']);
		/* print '<?xml version="1.0" encoding="UTF-8" ?>'."\n"; */
		/* print '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'; */
		print '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
		print "\n";

		if (empty($params)) die('XHTML Header has no Parameters');
		if (empty($title)) die('XHTML Header has no title set in $params[\'title\']');
		
		ob_start();
		self::$params = $params;
	}
	
	private static function printRealHeader($params)
	{
		extract($params);
		// xmlns wegen dem eclipse-validator per php eingebunden... *g*
		?>
<html <?= 'xmlns="http://www.w3.org/1999/xhtml"' ?>>
<head>
	<title><?= sly_html($title) ?></title>
	<?php
		$lines = array();
		
		if (!empty($favIcon)) {
			$lines[] = '<link href="'.$favIcon.'" rel="SHORTCUT ICON" type="image/x-icon" />';
		}
		
		$additionalMerged = false;
		if (!empty($feedFiles)) {
			foreach ($feedFiles as $media => $files) {
				if ($media == 'all' && !empty(self::$additionalFeedFiles)) {
					$files = array_merge($files, self::$additionalFeedFiles);
					$additionalMerged = true;
				}
				
				$lines = array_merge($lines, self::printFiles('feed', $media, $files));
			}
		}
		
		if (!$additionalMerged && !empty(self::$additionalFeedFiles)) {
			$lines = array_merge($lines, self::printFiles('feed', 'all', self::$additionalFeedFiles));
		}
		
		$additionalMerged = false;
		if (!empty($cssFiles)) {
			foreach ($cssFiles as $media => $files) {
				$useDeployer   = $deploy['activate'] && in_array($media, $deploy['css_indices']);
				$withTimestamp = $useDeployer && $deploy['with_timestamp'];
				
				if ($media == 'all' && !empty(self::$additionalCSSFiles)) {
					$files = array_merge($files, self::$additionalCSSFiles);
					$additionalMerged = true;
				}
				
				$lines = array_merge($lines, self::printFiles('css', $media, $files, $useDeployer, $withTimestamp));
			}
		}
		
		if (!$additionalMerged && !empty(self::$additionalCSSFiles)) {
			$useDeployer   = $deploy['activate'] && in_array($media, $deploy['css_indices']);
			$withTimestamp = $useDeployer && $deploy['with_timestamp'];
			$lines = array_merge($lines, self::printFiles('css', 'all', self::$additionalCSSFiles, $useDeployer, $withTimestamp));
		}
		
		if (!empty($links)) {
			foreach ($links as $rel => $file) $lines[]= '<link rel="'.$rel.'" href="'.$file.'" />';
		}
		
		if (!empty($inlineStyles) || !empty(self::$additionalCSSCode)) {
			$code  = empty($inlineStyles) ? '' : $inlineStyles;
			$code .= empty(self::$additionalCSSCode) ? '' : ' '.self::$additionalCSSCode;
			$lines[]= '<style type="text/css">'.($deploy['activate'] ? WV5_Deployer::minifyCSS($code) : $code).'</style>';
		}
		
		$additionalMerged = false;
		if (!empty($jsFiles)) {
			foreach ($jsFiles as $media => $files) {
				$useDeployer   = $deploy['activate'] && in_array($media, $deploy['js_indices']);
				$withTimestamp = $useDeployer && $deploy['with_timestamp'];
				
				if ($media == 'deploy' && !empty(self::$additionalJavaScriptFiles)) {
					$files = array_merge($files, self::$additionalJavaScriptFiles);
					$additionalMerged = true;
				}
				
				$lines = array_merge($lines, self::printFiles('js', $media, $files, $useDeployer, $withTimestamp));
			}
		}
		
		if (!$additionalMerged && !empty(self::$additionalJavaScriptFiles)) {
			$useDeployer   = $deploy['activate'] && in_array($media, $deploy['js_indices']);
			$withTimestamp = $useDeployer && $deploy['with_timestamp'];
			$lines = array_merge($lines, self::printFiles('js', 'deploy', self::$additionalJavaScriptFiles, $useDeployer, $withTimestamp));
		}
		
		if (!empty($js) || !empty($body['onload']) || !empty($body['onresize']) || !empty(self::$additionalJavaScriptCode)) {
			$lines[] = '<script type="text/javascript">';
			$lines[] = '/* <![CDATA[ */';
		}
		
		if (!empty($js)) $lines[] = $deploy['activate'] ? trim(WV5_Deployer::minifyJS($js)) : $js;
		if (!empty($body['onload'])) $lines[] = 'window.onload = function() { '.$body['onload'].' }';
		if (!empty($body['onresize'])) $lines[] = 'window.onresize = function() { '.$body['onresize'].' }';
		if (!empty(self::$additionalJavaScriptCode)) $lines[] = $deploy['activate'] ? trim(WV5_Deployer::minifyJS(self::$additionalJavaScriptCode)) : self::$additionalJavaScriptCode;
		
		if (!empty($js) || !empty($body['onload']) || !empty($body['onresize']) || !empty(self::$additionalJavaScriptCode)) {
			$lines[] = '/* ]]> */';
			$lines[] = '</script>';
		}
		
		if (!empty($metas)) {
			foreach ($metas as $name => $content) {
				$lines[] = '<meta name="'.$name.'" content="'.sly_html($content).'" />';
			}
		}
		
		if (!empty($httpmetas)) {
			foreach ($httpmetas as $name => $content) {
				$lines[] = '<meta http-equiv="'.$name.'" content="'.sly_html($content).'" />';
			}
		}
		
		$class = !empty($body['class']) ? ' class="'.$body['class'].'" ':'';
		$id    = !empty($body['id']) ? ' id="'.$body['id'].'" ':'';
		$style = !empty($body['style']) ? ' style="'.$body['style'].'" ':'';
		$body  = trim(implode(' ', array($class, $id, $style)));
		$body  = $body ? ' '.$body : '';
		
		print implode("\n\t", $lines);
		print "\n</head>\n<body$body>\n";
		
		if (isset($iewarning)) {
			if (is_string($iewarning) && !empty($iewarning)) {
				print '<!--[IF lt IE 7]><div id="ie_warning">'.$iewarning.'</div><![endif]-->';
			}
			elseif ($iewarning === true) {
				print '<!--[IF lt IE 7]><div id="ie_warning">Sie benutzen einen veralteten '.
				'Webbrowser, bitte machen Sie ein '.
				'<a href="http://www.microsoft.com/germany/windows/internet-explorer/download-ie.aspx">Update</a> '.
				'oder wechseln Sie zu einem '.
				'<a href="http://www.mozilla-europe.org/de/firefox/">alternativen Browser</a>.'.
				'</div><![endif]-->';
			}
		}
	}
	
	public static function printFooter()
	{
		$content = ob_get_clean();
		
		self::printRealHeader(self::$params);
		print $content;
		print "\n</body>\n</html>";
	}
	
	public static function handleOutputFilter($params)
	{
		$lines = array();
		
		if (!empty(self::$additionalCSSCode)) {
			$lines[] = '<style type="text/css">'.trim(self::$additionalCSSCode).'</style>';
		}
		
		if (!empty(self::$additionalJavaScriptCode)) {
			$lines[] = '<script type="text/javascript">';
			$lines[] = '/* <![CDATA[ */';
			$lines[] = trim(self::$additionalJavaScriptCode);
			$lines[] = '/* ]]> */';
			$lines[] = '</script>';
		}
		
		foreach (self::$additionalCSSFiles as $file) {
			$lines[] = '<link rel="stylesheet" type="text/css" href="../'.$file.'" />';
		}
		
		foreach (self::$additionalJavaScriptFiles as $file) {
			$lines[] = '<script type="text/javascript" src="../'.$file.'"></script>';
		}
		
		$lines = implode("\n  ", $lines);
		return str_replace('</head>', "  $lines\n</head>", $params['subject']);
	}
	
	private static function printFiles($type, $media, $files, $deployer = false, $timestamp = false)
	{
		$lines = array();
		$isConditional = false;
		
		if (substr($media, 0, 3) == 'IF ') {
			$lines[] = '<!--[if '.strtoupper(substr($media, 3)).']>';
			$media   = 'screen';
			$isConditional = true;
		}
		
		if ($type == 'css') $lines = array_merge($lines, self::printCSSFile($files, $media, $deployer, $timestamp));
		if ($type == 'js')  $lines = array_merge($lines, self::printJSFile($files, $media, $deployer, $timestamp));
		if ($type == 'feed') $lines = array_merge($lines, self::printFeedFile($files));
		
		if ($isConditional) $lines[] = '<![endif]-->';
		
		return $lines;
	}
	
	private static function printFeedFile($files) {
		
		$lines = array();
		
		foreach ($files as $type => $file) {
			
			$link = '<link rel="alternate" type="application/';
			if ($type != 'atom') $link .= 'rss';
			else $link .= $type;
			$link .= '+xml" title="';
			switch ($type) {
				case 'rss1':
					$link .= 'RSS-Feed 1.0';
					break;
				case 'rss2':
					$link .= 'RSS-Feed 2.0';
					break;
				case 'atom':
					$link .= 'Atom-Feed';
					break;
					
				default:
					$link .= 'RSS-Feed';
					break;
			}
			$link .= '" href="'.$file.'" />';
			
			$lines[] = $link;
		}
		
		return $lines;
	}
	
	private static function printCSSFile($files, $media, $useDeployer, $timestamp)
	{
		$lines = array();
		
		if ($useDeployer) {
			$lines[] = '<link rel="stylesheet" type="text/css" href="'.WV5_Deployer::mergeCSSFiles($files, true, $media, $timestamp).'" media="'.$media.'" />';
		}
		else {
			foreach ($files as $file) {
				$lines[] = '<link rel="stylesheet" type="text/css" href="'.$file.'" media="'.$media.'" />';
			}
		}
		
		return $lines;
	}
	
	private static function printJSFile($files, $media, $useDeployer, $timestamp)
	{
		$lines = array();
		
		if ($useDeployer) {
			$lines[] = '<script type="text/javascript" src="'.WV5_Deployer::mergeJSFiles($files, $media, $timestamp).'"></script>';
		}
		else {
			foreach ($files as $file) {
				$lines[] = '<script type="text/javascript" src="'.$file.'"></script>';
			}
		}
		
		return $lines;
	}
}
