<?php 
/*
 * Copyright (c) 20010, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
*/
abstract class sly_Controller_Ajax extends sly_Controller_Base{
	
    public function dispatch() {
        if (!method_exists($this, $this->action)) {
            throw new ControllerException('HTTP 404: Methode '. $this->action .' in '. get_class($this) .' nicht gefunden!');
        }

        if($this->checkPermission() !== true){
            throw new PermissionException('HTTP 403: Zugriff auf '. $this->action .' in '. get_class($this) .' nicht gestattet!');
        }
		while(ob_get_level()) ob_end_clean();
        $method = $this->action;
        $this->$method();
		exit;
    }
}