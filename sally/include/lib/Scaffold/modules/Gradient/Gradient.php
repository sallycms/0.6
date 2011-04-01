<?php

/**
 * Gradient class
 *
 * @author Paul Clark
 * @version 1.0
 * @dependencies gradientgd.class.php
 */
class Gradient
{
	/**
	 * List of created gradients and their locations
	 *
	 * @var array
	 */
	public static $gradients = array();

	public static function create_gradient($direction, $size, $from, $to, $stops = false )
	{
		if (!class_exists('GradientGD'))
			include(dirname(__FILE__).'/libraries/gradientgd.php');
		
		$file = "{$direction}_{$size}_".str_replace('#','',$from)."_".str_replace('#','',$to).".png";

		if($direction == 'horizontal')
		{
			$height = 50;
			$width = $size;
			$repeat = 'y';
		}
		else
		{
			$height = $size;
			$width = 50;
			$repeat = 'x';
		}

		if(!Scaffold_Cache::exists('gradients/'.$file)) 
		{
			Scaffold_Cache::create('gradients');
			$file = Scaffold_Cache::find('gradients') . '/' . $file;
			$gradient = new GradientGD($width,$height,$direction,$from,$to,$stops);
			$gradient->save($file);
		}
		
		$file = Scaffold_Cache::find('gradients') . '/' . $file;

		
		self::$gradients[] = array
		(
			$direction,
			$size,
			$from,
			$to,
			$file
		);

		$properties = "
			background-position: top left;
		    background-repeat: repeat-$repeat;
		    background-image: url(".Scaffold::url_path($file).");
		";
		
		return $properties;

	}
	
	private static function getWebkitLinear($startPos, $endPos, $colors) {
		
		$css = null;

		/* webkit */
		$wkColors = array();
		foreach ($colors as $idx => $color) {
			if (isset($color['color'])) {
				if ($idx === 0) {
					$wkColors[] = 'from('.$color['color'].')';
				}
				elseif ($idx === count($colors)-1) {
					$wkColors[] = 'to('.$color['color'].')';
				}
				else {
					$offset = null;
					if (isset($color['offset'])) {
						$offset = $color['offset'];
					}
					else {
						$offset = round($idx/(count($colors)-1), 1);
						$offset = number_format($offset, 1, '.', '');
					}
					$wkColors[] = 'color-stop('.$offset.','.$color['color'].')';
				}
			}
		}
		$wkColors = implode(', ', $wkColors);

		$css .= 'background-image: -webkit-gradient(linear, '.$startPos.', '.$endPos.', '.$wkColors.');';
		
		return $css;
	}
	
	private static function getWkMozLinear($startPos, $endPos, $colors) {
		
		$css = null;

		/* firefox */
		$ffAngle = null;
		/*
		 * TODO: calculate angle for firefox
		 */
		$ffColors = array();
		foreach ($colors as $color) {
			if (isset($color['color']) && isset($color['offset'])) {
				$ffColors[] = $color['color'].' '.round($color['offset']*100).'%';
			}
			elseif (isset($color['color'])) {
				$ffColors[] = $color['color'];
			}
		}
		$ffColors = implode(', ', $ffColors);

		$css .= 'background-image: -webkit-linear-gradient('.$startPos.$ffAngle.', '.$ffColors.');';
		$css .= 'background-image: -moz-linear-gradient('.$startPos.$ffAngle.', '.$ffColors.');';
		
		return $css;
	}

	/* For Internet Explorer > 8 */
	private static function getIELinear($colors) {

		if (!is_array($colors) || empty($colors)) return false;

		$css = null;

		$startColor = null;
		$sC = array_shift($colors);
		if (isset($sC['color'])) $startColor = $sC['color'];

		$endColor = null;
		$eC = array_shift($colors);
		if (isset($eC['color'])) $endColor = $eC['color'];

		$css .= '-ms-filter: "progid:DXImageTransform.Microsoft.gradient(startColorstr='.$startColor.', endColorstr='.$endColor.')";';

		return $css;
	}

	public static function getLinear($startPos, $endPos, $colors) {

		$css = null;
		
		$css .= self::getIELinear($colors);
		$css .= self::getWebkitLinear($startPos, $endPos, $colors);
		$css .= self::getWkMozLinear($startPos, $endPos, $colors);

		return $css;
	}
}