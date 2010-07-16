<?php

interface sly_I18N_Base {
	public function msg($key);
	public function addMsg($key, $msg);
	public function hasMsg($key);
}
