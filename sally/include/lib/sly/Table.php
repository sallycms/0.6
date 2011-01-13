<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @ingroup table
 */
class sly_Table
{
	protected $id;
	protected $columns;
	protected $isEmpty;
	protected $emptyNotice;
	protected $enableSorting;
	protected $enableSearching;
	protected $enableDragAndDrop;
	protected $dragAndDropHandler;
	protected $totalElements;

	protected static $perPage = 30;

	public function __construct($id = null)
	{
		// Das 'a'-Präfix verhindert, dass die ID mit einer Zahl beginnt, was in HTML nicht erlaubt ist.

		$this->id                 = $id === null ? 'a'.substr(md5(uniqid()), 0, 10) : $id;
		$this->columns            = array();
		$this->isEmpty            = false;
		$this->emptyNotice        = 'Es wurden noch keine Datensätze angelegt.';
		$this->enableSorting      = false;
		$this->enableSearching    = false;
		$this->enableDragAndDrop  = false;
		$this->dragAndDropHandler = '';
		$this->totalElements      = null;
	}

	public function setID($id) {
		$this->id = trim($id);
	}

	public static function isDragAndDropMode()
	{
		return sly_get('tableswitch', 'string') == 'dodraganddrop';
	}

	public static function setElementsPerPageStatic($perPage)
	{
		self::$perPage = abs((int) $perPage);

		if (self::$perPage < 1) {
			self::$perPage = 1;
		}
	}

	public function setElementsPerPage($perPage = 20)
	{
		self::setElementsPerPageStatic($perPage);
	}

	public function renderHeader($totalElements = null)
	{
		$this->totalElements = $totalElements;
		include SLY_INCLUDE_PATH.'/views/_table/table/header.phtml';
	}

	public function renderFooter()
	{
		include SLY_INCLUDE_PATH.'/views/_table/table/footer.phtml';
	}

	public function setIsEmpty($isEmpty)
	{
		$this->isEmpty = (bool) $isEmpty;
	}

	public function setEmptyNotice($notice)
	{
		$this->emptyNotice = $notice;
	}

	public function enableSorting($enable)
	{
		$this->enableSorting = (bool) $enable;
	}

	public function enableDragAndDrop($enable)
	{
		$this->enableDragAndDrop = (bool) $enable;
	}

	public function setDragAndDropHandler($function)
	{
		$this->dragAndDropHandler = $function;
	}

	public function addColumn(sly_Table_Column $col)
	{
		$this->columns[] = $col;
	}

	public function enableSearching($enable)
	{
		$this->enableSearching = (bool) $enable;
	}

	public function isSorting()
	{
		return $this->enableSorting;
	}

	public function isSearching()
	{
		return $this->enableSearching;
	}

	public function getSortKey($default = null)
	{
		return sly_get('sortby', 'string', $default);
	}

	public function getDirection($default = 'asc')
	{
		return sly_get('direction', 'string', $default);
	}

	/**
	 * Gibt true zurück, wenn ein Pager angezeigt werden soll.
	 */
	public function hasPager()
	{
		return $this->totalElements !== null && $this->totalElements > self::$perPage;
	}

	/**
	 * Gibt true zurück, wenn die Suchmaske angezeigt werden soll.
	 */
	public function hasSearch()
	{
		// Die Suchfunktion ist immer dann aktiviert, wenn sie im Objekt
		// aktiviert wurde (enableSearching) und wenn der Tabellenmodus
		// nicht auf dodraganddrop steht.

		return $this->enableSearching;
	}

	/**
	 * Gibt true zurück, ob Drag&Drop aktiviert werden soll.
	 */
	public function hasDragAndDrop()
	{
		// D&D ist nur aktiv, wenn es explizit aktiviert wurde. Zusätzlich:
		// D&D ist immer dann aktiv, wenn die Tabelle sich im dodraganddrop-Modus
		// befindet oder wenn weder Pager noch Suchfunktion aktiv sind. Also:

		// D&D = ACTIVE & (dodraganddrop || (!PAGER && !SEARCH))

		return $this->enableDragAndDrop && (self::isDragAndDropMode() || (!$this->hasPager() && !$this->enableSearching));
	}

	public static function getPagingParameters($tableName = 'table', $hasPager = false, $hasDragAndDrop = false)
	{
		$perPage = self::$perPage;

		$page     = sly_get('p_'.$tableName, 'int', 0);
		$start    = $page * $perPage;
		$elements = $perPage;
		$end      = $start + $perPage;
		$getAll   = array('page' => 0, 'start' => 0, 'end' => PHP_INT_MAX, 'elements' => PHP_INT_MAX);

		if (!$hasPager || ($hasDragAndDrop && self::isDragAndDropMode())) {
			return $getAll;
		}

		return array('page' => $page, 'start' => $start, 'end' => $end, 'elements' => $elements);
	}

	public static function getSearchParameters($tableName = 'table')
	{
		return sly_get('q_'.$tableName, 'string');
	}

	public static function getSortingParameters($defaultColumn, $enabledColumns = array())
	{
		$sortby    = sly_get('sortby', 'string', $defaultColumn);
		$direction = strtolower(sly_get('direction', 'string', 'asc')) == 'desc' ? 'DESC' : 'ASC';

		if (!in_array($sortby, $enabledColumns)) {
			$sortby = $defaultColumn;
		}

		return array('sortby' => $sortby, 'direction' => $direction);
	}
}
