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
	private $origWidth;
	private	$origHeight;
	private $width;
	private	$height;
	private $quality;
	private	$thumb_width;
	private	$thumb_height;
	private	$thumb_width_offset;
	private	$thumb_height_offset;
	private	$thumb_quality;
	private $upscalingAllowed = false;

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

		$this->origWidth           = imagesx($this->imgsrc);
		$this->origHeight          = imagesy($this->imgsrc);
		$this->width               = $this->origWidth;
		$this->height              = $this->origHeight;
		$this->quality             = 100;
		$this->width_offset        = 0;
		$this->height_offset       = 0;
		$this->thumb_width_offset  = 0;
		$this->thumb_height_offset = 0;
		$this->thumb_quality       = self::QUALITY;

		if (isset($REX['ADDON']['image_resize']['jpg_quality'])) {
			$this->thumb_quality = (int) $REX['ADDON']['image_resize']['jpg_quality'];
		}
		if (isset($REX['ADDON']['image_resize']['upscaling_allowed'])) {
			$this->upscalingAllowed = (bool) $REX['ADDON']['image_resize']['upscaling_allowed'];
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
		imagecopyresampled(
				$this->imgthumb,
				$this->imgsrc,
				$this->thumb_width_offset,
				$this->thumb_height_offset,
				$this->width_offset,
				$this->height_offset,
				$this->thumb_width,
				$this->thumb_height,
				$this->width,
				$this->height
		);
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
	 * determined whether the image need to be modified or not
	 * @return boolean
	 */
	private function imageGetsModified() {

		// if no filter are applied, size is smaller or equal and quality is lower than desired
		if (empty($this->filters)
			&& $this->thumb_width >= $this->width && $this->thumb_height >= $this->height
			&& $this->thumb_quality >= $this->quality) {

			return false;
		}

		return true;
	}

	/**
	 * Schreibt das Thumbnail an den durch $file definierten Platz
	 *
	 * @param string $file Dateiname des zu generierenden Bildes
	 * @return void
	 */
	public function generateImage($file) {

		if ($this->imageGetsModified()) {

			$this->resampleImage();
			$this->applyFilters();

			$fileext = strtoupper($this->getFileExtension());

			if ($fileext == 'JPG' || $fileext == 'JPEG') {
				imageJPEG($this->imgthumb, $file, $this->thumb_quality);
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
		}
		// just copy the image
		else {
			copy($this->fileName, $file);
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
	private function resizeBoth($width, $height) {
		
		if (!is_array($width) || !isset($width['value'])
			|| !is_array($height) || !isset($height['value'])) {

			return false;
		}

		$imgRatio  = $this->origWidth / $this->origHeight;
		$resizeRatio = $width['value'] / $height['value'];

		// if image ratio is wider than thumb ratio
		if ($imgRatio > $resizeRatio) {
			// if image should be cropped
			if (isset($width['crop']) && $width['crop']) {
				
				// resize height
				$this->resizeHeight($height);
				

				// crop width

				// set new cropped width from original image
		  		$this->width = (int) round($resizeRatio * $this->origHeight);

				// set width to crop width
				$this->thumb_width = (int) $width['value'];

				// right offset
				if (isset($width['offset']['right']) && is_numeric($width['offset']['right'])) {
					$this->width_offset = (int) ($this->origWidth - $this->width - ($this->origHeight / $this->thumb_height * $width['offset']['right']));
				}
				// left offset
				elseif (isset($width['offset']['left']) && is_numeric($width['offset']['left'])) {
					$this->width_offset = (int) $width['offset']['left'];
				}
				// set offset to center image
				else {
					$this->width_offset = (int) (floor($this->origWidth - $this->width) / 2);
				}

			}
			// else resize into bounding box
			else {
				$this->resizeWidth($width);
			}
		}
		// else image ratio is less wide than thumb ratio
		else {
			// if image should be cropped
			if (isset($height['crop']) && $height['crop']) {

				// resize width
				$this->resizeWidth($width);


				// crop height

				// set new cropped width from original image
		  		$this->height = (int) round($this->origWidth / $resizeRatio);

				// set height to crop height
				$this->thumb_height = (int) $height['value'];

				// bottom offset
				if (isset($height['offset']['bottom']) && is_numeric($height['offset']['bottom'])) {
					$this->height_offset = (int) ($this->origHeight - $this->height - ($this->origWidth / $this->thumb_width * $height['offset']['bottom']));
				}
				// top offset
				elseif (isset($height['offset']['top']) && is_numeric($height['offset']['top'])) {
					$this->height_offset = (int) $height['offset']['top'];
				}
				// set offset to center image
				else {
					$this->height_offset = (int) (floor($this->origHeight - $this->height) / 2);
				}


			}
			// else resize into bounding box
			else {
				$this->resizeHeight($height);
			}
		}
	}

	/**
	 * Setzt die Höhe und Breite des Thumbnails
	 *
	 * @param int $size
	 * @return void
	 */
	private function resizeHeight($size) {

		if (!is_array($size) || !isset($size['value'])) {
			return false;
		}
		if ($this->origHeight < $size['value'] && !$this->upscalingAllowed) {
			$size['value'] = $this->origHeight;
		}
		$this->thumb_height = (int) $size['value'];
		$this->thumb_width  = (int) round($this->origWidth / $this->origHeight * $this->thumb_height);
	}

	/**
	 * Setzt die Höhe und Breite des Thumbnails
	 *
	 * @param int $size
	 * @return void
	 */
	private function resizeWidth($size) {

		if (!is_array($size) || !isset($size['value'])) {
			return false;
		}
		if ($this->origWidth < $size['value'] && !$this->upscalingAllowed) {
			$size['value'] = $this->origWidth;
		}
		$this->thumb_width  = (int) $size['value'];
		$this->thumb_height = (int) ($this->origHeight / $this->origWidth * $this->thumb_width);
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
	//public function setNewSize($size, $mode, $height, $mode2, $offset, $offsetType)
	public function setNewSize($params) {

		if (isset($params['auto'])) {
			$this->resizeBoth($params['auto'], $params['auto']);
		}
		elseif (isset($params['width'])) {
			if (isset($params['height'])) {
				$this->resizeBoth($params['width'], $params['height']);
			}
			else {
				$this->resizeWidth($params['width']);
			}
		}
		elseif (isset($params['height'])) {
			$this->resizeHeight($params['height']);
		}
	}

	/**
	 * Wendet filtern auf das thumbnail an
	 *
	 * @return void
	 */
	public function addFilters() {
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

		if (true || !self::USECACHE || !file_exists($cachefile)) {

			// c100w__c200h__20r__20t__filename.jpg
			
			// separate filename and parameters
			preg_match('@((?:c?[0-9]{1,4}[whax]__)*(?:\-?[0-9]{1,4}[orltb]?__)*)(.*)@', $rex_resize, $params);
			if (!isset($params[1]) || !isset($params[2])) return false;

			// get filename
			$imageFile = $params[2];

			// trim _ at the end
			$params = trim($params[1], '_');
			// split parameters
			$params = explode('__', $params);

			// iterate parameters
			$imgParams = array();
			foreach ($params as $param) {
				// check crop option
				$crop = false;
				$prefix = substr($param, 0, 1);
				if ($prefix == 'c') {
					$crop = true;
					$param = substr($param, 1);
				}
				// identify type
				$suffix = substr($param, strlen($param)-1);
				// get value
				$value = substr($param, 0, strlen($param)-1);

				// set parameters for resizing
				if (in_array($suffix, array('w', 'h', 'a', 'x'))) {
					switch ($suffix) {
						case 'w':
							$suffix = 'width';
							break;
						case 'h':
							$suffix = 'height';
							break;
						case 'a':
							$suffix = 'auto';
							break;
						case 'x':
							$suffix = 'width';
							$crop = true;
							break;
					}
					$imgParams[$suffix] = array('value' => $value, 'crop' => ($crop));
				}

				// set parameters for crop offset
				if (in_array($suffix, array('o', 'r', 'l', 't', 'b'))) {
					switch ($suffix) {
						case 'o':
							$imgParams['width']['offset']['left']    = $value;
							$imgParams['height']['offset']['top']    = $value;
							break;
						case 'r':
							$imgParams['width']['offset']['right']   = $value;
							break;
						case 'l':
							$imgParams['width']['offset']['left']    = $value;
							break;
						case 't':
							$imgParams['height']['offset']['top']    = $value;
							break;
						case 'b':
							$imgParams['height']['offset']['bottom'] = $value;
							break;
					}
				}
			}

			if (empty($imageFile)){
				self::sendError();
			}

			$thumb = new self($imageFile);
			$thumb->setNewSize($imgParams);
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
