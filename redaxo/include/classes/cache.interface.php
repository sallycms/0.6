<?php
interface ICache {

	public function set($key, $value);
	
	public function get($key);
	
	public function flush();
	
	public function delete($key);

} 