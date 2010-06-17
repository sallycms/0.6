<?php
/**
 * Image-Resize Addon
 *
 * @author office[at]vscope[dot]at Wolfgang Hutteger
 * @author <a href="http://www.vscope.at">www.vscope.at</a>
 *
 * @author markus[dot]staab[at]redaxo[dot]de Markus Staab
 * @author zozi@webvariants.de
 *
 *
 * @package redaxo4
 * @version svn:$Id$
 */

class Thumbnail
{
	const ERRORFILE = 'warning.jpg';
	const QUALITY   = 85;
	const USECACHE  = true;

	private $fileName;
	private $isExternal;
	private $imgsrc;
	private $imgthumb;
	private $filters;
	private $width;
	private	$height;
	private	$thumb_width;
	private	$thumb_height;
	private	$thumb_width_offset;
	private	$thumb_height_offset;
	private	$quality;

	public function __construct($imgfile)
	{
		global $REX;

		$this->fileName   = $imgfile;
		$this->isExternal = strpos($imgfile, 'http') === 0;
		$this->filters    = array();

		if (!$this->isExternal) {
			$this->fileName = $REX['MEDIAFOLDER'].'/'.$this->fileName;
		}

		$data = file_get_contents($this->fileName);
		$this->imgsrc = imagecreatefromstring($data);

		if (!$this->imgsrc){
			$this->sendError();
		}

		$this->width               = imagesx($this->imgsrc);
		$this->height              = imagesy($this->imgsrc);
		$this->thumb_width_offset  = 0;
		$this->thumb_height_offset = 0;
		$this->quality             = self::QUALITY;

		if (isset($REX['ADDON']['image_resize']['jpg_quality'])) {
			$this->quality = (int) $REX['ADDON']['image_resize']['jpg_quality'];
		}
	}

	/**
	 * Baut das Thumbnail
	 *
	 * @return void
	 */
	private function resampleImage()
	{
		// Originalbild selbst sehr klein und wuerde via resize vergrößert
		// => Das Originalbild ausliefern

		if ($this->thumb_width > $this->width && $this->thumb_height > $this->height) {
			$this->thumb_width  = $this->width;
			$this->thumb_height = $this->height;
		}

		if (function_exists('imagecreatetruecolor')) {
			$this->imgthumb = @imagecreatetruecolor($this->thumb_width, $this->thumb_height);
		}
		else {
			$this->imgthumb = @imagecreate($this->thumb_width, $this->thumb_height);
		}

		if (!$this->imgthumb) {
			$this->sendError();
			exit();
		}

		// Transparenz erhalten
		$this->keepTransparent();
		imagecopyresampled($this->imgthumb, $this->imgsrc, 0, 0, $this->thumb_width_offset, $this->thumb_height_offset, $this->thumb_width, $this->thumb_height, $this->width, $this->height);
	}

	/**
	 * Sendet die Fehlerdatei
	 *
	 * @return void
	 */
	private function sendError()
	{
		$service = sly_Service_Factory::getService('AddOn');
		$folder  = $service->publicFolder('image_resize');
		self::sendImage($folder.'/'.self::ERRORFILE);
	}

	/**
	 * Sorgt dafür, dass Bilder transparent bleiben
	 *
	 * @return void
	 */
	private function keepTransparent()
	{
		$ext = strtolower($this->getFileExtension());

		if ($ext == 'png' || $ext == 'gif') {
			if ($ext == 'gif') {
				imagepalettecopy($this->imgsrc, $this->imgthumb);
			}

			$colorTransparent = imagecolortransparent($this->imgsrc);

			if ($colorTransparent >= 0) {
				// Get the original image's transparent color's RGB values
				$trnprt_color = imagecolorsforindex($this->imgsrc,  $colorTransparent);

				// Allocate the same color in the new image resource
				$colorTransparent = imagecolorallocate($this->imgthumb, $colorTransparent['red'], $colorTransparent['green'], $colorTransparent['blue']);

				// Completely fill the background of the new image with allocated color.
				imagefill($this->imgthumb, 0, 0, $colorTransparent);

				// Set the background color for new image to transparent
				imagecolortransparent($this->imgthumb, $colorTransparent);
			}
			elseif ($ext == 'png') {
				imagealphablending($this->imgthumb, false);

				// Create a new transparent color for image
				$color = imagecolorallocatealpha($this->imgthumb, 0, 0, 0, 127);

				// Completely fill the background of the new image with allocated color.
				imagefill($this->imgthumb, 0, 0, $color);

				imagesavealpha($this->imgthumb, true);
			}

			if ($ext == 'gif') {
				imagetruecolortopalette($this->imgthumb, true, 256);
			}
		}
	}

	/**
	 * Schreibt das Thumbnail an den durch $file definierten Platz
	 *
	 * @param string $file Dateiname des zu generierenden Bildes
	 * @return void
	 */
	public function generateImage($file)
	{
		$this->resampleImage();
		$this->applyFilters();
		$fileext = strtoupper($this->getFileExtension());

		if ($fileext == 'JPG' || $fileext == 'JPEG') {
			imageJPEG($this->imgthumb, $file, $this->quality);
		}
		elseif ($fileext == 'PNG') {
			imagePNG($this->imgthumb, $file);
		}
		elseif ($fileext == 'GIF') {
			imageGIF($this->imgthumb, $file);
		}
		elseif ($fileext == 'WBMP') {
			imageWBMP($this->imgthumb, $file);
		}

		if ($file) {
			$perm = sly_Core::config()->get('FILEPERM');
			@chmod($file, $perm);
		}
	}

	/**
	 * Wendet alle konfigurierten Filter auf das Thumbnail an
	 *
	 * @return void
	 */
	private function applyFilters()
	{
		$includePath = sly_Core::config()->get('INCLUDE_PATH');

		foreach ($this->filters as $filter) {
			$filter = preg_replace('#[^a-z0-9_]#i', '', $filter);
			$file   = $includePath.'/addons/image_resize/filters/filter.'.$filter.'.inc.php';
			$fname  = 'image_resize_'.$filter;

			if (file_exists($file))      require_once $file;
			if (function_exists($fname)) $fname($this->imgthumb);
		}
	}

	/**
	 * Setzt Höhe und Breite des Thumbnails
	 *
	 * @param int $width Breite des Thumbs
	 * @param int $height Höhe des Thumbs
	 * @return void
	 */
	private function size_both($width, $height)
	{
		if ($this->width < $width && $this->height < $height) {
			$this->thumb_width  =  $this->width;
			$this->thumb_height =  $this->height;
			return;
		}

		$this->thumb_width  = (int) $width;
		$this->thumb_height = (int) $height;

		$width_ratio  = $this->width / $this->thumb_width;
		$height_ratio = $this->height / $this->thumb_height;

		if ($width_ratio > $height_ratio){
			$this->size_width($width);
		}
		else {
			$this->size_height($height);
		}
	}

	/**
	 * Setzt die Höhe und Breite des Thumbnails
	 *
	 * @param int $size
	 * @return void
	 */
	private function size_height($size)
	{
		if ($this->height < $size){
			$size = $this->height;
		}

		// --- height
		$this->thumb_height = (int) $size;

		// siehe http://forum.redaxo.de/ftopic9292.html
		if ($this->thumb_width == 0) {
			$this->thumb_width  = (int) ($this->thumb_height / $this->height * $this->width);
		}
	}

	/**
	 * Setzt die Höhe und Breite des Thumbnails
	 *
	 * @param int $size
	 * @return void
	 */
	private function size_width($size)
	{
		if ($this->width < $size) {
			$size = $this->width;
		}

		// --- width
		$this->thumb_width  = (int) $size;
		$this->thumb_height = (int) ($this->thumb_width / $this->width * $this->height);
	}

	/**
	 * Setzt die Höhe und Breite des Thumbnails
	 *
	 * @param int $size
	 * @return void
	 */
	private function size_auto($size)
	{
		if ($this->width >= $this->height) {
			$this->size_width($size);
		}
		else {
			$this->size_height($size);
		}
	}

	/**
	 * Ausschnitt aus dem Bild auf bestimmte größe zuschneiden
	 *
	 * @param $width int Breite des Ausschnitts
	 * @param $height int Hoehe des Ausschnitts
	 * @param $offset int Verschiebung des Ausschnitts
	 * @param $offsetType
	 */
	private function size_crop($width, $height, $offset, $offsetType)
	{
		$this->thumb_width  = (int) $width;
		$this->thumb_height = (int) $height;

		$width_ratio  = $this->width / $this->thumb_width;
		$height_ratio = $this->height / $this->thumb_height;

		if ($width_ratio >= 1 || $height_ratio >= 1) {
			// Es muss an der Breite beschnitten werden
			if ($width_ratio > $height_ratio) {
				$this->thumb_width_offset = (int) (round(($this->width - $this->thumb_width * $height_ratio) / 2) + $offset);

				if ($offsetType == 'r') $this->thumb_width_offset = (int) $offset;
				if ($offsetType == 'l') $this->thumb_width_offset = (int) $this->width - round($this->thumb_width * $height_ratio) - $offset;

		  		$this->width = (int) round($this->thumb_width * $height_ratio);

			}
			// es muss an der Höhe beschnitten werden
			elseif ($width_ratio < $height_ratio) {
				//$this->img['height_offset_thumb'] = (int) (round(($this->img['height'] - $this->img['height_thumb'] * $width_ratio) / 2) + $offset);
				$this->thumb_height_offset = 0;

				if ($offsetType == 'r') $this->thumb_height_offset = (int) $offset;
				if ($offsetType == 'l') $this->thumb_height_offset = (int) $this->height - round($this->thumb_height * $width_ratio) - $offset;

				$this->height = (int) round($this->thumb_height * $width_ratio);
			}
		}
		else {
			$this->thumb_width  = $this->width;
			$this->thumb_height = $this->height;
		}
	}

	/**
	 * Ausschnitt aus dem Bild auf bestimmte größe zuschneiden
	 *
	 * @param $width int Breite des Ausschnitts
	 * @param $height int Hoehe des Ausschnitts
	 */
	private function size_autocrop($width, $height)
	{
		$this->thumb_width  = (int) $width;
		$this->thumb_height = (int) $height;

		$width_ratio  = $this->width / $this->thumb_width;
		$height_ratio = $this->height / $this->thumb_height;

		if ($width_ratio >= 1 || $height_ratio >= 1) {
			// Es muss an der Breite beschnitten werden
			if ($width_ratio > $height_ratio) {
				$this->thumb_width_offset = (int) round(($this->width - $this->thumb_width * $height_ratio) / 2);
		  		$this->width              = (int) round($this->thumb_width * $height_ratio);
			}
			// es muss an der Höhe beschnitten werden
			elseif ($width_ratio < $height_ratio) {
				$this->thumb_height_offset = (int) round(($this->height - $this->thumb_height * $width_ratio) / 2);
				$this->height              = (int) round($this->thumb_height * $width_ratio);
			}
		}
		else {
			$this->thumb_width  = $this->width;
			$this->thumb_height = $this->height;
		}
	}

	/**
	 * Setzt die Höhe und Breite des Thumbnails
	 *
	 * @param int $size breite/höhe in pixel je nach modus
	 * @param string $mode resize modus
	 * @param int $height höhe in pixel wenn $mode2 gesetzt
	 * @param string $mode2 resize modus2
	 * @param int $offset offset in pixel
	 * @param string $offsetType offset von links, oder rechts, default ist mitte
	 * @return void
	 */
	public function setNewSize($size, $mode, $height, $mode2, $offset, $offsetType)
	{
		if ($mode == 'w') {
			if ($mode2 == 'h') $this->size_both($size, $height);
			else               $this->size_width($size);
		}

		if ($mode == 'h') $this->size_height($size);
		if ($mode == 'c') $this->size_crop($size, $height, $offset, $offsetType);
		if ($mode == 'a') $this->size_auto($size);
		if ($mode == 'x') $this->size_autocrop($size, $height);
	}

	/**
	 * Wendet filtern auf das thumbnail an
	 *
	 * @return void
	 */
	public function addFilters()
	{
		$this->filters = array_unique(array_filter(rex_get('rex_filter', 'array', array())));
	}

	/**
	 * sendet ein bearbeitetes bild an den browser
	 *
	 * @param string $rex_resize
	 * @return void
	 */
	public static function getResizedImage($rex_resize)
	{
		$cachefile = self::getCacheFileName($rex_resize);

		if (!self::USECACHE || !file_exists($cachefile)) {
			preg_match('@([0-9]*)([awhcx])__(([0-9]*)([h])__)?((\-?[0-9]*)([rlo])__)?(.*)@', $rex_resize, $resize);
			$size       = $resize[1];
			$mode       = $resize[2];
			$height     = $resize[4];
			$mode2      = $resize[5];
			$offset     = $resize[7];
			$offsetType = $resize[8];
			$imagefile  = $resize[9];

			if (empty($imagefile)){
				self::sendError();
			}

			$thumb = new self($imagefile);
			$thumb->setNewSize($size, $mode, $height, $mode2, $offset, $offsetType);
			$thumb->addFilters();
			$thumb->generateImage($cachefile);
		}

		$cachetime = filectime($cachefile);
		self::sendImage($cachefile, $cachetime);
	}

	/**
	 * Löscht den Cache
	 *
	 * @param $filename um ein bestimmtes bild zu löschen
	 * @return int Anzahl der gelöschten Bilder
	 */
	public static function deleteCache($filename = '')
	{
		$service = sly_Service_Factory::getService('AddOn');
		$folder  = $service->publicFolder('image_resize');
		$c       = 0;
		$files   = glob($folder.'/cache__*');

		if ($files) {
			if (empty($filename)) {
				array_map('unlink', $files);
				return count($files);
			}

			foreach ($files as $file) {
				if ($filename == substr($file, -strlen($filename))) {
					unlink($file);
					++$c;
				}
			}
		}

		return $c;
	}
	
	/**
	 * @return int
	 */
	public static function mediaUpdated($params)
	{
		return self::deleteCache($params['filename']);
	}

	/**
	 * @return string Die File Extension des Bildes
	 */
	private function getFileExtension()
	{
		return self::getFileExtensionStatic($this->fileName);
	}

	/**
	 * @param string $fileName
	 * @return string Die File Extension einer Datei
	 */
	private static function getFileExtensionStatic($fileName)
	{
		return strtoupper(substr(strrchr($fileName, '.'), 1));
	}

	/**
	 * Gibt den Dateinamen eines Bildes anhand des rex_resize Parameters zurück
	 *
	 * @param string $rex_resize
	 * @return string Dateiname des Bildes im Cache
	 */
	private static function getCacheFileName($rex_resize)
	{
		$service = sly_Service_Factory::getService('AddOn');
		$folder  = $service->publicFolder('image_resize');
		return $folder.'/cache__'.str_replace(array('http://', 'https://', '/'), array('', '', '_'), $rex_resize);
	}

	/**
	 * Sendet ein Bild an den Browser und beendet das Script
	 *
	 * @param string $fileName Dateiname des Bildes
	 * @param timestamp $lastModified wann wurde das Bild zuletzt modifiziert?
	 * @return void
	 */
	private static function sendImage($fileName)
	{
		while (ob_get_level()) ob_end_clean();

		$etag = md5($fileName.filectime($fileName));

		if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
			if ($_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
				header('HTTP/1.0 304 Not Modified');
				exit();
			}
		}

		header('Content-Type: image/'.self::getFileExtensionStatic($fileName));
		header('ETag: '.$etag);
		header('Cache-Control: ');
		header('Pragma: ');

		readfile($fileName);
		exit();
	}
}
