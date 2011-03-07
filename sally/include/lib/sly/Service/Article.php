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
class sly_Service_Article extends sly_Service_Model_Base {
	protected $tablename = 'article';

	protected function makeInstance(array $params) {
		return new sly_Model_Article($params);
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
		return $this->findOne(array('pid' => (int) $pid, 'startpage' => 0));
	}

	public function findById($id, $clang) {
		return $this->findOne(array('id' => (int) $id, 'clang' => $clang, 'startpage' => 0));
	}

	public function add($categoryID, $name, $status, $position = -1) {
		global $REX;

		$db       = sly_DB_Persistence::getInstance();
		$parentID = (int) $categoryID;
		$position = (int) $position;
		$status   = (int) $status;

		// Parent validieren

		if ($parentID !== 0 && OOCategory::getCategoryById($parentID) === null) {
			throw new sly_Exception('Parent category does not exist.');
		}

		// Artikeltyp vom Startartikel der jeweiligen Sprache vererben
		// Catname ermitteln

		$types    = array();
		$catnames = array();

		if ($parentID !== 0) {
			foreach ($db->select('article', 'clang, type', array('id' => $parentID, 'startpage' => 1)) as $row) {
				$types[$row['clang']] = $row['type'];
			}

			foreach ($db->select('article', 'clang, catname', 'id = '.$parentID.' AND catprior <> 0 AND startpage = 1') as $row) {
				$catnames[$row['clang']] = $row['catname'];
			}
		}

		// Position validieren

		$where    = '((re_id = '.$parentID.' AND catprior = 0) OR (id = '.$parentID.')) AND clang = 1';
		$maxPos   = $db->magicFetch('article', 'MAX(prior)', $where) + 1;
		$position = ($position <= 0 || $position > $maxPos) ? $maxPos : $position;

		// Pfad ermitteln

		if ($parentID !== 0) {
			$path = $db->magicFetch('article', 'path', array('id' => $parentID, 'startpage' => 1, 'clang' => 0));
			$path = $path.$parentID.'|';
		}
		else {
			$path = '|';
		}

		// Die ID ist für alle Sprachen gleich und entspricht einfach der aktuell
		// höchsten plus 1.

		$newID = $db->magicFetch('article', 'MAX(id)', array('clang' => 0)) + 1;

		// Entferne alle Artikel aus dem Cache, die nach dem aktuellen kommen und
		// daher ab Ende dieser Funktion eine neue Positionsangabe haben.

		$cache = sly_Core::cache();

		foreach (array_keys($REX['CLANG']) as $clangID) {
			$db->select('article', 'id', 'prior > '.$position.' AND startpage = 0 AND clang = '.$clangID.' AND re_id = '.$parentID);

			foreach ($db as $row) {
				$cache->delete('sly.article', $row['id'].'_'.$clangID);
			}
		}

		// Bevor wir die neuen Datensätze einfügen, machen wir in den sortierten
		// Listen (je eine pro Sprache) Platz, indem wir alle Artikel, deren
		// Priorität größergleich der Priorität des neuen Artikels ist, um eine
		// Position nach unten schieben.

		$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');

		$db->query(
			'UPDATE '.$prefix.'article SET prior = prior + 1 '.
			'WHERE ((re_id = '.$parentID.' AND catprior = 0) OR id = '.$parentID.') AND prior >= '.$position.' '.
			'ORDER BY prior ASC'
		);

		// Kategorie in allen Sprachen anlegen

		$defaultType = sly_Core::config()->get('DEFAULT_ARTICLE_TYPE', '');

		foreach (array_keys($REX['CLANG']) as $clangID) {
			$article = new sly_Model_Article(array(
				        'id' => $newID,
				     're_id' => $parentID,
				      'name' => $name,
				   'catname' => !empty($catnames[$clangID]) ? $catnames[$clangID] : '',
				  'catprior' => 0,
				'attributes' => '',
				 'startpage' => 0,
				     'prior' => $position,
				      'path' => $path,
				    'status' => $status ? 1 : 0,
				      'type' => !empty($types[$clangID]) ? $types[$clangID] : $defaultType,
				     'clang' => $clangID,
				  'revision' => 0
			));

			$cat->setUpdateColumns();
			$cat->setCreateColumns();
			$this->save($article);

			// online/offline Listen leeren
			$cache->delete('sly.article.list', $parentID.'_'.$clangID.'_0');
			$cache->delete('sly.article.list', $parentID.'_'.$clangID.'_1');
		}

		// System benachrichtigen

		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notify('SLY_ART_ADDED', $newID, array(
			're_id'    => $parentID,
			'clang'    => $clangID,
			'name'     => $name,
			'position' => $position,
			'path'     => $path,
			'status'   => $status
		));

		return $newID;
	}

	public function edit($articleID, $clangID, $name, $position = false) {
		$articleID = (int) $articleID;
		$clangID   = (int) $clangID;
		$db        = sly_DB_Persistence::getInstance();
		$cache     = sly_Core::cache();

		// Artikel validieren
		$article = $this->findById($articleID, $clangID);

		if ($article === null) {
			throw new sly_Exception('Article not found.');
		}

		// Artikel selbst updaten
		$article->setName($name);
		$article->setUpdateColumns();
		$this->save($article);

		// Cache sicherheitshalber schon einmal leeren
		$cache->delete('sly.article', $articleID.'_'.$clangID);

		// Kategorie verschieben, wenn nötig
		if ($position !== false && $position != $article->getPrior()) {
			$parentID = $cat->getParentId();
			$oldPrio  = $cat->getPrior();
			$position = (int) $position;

			$where   = '((re_id = '.$parentID.' AND catprior = 0) OR id = '.$parentID.') AND clang = '.$clangID;
			$maxPrio = $db->magicFetch('article', 'MAX(prior)', $where);
			$newPrio = ($position <= 0 || $position > $maxPrio) ? $maxPrio : $position;

			// Nur aktiv werden, wenn sich auch etwas geändert hat.
			if ($newPrio != $oldPrio) {
				$prefix      = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
				$relation    = $newPrio < $oldPrio ? '+' : '-';
				list($a, $b) = $newPrio < $oldPrio ? array($newPrio, $oldPrio) : array($oldPrio, $newPrio);

				// alle anderen entsprechend verschieben
				$db->query(
					'UPDATE '.$prefix.'article SET prior = prior '.$relation.' 1 '.
					'WHERE prior BETWEEN '.$a.' AND '.$b.' AND '.$where
				);

				// eigene neue Position speichern
				$article->setPrior($newPrio);
				$this->save($article);

				// alle Artikel in dieser Ebene aus dem Cache entfernen
				$db->select('article', 'id', array('re_id' => $parentID, 'clang' => $clangID, 'catprior' => 0));

				foreach ($db as $row) {
					$cache->delete('sly.article', $row['id'].'_'.$clangID);
				}

				$cache->delete('sly.article.list', $parentID.'_'.$clangID.'_0');
				$cache->delete('sly.article.list', $parentID.'_'.$clangID.'_1');
			}
		}

		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notify('SLY_ART_UPDATED', $article);

		return true;
	}

	public function delete($articleID) {
		global $REX;

		$articleID = (int) $articleID;
		$db        = sly_DB_Persistence::getInstance();
		$cache     = sly_Core::cache();
		$article   = $this->findById($articleID, 0);

		// Prüfen ob der Artikel existiert
		if ($article === null) {
			throw new sly_Exception('Article not found.');
		}

		// Artikel löschen
		$return = rex_deleteArticle($articleID);
		if (!$return['state']) throw new sly_Exception($return['message']);

		// Nachbarartikel neu positionieren
		$parent = $article->getParentId();
		$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');

		foreach (array_keys($REX['CLANG']) as $clangID) {
			$iArticle = $this->findById($articleID, $clangID);
			$prior    = $cat->getPrior();
			$where    = 'prior >= '.$prior.' AND re_id = '.$parent.' AND catprior = 0 AND clang = '.$clangID;

			$db->query('UPDATE '.$prefix.'article SET prior = prior - 1 WHERE '.$where);

			$cache->delete('sly.article', $articleID.'_'.$clangID);
			$cache->delete('sly.article.list', $parent.'_'.$clangID.'_0');
			$cache->delete('sly.article.list', $parent.'_'.$clangID.'_1');

			// Cache leeren
			$db->select('article', 'id', $where);

			foreach ($db as $row) {
				$cache->delete('sly.article', $row['id'].'_'.$clangID);
			}
		}

		// Event auslösen
		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notify('SLY_ART_DELETED', $article);

		return true;
	}

	public function changeStatus($articleID, $clangID, $newStatus = null) {
		global $REX;

		$articleID = (int) $articleID;
		$clangID   = (int) $clangID;
		$article   = $this->findById($articleID, 0);

		// Prüfen ob die Artikel existiert
		if ($article === null) {
			throw new sly_Exception(t('no_such_article'));
		}

		$stati     = $this->getStati();
		$re_id     = $article->getParentId();
		$oldStatus = $article->getStatus();

		// Status wurde nicht von außen vorgegeben,
		// => zyklisch auf den nächsten weiterschalten
		if ($newStatus === null) {
			$newStatus = ($oldStatus + 1) % count($stati);
		}

		// Artikel updaten
		$article->setStatus($newStatus);
		$article->setUpdateColumns();
		$this->save($article);

		// Cache leeren
		$cache = sly_Core::cache();
		$cache->delete('sly.article', $articleID.'_'.$clangID);
		$cache->delete('sly.article.list', $re_id.'_'.$clangID.'_0');
		$cache->delete('sly.article.list', $re_id.'_'.$clangID.'_1');

		// Event auslösen
		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notify('SLY_ART_STATUS', $article);

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

			$stati = sly_Core::dispatcher()->filter('SLY_ART_STATUS_TYPES', $stati);
		}

		return $stati;
	}
}
