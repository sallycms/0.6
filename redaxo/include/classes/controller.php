<?php
/*
 * Copyright (c) 2009, webvariants GbR, http://www.webvariants.de
 *
 * Diese Datei steht unter der MIT-Lizenz. Der Lizenztext befindet sich in der
 * beiliegenden LICENSE Datei und unter:
 *
 * http://www.opensource.org/licenses/mit-license.php
 * http://de.wikipedia.org/wiki/MIT-Lizenz
*/

abstract class Controller {

    const PAGEPARAM = 'page';
    const SUBPAGEPARAM = 'subpage';
    const ACTIONPARAM = 'func';
    const DEFAULTPAGE = 'structure';

    protected $action;
   
    protected function __construct() {
        $this->action = rex_request(self::ACTIONPARAM, 'string', 'index');
    }

    public static function factory() {
        $name = rex_request(self::SUBPAGEPARAM, 'string', '');
        if(empty($name)){
            $name = rex_request(self::PAGEPARAM, 'string', self::DEFAULTPAGE);
        }
        if(!empty($name)){
            $name = strtoupper(substr($name, 0, 1)).substr($name, 1).'Controller';
            if(class_exists($name)) {
                return new $name($name);
            }
        }
        return null;
    }

    public function dispatch() {

        if (!method_exists($this, $this->action)) {
            throw new ControllerException('HTTP 404: Methode '. $this->action .' in '. get_class($this) .' nicht gefunden!');
        }

        if($this->checkPermission() !== true){
            throw new PermissionException('HTTP 403: Zugriff auf '. $this->action .' in '. get_class($this) .' nicht gestattet!');
        }

        $method = $this->action;
        return $this->$method();
    }

    protected abstract function index();

    protected function render($filename, $params) {
        global $REX, $I18N;

        // Die Parameternamen $params und $filename sind zu kurz, als dass
        // man sie zuverlässig nutzen könnte. Wenn $params durch extract()
        // während der Ausführung überschrieben wird kann das unvorhersehbare
        // Folgen haben. Darum wird $filename und $params in kryptische
        // Variablen verpackt und aus dem Kontext entfernt.
        $filenameHtuG50hNCdikAvf7CZ1F = $filename;
        $paramsHtuG50hNCdikAvf7CZ1F = $params;
        unset($filename);
        unset($params);
        extract($paramsHtuG50hNCdikAvf7CZ1F);

        ob_start();
        require_once $REX['INCLUDE_PATH'].DIRECTORY_SEPARATOR.$filenameHtuG50hNCdikAvf7CZ1F;

        print ob_get_clean();
    }

    protected abstract function checkPermission();

}
