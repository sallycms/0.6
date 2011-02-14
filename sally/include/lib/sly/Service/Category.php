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
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_Category extends sly_Service_Model_Base {
	protected $tablename = 'article';

	protected function makeInstance(array $params) {
		return new sly_Model_Category($params);
	}

	public function save(sly_Model_Base $cat) {
		$persistence = sly_DB_Persistence::getInstance();

		if ($cat->getPid() == sly_Model_Base::NEW_ID) {
			$data = $cat->toHash();
			unset($data['pid']);
			$persistence->insert($this->getTableName(), $data);
			$cat->setPid($persistence->lastId());
		}
		else {
			$persistence->update($this->getTableName(), $cat->toHash(), array('pid' => $cat->getPid()));
		}

		return $cat;
	}

	public function findByPid($pid) {
		return $this->findOne(array('pid' => (int) $pid, 'startpage' => 1));
	}

	public function findById($id, $clang) {
		return $this->findOne(array('id' => (int) $id, 'clang' => $clang, 'startpage' => 1));
	}

	public function add($parentID, $name, $status, $position = -1) {
		global $REX;

		$db       = sly_DB_Persistence::getInstance();
		$parentID = (int) $parentID;
		$position = (int) $position;
		$status   = (int) $status;

		// Parent validieren

		if ($parentID !== 0 && OOCategory::getCategoryById($parentID) === null) {
			throw new sly_Exception('Parent category does not exist.');
		}

		// Artikeltyp vom Startartikel der jeweiligen Sprache vererben

		$startpageTypes = array();

		if ($parentID !== 0) {
			foreach ($db->select('article', 'clang, type', array('id' => $parentID, 'startpage' => 1)) as $row) {
				$startpageTypes[$row['clang']] = $row['type'];
			}
		}

		// Position validieren

		$maxPos   = $db->magicFetch('article', 'MAX(catprior)', 're_id = '.$parentID.' AND catprior <> 0 AND clang = 0') + 1;
		$position = ($position <= 0 || $position > $maxPos) ? $maxPos : $position;

		// Pfad ermitteln

		if ($parentID !== 0) {
			$path  = $db->magicFetch('article', 'path', array('id' => $parentID, 'startpage' => 1, 'clang' => 0));
			$path .= $parentID.'|';
		}
		else {
			$path = '|';
		}

		// Die ID ist für alle Sprachen gleich und entspricht einfach der aktuell
		// höchsten plus 1.

		$newID = $db->magicFetch('article', 'MAX(id)', array('clang' => 0)) + 1;

		// Entferne alle Kategorien aus dem Cache, die nach der aktuellen kommen und
		// daher ab Ende dieser Funktion eine neue Positionsangabe haben.

		$cache = sly_Core::cache();

		foreach (array_keys($REX['CLANG']) as $clangID) {
			$db->select('article', 'id', 'catprior > '.$position.' AND startpage = 1 AND clang = '.$clangID.' AND re_id = '.$parentID);

			foreach ($db as $row) {
				$cache->delete('sly.category', $row['id'].'_'.$clangID);
			}
		}

		// Bevor wir die neuen Datensätze einfügen, machen wir in den sortierten
		// Listen (je eine pro Sprache) Platz, indem wir alle Kategorien, deren
		// Priorität größergleich der Priorität der neuen Kategorie ist, um eine
		// Position nach unten schieben.

		$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');

		$db->query(
			'UPDATE '.$prefix.'article SET catprior = catprior + 1 '.
			'WHERE re_id = '.$parentID.' AND catprior <> 0 AND catprior >= '.$position.' '.
			'ORDER BY catprior ASC'
		);

		// Kategorie in allen Sprachen anlegen

		$defaultType = sly_Core::config()->get('DEFAULT_ARTICLE_TYPE', '');

		foreach (array_keys($REX['CLANG']) as $clangID) {
			if (!empty($startpageTypes[$clangID])) {
				$type = $startpageTypes[$clangID];
			}
			else {
				$type = $defaultType;
			}

			$cat = new sly_Model_Category(array(
				        'id' => $newID,
				     're_id' => $parentID,
				      'name' => $name,
				   'catname' => $name,
				  'catprior' => $position,
				'attributes' => '',
				 'startpage' => 1,
				     'prior' => 1,
				      'path' => $path,
				    'status' => $status ? 1 : 0,
				      'type' => $type,
				     'clang' => $clangID,
				  'revision' => 0
			));

			$cat->setUpdateColumns();
			$cat->setCreateColumns();
			$this->save($cat);

			$cache->delete('sly.category.list', $parentID.'_'.$clangID);
		}

		// System benachrichtigen

		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notify('SLY_CAT_ADDED', $newID, array(
			're_id'    => $parentID,
			'clang'    => $clangID,
			'name'     => $name,
			'position' => $position,
			'path'     => $path,
			'status'   => $status
		));

		return $newID;
	}

	public function edit($categoryID, $clangID, $name, $position = false) {
		$categoryID = (int) $categoryID;
		$clangID    = (int) $clangID;
		$db         = sly_DB_Persistence::getInstance();
		$cache      = sly_Core::cache();

		// Kategorie validieren
		$cat = $this->findById($categoryID, $clangID);

		if ($cat === null) {
			throw new sly_Exception('Category not found.');
		}

		// Kategorie selbst updaten
		$cat->setCatname($name);
		$cat->setUpdateColumns();
		$this->save($cat);

		// Cache sicherheitshalber schon einmal leeren
		$cache->delete('sly.category', $categoryID.'_'.$clangID);

		// Name der Kategorie in den Kindern ändern
		$where = array('re_id' => $categoryID, 'startpage' => 0, 'clang' => $clangID);
		$db->update('article', array('catname' => $name), $where);

		// Kinder abrufen, um für jedes Kind den Cache zu leeren.
		$db->select('article', 'id', $where);

		foreach ($db as $child) {
			rex_deleteCacheArticle($child['id'], $clangID);
		}

		// Kategorie verschieben, wenn nötig
		if ($position !== false && $position != $cat->getCatprior()) {
			$parentID = $cat->getReId();
			$oldPrio  = $cat->getCatprior();
			$position = (int) $position;

			$where   = 're_id = '.$parentID.' AND catprior <> 0 AND clang = 0';
			$maxPrio = $db->magicFetch('article', 'MAX(catprior)', $where);
			$newPrio = ($position <= 0 || $position > $maxPrio) ? $maxPrio : $position;

			// Nur aktiv werden, wenn sich auch etwas geändert hat.
			if ($newPrio != $oldPrio) {
				$prefix      = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
				$relation    = $newPrio < $oldPrio ? '+' : '-';
				list($a, $b) = $newPrio < $oldPrio ? array($newPrio, $oldPrio) : array($oldPrio, $newPrio);

				// alle anderen entsprechend verschieben
				$db->query(
					'UPDATE '.$prefix.'article SET catprior = catprior '.$relation.' 1 '.
					'WHERE catprior BETWEEN '.$a.' AND '.$b.' AND '.$where
				);

				// eigene neue Position speichern
				$cat->setCatprior($newPrio);
				$this->save($cat);

				// alle Kategorien in dieser Ebene aus dem Cache entfernen
				$db->select('article', 'id', 're_id = '.$parentID.' AND clang = '.$clangID.' AND catprior <> 0');

				foreach ($db as $row) {
					$cache->delete('sly.category', $row['id'].'_'.$clangID);
				}

				$cache->delete('sly.category.list', $parentID.'_'.$clangID);
			}
		}

		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notify('SLY_CAT_UPDATED', $cat);

		return true;
	}

	public function delete($categoryID) {
		global $REX;

		$categoryID = (int) $categoryID;
		$db         = sly_DB_Persistence::getInstance();
		$cache      = sly_Core::cache();
		$cat        = $this->findById($categoryID, 0);

		// Prüfen ob die Kategorie existiert
		if ($cat === null) {
			throw new sly_Exception('Category not found.');
		}

		// Prüfen ob die Kategorie noch Kinder (Kategorien oder Artikel) besitzt
		$where    = array('re_id' => $categoryID);
		$children = $db->magicFetch('article', 'COUNT(*)', $where);

		if ($children > 0) {
			throw new sly_Exception('Category has still content and therefore cannot be deleted.');
		}

		// Kategorie löschen
		$return = rex_deleteArticle($categoryID);
		if (!$return['state']) throw new sly_Exception($return['message']);

		// Nachbarkategorien neu positionieren
		$parent = $cat->getReId();
		$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');

		foreach (array_keys($REX['CLANG']) as $clangID) {
			$iCat     = $this->findById($categoryID, $clangID);
			$catprior = $cat->getCatprior();

			$db->query(
				'UPDATE '.$prefix.'article SET catprior = catprior - 1 '.
				'WHERE re_id = '.$parent.' AND catprior > '.$catprior.' '.
				'AND catprior <> 0 AND clang = '.$clangID
			);

			$cache->delete('sly.category', $categoryID.'_'.$clangID);
			$cache->delete('sly.category.list', $parent.'_'.$clangID);

			// Cache leeren
			$db->select('article', 'id', 're_id = '.$parent.' AND catprior >= '.$catprior.' AND clang = '.$clangID);

			foreach ($db as $row) {
				$cache->delete('sly.category', $row['id'].'_'.$clangID);
			}
		}

		// Event auslösen
		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notify('SLY_CAT_DELETED', $cat);

		return true;
	}

	public function changeStatus($categoryID, $clangID, $newStatus = null) {
		global $REX;

		$categoryID = (int) $categoryID;
		$clangID    = (int) $clangID;
		$cat        = $this->findById($categoryID, 0);

		// Prüfen ob die Kategorie existiert
		if ($cat === null) {
			throw new sly_Exception(t('no_such_category'));
		}

		$stati     = $this->getStati();
		$re_id     = $cat->getReId();
		$oldStatus = $cat->getStatus();

		// Status wurde nicht von außen vorgegeben,
		// => zyklisch auf den nächsten weiterschalten
		if ($newStatus === null) {
			$newStatus = ($oldStatus + 1) % count($stati);
		}

		// Kategorie updaten
		$cat->setStatus($newStatus);
		$cat->setUpdateColumns();
		$this->save($cat);

		// Cache leeren
		rex_deleteCacheArticle($categoryID, $clangID);

		$cache = sly_Core::cache();
		$cache->delete('sly.category', $categoryID.'_'.$clangID);
		$cache->delete('sly.category.list', $re_id.'_'.$clangID);

		// Event auslösen
		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notify('SLY_CAT_STATUS', $cat);

		return true;
	}

	public function getStati() {
		static $stati;

		if (!$stati) {
			$stati = array(
				// Name, CSS-Klasse
				array(t('status_offline'), 'rex-offline'),
				array(t('status_online'),  'rex-online')
			);

			$stati = sly_Core::dispatcher()->filter('SLY_CAT_STATUS_TYPES', $stati);
		}

		return $stati;
	}
}
