<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
 */

class sly_Util_Array extends ArrayObject {
	
	public function set($key, $value) {
		if (is_null($key) || strlen($key) === 0) trigger_error('Key must not be empty!', E_USER_ERROR);
		
		if (strpos($key, '/') === false) {
			$this[$key] = $value;
			return $value;
		}
		
		// Da wir Schreibvorgänge anstoßen werden, arbeiten wir hier explizit
		// mit Referenzen. Ja, Referenzen sind i.d.R. böse, deshalb werden sie auch
		// in get() und has() nicht benutzt. Copy-on-Write und so.
		
		$path = explode('/', $key);
		
		$res  = $this;
		
		foreach ($path as $step) {
			
			if (!array_key_exists($step, $res)) $res[$step] = array();
			$res = &$res[$step];
		}
		$res = $value;
		return $value;
	}
	
	public function get($key) {
		if (empty($key)) return $this->getArrayCopy();
		if (strpos($key, '/') === false) return $this[$key];
		
		$path = array_filter(explode('/', $key));
		$res  = $this;
		
		foreach ($path as $step) {
			if (!array_key_exists($step, $res)) {
				trigger_error('Element '.$key.' not found!', E_USER_NOTICE);
				return null;
			}
			$res = $res[$step];
		}
		
		return $res;
	}
	
	public function has($key) {
		if (empty($key)) return true;
		if (strpos($key, '/') === false) return array_key_exists($key, $this);
		
		$path = array_filter(explode('/', $key));
		$curr = $this;
		$res = true;
		
		foreach ($path as $step) {
			if (!array_key_exists($step, $curr)) {
				$res = false;
				break;	
			}
			$curr = $curr[$step];
		}
		
		return $res;
	}
	
	public function remove($key) {
		if (empty($key)) {
			$this->exchangeArray(array());
			return true;
		}
		if (strpos($key, '/') === false) {
			unset($this[$key]);
			return true;
		}
		
		$path = array_filter(explode('/', $key));
		$curr = $this;
		
		foreach ($path as $step) {
			if (!array_key_exists($step, $curr)) return false;
			$curr = &$curr[$step];
		}
		
		unset($curr);
		return true;
	}
	
	public function hasMergeCollision($array) {
		return $this->hasMergeCollisionRecursive($this->getArrayCopy(), $array);
	}
	
	private function hasMergeCollisionRecursive($array, $array1) {
		foreach ($array1 as $key => $value) {
			if (is_array($value) && isset($array[$key]) && is_array($array[$key])) {
				if (!$this->hasMergeCollisionRecursive($array[$key], $value)) return false;
			}
			else {
				if ($array[$key] != $value) return false;
			}
		}
		return true;
	}
	
	public function merge($array) {
		if (!is_array($array)) return false;
		$this->exchangeArray(array_replace_recursive($this->getArrayCopy(), $array));
	}
}