<?php

$lime->comment('Testing sly_Event_Dispatcher...');

$___STATE = array();

function listenerA($params) {
	global $___STATE;
	$___STATE[] = 'a';
	return null;
}

function listenerB($params) {
	global $___STATE;
	$___STATE[] = 'b';
	return false;
}

function listenerC($params) {
	global $___STATE;
	$___STATE[] = 'c';
	return true;
}

function listenerD($params) {
	global $___STATE;
	$___STATE[] = 'd';
	return 'hello world';
}

function listenerE($params) {
	global $___STATE, $lime;
	$___STATE[] = 'e';
	$lime->ok(isset($params['subject']), 'iterate() passes the subject to all listeners');
	$lime->ok(isset($params['event']), 'iterate() passes the event to all listeners');
	return $params['subject'];
}

function listenerF($params) {
	global $___STATE;
	$___STATE[] = 'f';
	return strtoupper($params['subject']);
}

function listenerG($params) {
	global $___STATE;
	$___STATE[] = 'g';
	return $params['subject'].'s';
}

function runEventDispatcherTest($listeners, $method, $subject, $event = null) {
	global $___STATE, $lime, $dummyEvent, $dispatcher;
	
	$dispatcher->clear($dummyEvent);
	
	foreach (array_filter(explode(',', $listeners)) as $listener) {
		$dispatcher->register($dummyEvent, 'listener'.strtoupper($listener));
	}

	$___STATE = array();
	$result   = $dispatcher->$method($event === null ? $dummyEvent : $event, $subject);
	
	return $result;
}

$dispatcher = sly_Event_Dispatcher::getInstance();
$dummyEvent = 'SLY_DUMMY_'.strtoupper(uniqid().uniqid());

// generic tests ===============================================================

$lime->ok(sly_Event_Dispatcher::getInstance() === sly_Event_Dispatcher::getInstance(), 'getInstance() works as expected');

$lime->is($dispatcher->hasListeners($dummyEvent), false, 'hasListeners() returns 0 is no listeners are set');
$lime->is($dispatcher->getListeners($dummyEvent), array(), 'getListeners() returns array() is no listeners are set');

$dispatcher->register($dummyEvent, 'listenerA');
$dispatcher->register($dummyEvent, 'listenerB');

$lime->is(count($dispatcher->getListeners($dummyEvent)), 2, 'getListeners() returns array(a,b) after two listeners have been registered');

$dispatcher->register($dummyEvent, 'listenerB');
$dispatcher->register($dummyEvent, 'listenerA');

$lime->is(count($dispatcher->getListeners($dummyEvent)), 4, 'register() allows for a listener to be bound multiple times');
$lime->ok(in_array($dummyEvent, $dispatcher->getEvents()), 'getEvents() returns a list that contains our dummy event');

$dispatcher->clear($dummyEvent);

$lime->is($dispatcher->hasListeners($dummyEvent), false, 'clear() removes all bound listeners');

// notify() ====================================================================

$result = runEventDispatcherTest('', 'notify', 'foo', $dummyEvent.'_');
$lime->is($result, 0, 'notify() returns 0 if no listeners are bound');

// notify() ====================================================================

$listeners = 'a,b,a,d';
$result    = runEventDispatcherTest($listeners, 'notify', 'foo');

$lime->is($result, 4, 'notify() returns the number of bound listeners');
$lime->is(implode(',', $___STATE), $listeners, 'notify() calls the listeners in their correct order and in total (cannot be stopped)');

// notifyUntil() ===============================================================

$listeners = 'a,b';
$result    = runEventDispatcherTest($listeners, 'notifyUntil', 'foo');

$lime->is($result, false, 'notifyUntil() returns false if no listener stops the loop');
$lime->is(implode(',', $___STATE), $listeners, 'notifyUntil() calls the listeners in their correct order');

// notifyUntil() ===============================================================

$result = runEventDispatcherTest('a,c,b', 'notifyUntil', 'foo');
$lime->is($result, true, 'notifyUntil() returns true because listenerC returned true');
$lime->is($___STATE, array('a', 'c'), 'notifyUntil() really stopped at listenerC');





// filter() ====================================================================

$result = runEventDispatcherTest('f,g', 'filter', 'test');
$lime->is($result, 'TESTs', 'filter() correctly passes the value between listeners');
$lime->is($___STATE, array('f', 'g'), 'filter() called every listener');

// filter() ====================================================================

$result = runEventDispatcherTest('f,g,f', 'filter', 'test');
$lime->is($result, 'TESTS', 'filter() correctly calls every listener (even multiple times)');

unset($result, $listeners, $___STATE, $dispatcher, $dummyEvent);
