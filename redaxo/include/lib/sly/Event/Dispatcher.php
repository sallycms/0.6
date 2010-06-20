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

/**
 * Event system
 *
 * @author christoph@webvariants.de
 * @since  0.2
 */
class sly_Event_Dispatcher
{
	private $listeners;
	private static $instance;

	private function __construct() {
		$this->listeners = array();
	}

	public static function getInstance() {
		if (empty(self::$instance)) self::$instance = new self();
		return self::$instance;
	}

	public function register($event, $listener, $params = array()) {
		$params = sly_makeArray($params);
		$this->listeners[$event][] = array('listener' => $listener, 'params' => $params);
	}

	public function clear($event) {
		if (isset($this->listeners[$event])) {
			$this->listeners[$event] = array();
			return true;
		}

		return false;
	}

	public function getEvents() {
		return array_keys($this->listeners);
	}

	public function hasListeners($event) {
		return !empty($this->listeners[$event]);
	}

	public function getListeners($event) {
		return $this->hasListeners($event) ? $this->listeners[$event] : array();
	}

	/**
	 * Notify all listeners
	 *
	 * This method will call all listeners but not evaluate their return values.
	 * It's like "fire and forget" and useful if you're not interested in what
	 * listeners have to say.
	 *
	 * @param  string $event    the event to be triggered
	 * @param  mixed  $subject  an optional value for the listeners to work with
	 * @param  array  $params   additional parameters (if necessary)
	 * @return int              the number of listeners that have been executed
	 */
	public function notify($event, $subject = null, $params = array()) {
		$result = $this->iterate($event, $subject, $params, 'forget');
		return $result['called'];
	}

	/**
	 * Notify all listeners until one stops
	 *
	 * This method will call all listeners and stop when the first one returns
	 * true (or any other value except null, false or ''). A listener therefore
	 * can decide whether further listeners will be called or not.
	 *
	 * Be careful: If a listener returns false/null, you cannot distinguish this
	 * from an error or empty event.
	 *
	 * @param  string $event    the event to be triggered
	 * @param  mixed  $subject  an optional value for the listeners to work with
	 * @param  array  $params   additional parameters (if necessary)
	 * @return mixed            null if no listeners are set, false if no
	 *                          listener stops the evaluation or the return value
	 *                          of the last executed listener (the one that
	 *                          stopped the loop)
	 */
	public function notifyUntil($event, $subject = null, $params = array()) {
		$result = $this->iterate($event, $subject, $params, 'stop');

		switch ($result['state']) {
			case 'empty':   return null;
			case 'stopped': return $result['result'];
			default:        return false;
		}
	}

	/**
	 * Filter a value
	 *
	 * This method will call all listeners and give each one the return value of
	 * it's predecessor. The first listener get's the unaltered $subject. The
	 * result of this method is the return value of the last listener.
	 *
	 * Listeners cannot stop the evaluation (in contrast to notifyUntil()).
	 *
	 * @param  string $event    the event to be triggered
	 * @param  mixed  $subject  an optional value for the listeners to work with
	 * @param  array  $params   additional parameters (if necessary)
	 * @return mixed            the return value of the last listener or the
	 *                          original subject if no listeners have been set
	 */
	public function filter($event, $subject = null, $params = array()) {
		$result = $this->iterate($event, $subject, $params, 'filter');
		return $result['result'];
	}

	protected function iterate($event, $subject, $params, $foldStrategy) {
		if (!$this->hasListeners($event)) return array('state' => 'empty', 'called' => 0, 'result' => $subject);

		$params    = sly_makeArray($params);
		$listeners = $this->getListeners($event);
		$called    = 0;

		$params['event']           = $event;
		$params['subject']         = $subject;
		$params['extension_point'] = $event;    // REDAXO compatibility

		foreach ($listeners as $listener) {
			$callee = $listener['listener'];
			$args   = array_merge($params, $listener['params']);
			$retval = call_user_func($callee, $args);

			++$called;

			switch ($foldStrategy) {
				case 'filter':
					// The return value of this listener shall be the subject of the next one.
					$params['subject'] = $retval;
					break;

				case 'stop':
					// If one listener returns true, break the loop.
					if ($retval) return array('state' => 'stopped', 'called' => $called, 'result' => $retval);
			}
		}

		return array('state' => 'done', 'result' => $params['subject'], 'called' => $called);
	}
}
