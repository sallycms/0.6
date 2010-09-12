<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * Artikel Objekt.
 * Zuständig für die Verarbeitung eines Artikel
 *
 * @ingroup redaxo
 */
class rex_article {
	public $slice_id;
	public $article_id;
	public $mode;
	public $content;
	public $function;
	public $category_id;
	public $template;
	public $save;
	public $ctype;
	public $clang;
	public $getSlice;
	public $eval;
	public $viasql;
	public $article_revision;
	public $slice_revision;
	public $warning;
	public $info;
	public $debug;

	private $slices       = array();

	public function __construct($article_id = null, $clang = null) {
		$this->article_id = 0;
		$this->template   = '';
		$this->ctype      = -1; // zeigt alles an
		$this->slice_id   = 0;

		$this->mode    = 'view';
		$this->content = '';
		$this->eval    = false;
		$this->viasql  = false;

		$this->article_revision = 0;
		$this->slice_revision   = 0;

		$this->debug   = false;
		$this->ARTICLE = null;

		if ($clang === null) {
			$clang = sly_Core::getCurrentClang();
		}

		$this->setClang($clang);

		rex_register_extension_point('ART_INIT', "", array(
			'article'    => $this,
			'article_id' => $article_id,
			'clang'      => $this->clang
		));

		if ($article_id !== null) {
			$this->setArticleId($article_id);
		}
	}

	public function setSliceRevision($sr) {
		$this->slice_revision = (int) $sr;
	}

	public function getContentAsQuery($viasql = true) {
		$this->viasql = (boolean) $viasql;
	}

	/**
	 * Slice Id setzen für Editiermodus
	 */
	public function setSliceId($id) {
		$this->slice_id = (int) $id;
	}

	public function setClang($value) {
		global $REX;
		if (!isset($REX['CLANG'][$value]) || empty($REX['CLANG'][$value])) $value = sly_Core::getCurrentClang();
		$this->clang  = $value;
		$this->slices = array();
	}

	public function getArticleId() {
		return $this->article_id;
	}

	public function getClang() {
		return $this->clang;
	}

	public function setArticleId($article_id) {
		global $REX;

		$this->article_id = (int) $article_id;

		if ($this->viasql) {
			$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
			$qry    = 'SELECT * FROM '.$prefix.'article WHERE id = '.$this->article_id.' AND clang = '.$this->clang;

			$this->ARTICLE = new rex_sql();
			$this->ARTICLE->setQuery($qry);

			if ($this->ARTICLE->getRows() > 0) {
				$this->template    = $this->getValue('template');
				$this->category_id = $this->getValue('category_id');
				return true;
			}
		}
		else {
			$this->ARTICLE = OOArticle::getArticleById($this->article_id, $this->clang);

			if (OOArticle::isValid($this->ARTICLE)) {
				$this->template    = $this->ARTICLE->getTemplateName();
				$this->category_id = $this->ARTICLE->getCategoryId();
				return true;
			}
		}

		$this->article_id  = 0;
		$this->template    = '';
		$this->category_id = 0;
		$this->slices      = array();

		return false;
	}

	public function setTemplateName($template) {
		$service = sly_Service_Factory::getService('Template');
		$this->template = $service->exists($template) ? $template : '';
		return $this->template;
	}

	public function getTemplateName() {
		return $this->template;
	}

	public function setMode($mode) {
		$this->mode = $mode;
	}

	public function setFunction($function) {
		$this->function = $function;
	}

	public function setEval($value) {
		$this->eval = (boolean) $value;
	}

	public function correctValue($value) {
		if ($value == 'category_id') {
			if ($this->getValue('startpage') != 1) $value = 're_id';
			else $value = 'id';
		}
		// Nicht generated, oder über SQL muss article_id -> id heißen
		else if ($value == 'article_id') {
			$value = 'id';
		}

		return $value;
	}

	protected function _getValue($value) {
		global $REX;
		$value = $this->correctValue($value);
		return $this->ARTICLE->getValue($value);
	}

	public function getValue($value) {
		if ($this->hasValue($value)) {
			return $this->_getValue($value);
		}

		return '['.$value.' not found]';
	}

	public function hasValue($value) {
		$value = $this->correctValue($value);
		return $this->ARTICLE->hasValue($value);
	}

	public function getArticle($curctype = -1) {
		// Einzelnes Slice ausgeben. Besser: direkt $this->getSliceOutput aufrufen.
		if ($this->getSlice) return $this->getSliceOutput($this->getSlice);
		if ($this->content != '') return $this->content;

		global $REX, $I18N;

		$this->ctype = $curctype;
		$module      = sly_request('module', 'string');
		$prior        = sly_request('prior', 'int', 0);
		// article caching
		ob_start();
		ob_implicit_flush(0);

		if (!$this->viasql && !$this->getSlice) {
			$this->printArticleContent();
		}
		else {
			if ($this->article_id != 0) {
				// Initialize $this->CONT, $this->slices, $this->predecessors
				$this->initSlices();
				$slices       = $this->slices;

				$this->content = '';

				foreach ($slices as $currentSlice) {
					// ------------- EINZELNER SLICE - AUSGABE
					$slice_content       = '';

					if ($this->mode == 'edit') { // BACKEND
						// ----- add select box einbauen
						if ($this->function == 'add' && $currentSlice['Counter'] == $prior) {
							$slice_content = $this->addSlice($currentSlice['Counter'], $module);
						}
						else {
							$slice_content = $this->getAddModuleForm($currentSlice['Counter']); // ----- BLOCKAUSWAHL - SELECT
						}

						// ----- EDIT/DELETE BLOCK - Wenn Rechte vorhanden

						if ($REX['USER']->isAdmin() || $REX['USER']->hasPerm('module['.$currentSlice['Module'].']')) {
							$slice_content = $this->getEditSliceMarkup($currentSlice, $slice_content);
						}
						else {
							// ----- hat keine Rechte an diesem Modul
							$mne = '
								<div class="rex-content-editmode-module-name">
									<h3 class="rex-hl4" id="slice'.$currentSlice['ID'].'">'.sly_html($currentSlice['ModuleName']).'</h3>
									<div class="rex-navi-slice">
										<ul>
											<li>'.$I18N->msg('no_editing_rights').' <span>'.sly_html($currentSlice['ModuleName']).'</span></li>
										</ul>
									</div>
								</div>';
							$slice_content .= $mne.$this->getSliceOutput($currentSlice['ID']);
						}
					}

					// --------------- ENDE EINZELNER SLICE

					// ---------- Slice in Ausgabe speichern wenn ctype richtig
					if ($this->ctype == -1 or $this->ctype == $currentSlice['CType']) {
						$this->content .= $slice_content;

					}

					// zum nächsten Slice
				}

				// ----- add module im edit mode
				if ($this->mode == 'edit') {
					if ($this->function == 'add' && count($slices) == $prior) {
						$slice_content = $this->addSlice(count($slices), $module);
					}
					else {
						$slice_content = $this->getAddModuleForm(count($slices));
					}

					$this->content .= $slice_content;
				}

				// schreibe Content
				if ($this->eval === false) print $this->content;
				else eval("?>".$this->content);
			}
			else {
				print $I18N->msg('no_article_available');
			}
		}

		$CONTENT = ob_get_clean();
		return $CONTENT;
	}

	private function printArticleContent() {
		$sql = sly_DB_Persistence::getInstance();
		$where = array('article_id' => $this->article_id, 'clang' => $this->clang);
		if($this->ctype != -1) {
			$where['ctype'] = $this->ctype;
		}
		$sql->select('article_slice', 'id', $where, null, 'ctype, prior ASC');
		foreach($sql as $articleSliceId) {
			print OOArticleSlice::getArticleSliceById($articleSliceId, $this->clang)->getContent();
		}
/*
		if ($this->article_id != 0) {
			global $REX;

			$article_content_file = SLY_DYNFOLDER.'/internal/sally/articles/'.$this->article_id.'.'.$this->clang.'.content.php';

			if (!file_exists($article_content_file)) {
				include_once SLY_INCLUDE_PATH.'/functions/function_rex_generate.inc.php';
				$generated = rex_generateArticleContent($this->article_id, $this->clang);

				if ($generated !== true) {
					// Fehlermeldung ausgeben
					print $generated;
				}
			}

			if (file_exists($article_content_file)) {
				include $article_content_file;
			}
		}
 *
 */
	}

	/**
	 * Holt alle Slices des aktuellen Artikels aus der Datenbank und legt das
	 * Result-Array in $this->CONT ab.
	 */
	private function fetchArticleSlices() {
		// ---------- alle teile/slices eines artikels auswaehlen
		// slice + value + modul + artikel
		$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
		$query  =
			'SELECT slices.*, a.re_id FROM '.$prefix.'article_slice AS slices '.
			'LEFT JOIN '.$prefix.'article a ON slices.article_id = a.id '.
			'WHERE '.
				'slices.article_id = '.intval($this->article_id).' AND '.
				'slices.ctype = '.intval($this->ctype).' AND '.
				'slices.clang = '.intval($this->clang).' AND a.clang = '.intval($this->clang).' AND '.
				'slices.revision = '.intval($this->slice_revision).' '.
			'ORDER BY slices.prior';

		$sql = new rex_sql();
		$sql->setQuery($query);
		$this->CONT = $sql;
	}

	private function initSlices() {
		if (!empty($this->slices)) return;

		$this->fetchArticleSlices();

		$slices       = array();
		$service      = sly_Service_Factory::getService('Module');

		// SLICE IDS/MODUL SETZEN - Speichern der Daten

		for ($i = 0; $i < $this->CONT->getRows(); $i++) {
			$sliceId         = $this->CONT->getValue('slices.id');

			$slices[$sliceId]['ID']           = $sliceId;
			$slices[$sliceId]['CType']        = $this->CONT->getValue('slices.ctype');
			$slices[$sliceId]['sliceId']      = $this->CONT->getValue('slices.slice_id');
			$slices[$sliceId]['Module']       = $this->CONT->getValue('slices.module');
			$slices[$sliceId]['ModuleName']   = $service->getTitle($slices[$sliceId]['Module']);
			$slices[$sliceId]['Counter']      = $i;


			$this->CONT->next();
		}

		$this->CONT->reset();
		$this->slices       = $slices;
	}

	private function getSliceOutput($sliceID) {
		// damit $REX verfügbar ist
		global $REX;

		ob_start();
		ob_implicit_flush(0);

		$this->initSlices();

		$slice        = $this->slices[$sliceID];
		$service      = sly_Service_Factory::getService('Module');
		$sliceContent = (string) $service->getContent($slice['Module'], 'output');

		$sliceContent = $this->replaceObjectVars($slice['sliceId'], $sliceContent);
		$sliceContent = $this->triggerSliceShowEP($sliceContent, $slice);

		if ($this->eval === false) echo $sliceContent;
		else eval("?>".$sliceContent);

		$CONTENT = ob_get_clean();
		return $CONTENT;
	}

	private function triggerSliceShowEP($content, $slice) {
		return rex_register_extension_point('SLICE_SHOW', $content, array(
			'article_id'        => $this->article_id,
			'clang'             => $this->clang,
			'ctype'             => $slice['CType'],
			'module'            => $slice['Module'],
			'slice_id'          => $slice['ID'],
			'function'          => $this->function,
			'function_slice_id' => $this->slice_id
		));
	}

	public function getModuleSelect() {
		static $moduleSelect;
		if (empty($moduleSelect)) {
			global $REX, $I18N;

			$moduleService   = sly_Service_Factory::getService('Module');
			$templateService = sly_Service_Factory::getService('Template');
			$modules         = $moduleService->getModules();
			$slots           = $templateService->getSlots($this->template);
			$moduleSelect    = array();

			foreach (array_keys($slots) as $slotID) {
				$moduleSelect[$slotID] = new rex_select();
				$moduleSelect[$slotID]->setName('module');
				$moduleSelect[$slotID]->setSize('1');
				$moduleSelect[$slotID]->setStyle('class="rex-form-select"');
				$moduleSelect[$slotID]->setAttribute('onchange', 'this.form.submit();');
				$moduleSelect[$slotID]->addOption('----------------------------  '.$I18N->msg('add_block'),'');

				foreach ($modules as $module => $moduleTitle) {
					if ($REX['USER']->isAdmin() || $REX['USER']->hasPerm('module['.$module.']')) {
						if ($templateService->hasModule($this->template, $slotID, $module)) {
							$moduleSelect[$slotID]->addOption(rex_translate($moduleTitle, null, false), $module);
						}
					}
				}
			}
		}

		return $moduleSelect;
	}

	private function getAddModuleForm($prior) {
		global $I18N;

		$formURL      = 'index.php';
		$moduleSelect = $this->getModuleSelect();
		$formID       = ' id="slice'.$prior.'"';

		$moduleSelect[$this->ctype]->setId('module'.$prior);

		$sliceContent = '
			<div class="rex-form rex-form-content-editmode">
				<form action="'.$formURL.'" method="get"'.$formID.'>
					<fieldset class="rex-form-col-1">
						<legend><span>'. $I18N->msg("add_block") .'</span></legend>
						<input type="hidden" name="article_id" value="'.$this->article_id.'" />
						<input type="hidden" name="page" value="content" />
						<input type="hidden" name="mode" value="'.$this->mode.'" />
						<input type="hidden" name="prior" value="'.$prior.'" />
						<input type="hidden" name="function" value="add" />
						<input type="hidden" name="clang" value="'.$this->clang.'" />
						<input type="hidden" name="ctype" value="'.$this->ctype.'" />

						<div class="rex-form-wrapper">
							<div class="rex-form-row">
								<p class="rex-form-col-a rex-form-select">
									'.$moduleSelect[$this->ctype]->get().'
									<noscript><input class="rex-form-submit" type="submit" name="btn_add" value="'.$I18N->msg("add_block").'" /></noscript>
								</p>
							</div>
						</div>
					</fieldset>
				</form>
			</div>';
		return $sliceContent;
	}

	private function getEditSliceMarkup($currentSlice, $slice_content) {
		global $REX, $I18N;

		$msg = '';

		if ($this->slice_id == $currentSlice['ID']) {
			if (!empty($this->warning)) $msg .= rex_warning($this->warning);
			if (!empty($this->info))    $msg .= rex_info($this->info);
		}

		$sliceUrl = 'index.php?page=content&amp;article_id='.$this->article_id.'&amp;mode=edit&amp;slice_id='.$currentSlice['ID'].'&amp;clang='.$this->clang.'&amp;ctype='.$this->ctype.'%s#slice'.$currentSlice['Counter'];
		$listElements = array();
		$listElements[] = '<a href="'.sprintf($sliceUrl, '&amp;function=edit').'" class="rex-tx3">'.$I18N->msg('edit').' <span>'.sly_html($currentSlice['ModuleName']).'</span></a>';
		$listElements[] = '<a href="'.sprintf($sliceUrl, '&amp;function=delete&amp;save=1').'" class="rex-tx2" onclick="return confirm(\''.$I18N->msg('delete').' ?\')">'.$I18N->msg('delete').' <span>'.sly_html($currentSlice['ModuleName']).'</span></a>';

		if ($REX['USER']->isAdmin() || $REX['USER']->hasPerm('moveSlice[]')) {
			$moveUp   = $I18N->msg('move_slice_up');
			$moveDown = $I18N->msg('move_slice_down');

			// upd stamp übergeben, da sonst ein block nicht mehrfach hintereindander verschoben werden kann
			// (Links wären sonst gleich und der Browser lässt das klicken auf den gleichen Link nicht zu)
			// WTF?!

			$listElements[] = '<a href="'. sprintf($sliceUrl, '&amp;upd='.time().'&amp;function=moveup')  .'" title="'.$moveUp  .'" class="rex-slice-move-up"><span>'.  $currentSlice['ModuleName'].'</span></a>';
			$listElements[] = '<a href="'. sprintf($sliceUrl, '&amp;upd='.time().'&amp;function=movedown').'" title="'.$moveDown.'" class="rex-slice-move-down"><span>'.$currentSlice['ModuleName'].'</span></a>';
		}

		$listElements = rex_register_extension_point('ART_SLICE_MENU', $listElements, array(
			'article_id' => $this->article_id,
			'clang'      => $this->clang,
			'ctype'      => $currentSlice['CType'],
			'module'     => $currentSlice['Module'],
			'slice_id'   => $currentSlice['ID']
		));

		$mne = $msg;

		if ($this->function=="edit" && $this->slice_id == $currentSlice['ID']) {
			$mne .= '<div class="rex-content-editmode-module-name rex-form-content-editmode-edit-slice">';
		}
		else {
			$mne .= '<div class="rex-content-editmode-module-name">';
		}

		$mne .= '
			<h3 class="rex-hl4">'.sly_html($currentSlice['ModuleName']).'</h3>
			<div class="rex-navi-slice">
				<ul>';

		$listElementFlag = true;

		foreach ($listElements as $listElement) {
			$class = '';

			if ($listElementFlag) {
				$class = ' class="rex-navi-first"';
				$listElementFlag = false;
			}

			$mne .= '<li'.$class.'>'.$listElement.'</li>';
		}

		$mne           .= '</ul></div></div>';
		$slice_content .= $mne;

		if ($this->function == 'edit' && $this->slice_id == $currentSlice['ID']) {
			// **************** Aktueller Slice

			// ----- PRE VIEW ACTION [EDIT]
			$REX_ACTION = array();

			// nach klick auf den Übernehmen button,
			// die POST werte übernehmen

			if (rex_var::isEditEvent()) {
				foreach (sly_Core::getVarTypes() as $obj) {
					$REX_ACTION = $obj->getACRequestValues($REX_ACTION);
				}
			}

			// Sonst die Werte aus der DB holen
			// (1. Aufruf via Editieren Link)

			else {
				foreach (sly_Core::getVarTypes() as $obj) {
					$REX_ACTION = $obj->getACDatabaseValues($REX_ACTION, $currentSlice['sliceId']);
				}
			}

			if     ($this->function == 'edit')   $modebit = 2; // pre-action and edit
			elseif ($this->function == 'delete') $modebit = 4; // pre-action and delete
			else                                 $modebit = 1; // pre-action and add

			$moduleService = sly_Service_Factory::getService('Module');
			$actionService = sly_Service_Factory::getService('Action');
			$actions       = $moduleService->getActions($currentSlice['Module']);
			$actions       = isset($actions['preview']) ? sly_makeArray($actions['preview']) : array();

			foreach ($actions as $actionName) {
				$action = $actionService->getContent($actionName, 'preview');

				// Variablen ersetzen
				foreach (sly_Core::getVarTypes() as $obj) {
					$iaction = $obj->getACOutput($REX_ACTION, $action);
				}

				eval('?>'.$action);

				// Speichern (falls nätig)

				foreach (sly_Core::getVarTypes() as $obj) {
					$obj->setACValues($currentSlice['sliceId'], $REX_ACTION);
				}
			}

			// ----- / PRE VIEW ACTION

			$moduleInput    = $moduleService->getContent($currentSlice['Module'], 'input');
			$slice_content .= $this->editSlice($currentSlice['ID'], $moduleInput, $currentSlice['CType'], $currentSlice['Module']);
			$slice_content  = $this->triggerSliceShowEP($slice_content, $currentSlice);
		}
		else {
			// Modulinhalt ausgeben
			$slice_content .= '
				<!-- *** OUTPUT OF MODULE-OUTPUT - START *** -->
				<div class="rex-content-editmode-slice-output">
					<div class="rex-content-editmode-slice-output-2">
						'.$this->getSliceOutput($currentSlice['ID']).'
					</div>
				</div>
				<!-- *** OUTPUT OF MODULE-OUTPUT - END *** -->
			';
		}

		return $slice_content;
	}

	// ----- Template inklusive Artikel zurückgeben
	public function getArticleTemplate() {
		// global $REX hier wichtig, damit in den Artikeln die Variable vorhanden ist!
		global $REX;

		if (!empty($this->template) && $this->article_id != 0) {
			$service  = sly_Service_Factory::getService('Template');
			$template = $this->template;

			if (!$service->isGenerated($template)) $service->generate($template);
			$templateFile = $service->getCacheFile($template);

			unset($service, $template);

			ob_start();
			ob_implicit_flush(0);
			include $templateFile;
			$content = ob_get_clean();
		}
		else {
			$content = 'Kein Template';
		}

		return $content;
	}

  // ----- ADD Slice
  public function addSlice($prior, $module)
  {
    global $REX,$I18N;

    $moduleService = sly_Service_Factory::getService('Module');

    if (!$moduleService->exists($module))
    {
      $slice_content = rex_warning($I18N->msg('module_doesnt_exist'));
    }
    else
    {
      $slice_content = '
        <a name="addslice"></a>
        <div class="rex-form rex-form-content-editmode-add-slice">
        <form action="index.php#slice'.$prior.'" method="post" id="REX_FORM" enctype="multipart/form-data">
          <fieldset class="rex-form-col-1">
            <legend><span>'. $I18N->msg('add_block').'</span></legend>
            <input type="hidden" name="article_id" value="'.$this->article_id.'" />
            <input type="hidden" name="page" value="content" />
            <input type="hidden" name="mode" value="'.$this->mode.'" />
            <input type="hidden" name="prior" value="'.$prior.'" />
            <input type="hidden" name="function" value="add" />
            <input type="hidden" name="module" value="'.sly_html($module).'" />
            <input type="hidden" name="save" value="1" />
            <input type="hidden" name="clang" value="'.$this->clang.'" />
            <input type="hidden" name="ctype" value="'.$this->ctype.'" />

            <div class="rex-content-editmode-module-name">
              <h3 class="rex-hl4">
                '.$I18N->msg("module").': <span>'.sly_html($moduleService->getTitle($module)).'</span>
              </h3>
            </div>

            <div class="rex-form-wrapper">

              <div class="rex-form-row">
                <div class="rex-content-editmode-slice-input">
                <div class="rex-content-editmode-slice-input-2">
                  '.$moduleService->getContent($module, 'input').'
                </div>
                </div>
              </div>

            </div>
          </fieldset>

          <fieldset class="rex-form-col-1">
             <div class="rex-form-wrapper">
              <div class="rex-form-row">
                <p class="rex-form-col-a rex-form-submit">
                  <input class="rex-form-submit" type="submit" name="btn_save" value="'.$I18N->msg('add_block').'" />
                </p>
              </div>
            </div>
          </fieldset>
        </form>
        </div>
        <script type="text/javascript">
           <!--
          jQuery(function($) {
            $(":input:visible:enabled:not([readonly]):first", $("form#REX_FORM")).focus();
          });
           //-->
        </script>';

      // Beim Add hier die Meldung ausgeben
      // Sollte das hier nicht immer true sein?
      if ($this->slice_id == 0)
      {
         if ($this->warning != '')
         {
           echo rex_warning($this->warning);
         }
         if ($this->info != '')
         {
           echo rex_info($this->info);
         }
      }

      $slice_content = $this->replaceVars(0, $slice_content);
    }

    return $slice_content;
  }

  // ----- EDIT Slice
  public function editSlice($sliceID, $moduleInput, $ctype, $module)
  {
    global $REX, $I18N;

    $slice_content = '
      <a name="editslice"></a>
      <div class="rex-form rex-form-content-editmode-edit-slice">
      <form enctype="multipart/form-data" action="index.php#slice'.$sliceID.'" method="post" id="REX_FORM">
        <fieldset class="rex-form-col-1">
          <legend><span>'. $I18N->msg('edit_block') .'</span></legend>
          <input type="hidden" name="article_id" value="'.$this->article_id.'" />
          <input type="hidden" name="page" value="content" />
          <input type="hidden" name="mode" value="'.$this->mode.'" />
          <input type="hidden" name="slice_id" value="'.$sliceID.'" />
          <input type="hidden" name="ctype" value="'.$ctype.'" />
          <input type="hidden" name="function" value="edit" />
          <input type="hidden" name="save" value="1" />
          <input type="hidden" name="update" value="0" />
          <input type="hidden" name="clang" value="'.$this->clang.'" />

          <div class="rex-form-wrapper">
            <div class="rex-form-row">
              <div class="rex-content-editmode-slice-input">
              <div class="rex-content-editmode-slice-input-2">
              '.$moduleInput.'
              </div>
              </div>
            </div>
          </div>
        </fieldset>

        <fieldset class="rex-form-col-2">
          <div class="rex-form-wrapper">
            <div class="rex-form-row">
              <p class="rex-form-col-a rex-form-submit">
                <input class="rex-form-submit" type="submit" value="'.$I18N->msg('save_block').'" name="btn_save" />
                <input class="rex-form-submit rex-form-submit-2" type="submit" value="'.$I18N->msg('update_block').'" name="btn_update" />
              </p>
            </div>
          </div>
        </fieldset>
      </form>
      </div>
      <script type="text/javascript">
         <!--
        jQuery(function($) {
          $(":input:visible:enabled:not([readonly]):first", $("form#REX_FORM")).focus();
        });
         //-->
      </script>';

		return $this->replaceVars($sliceID, $slice_content);
	}

	/**
	 * Modulvariablen werden ersetzt
	 */
	public function replaceVars($sliceID, $content, $forceMode = null) {
		$content = $this->replaceObjectVars($sliceID, $content);
		$content = $this->replaceCommonVars($content, $forceMode);
		return $content;
	}

	/**
	 * REX_VAR-Ersetzungen
	 */
	public function replaceObjectVars($slice_id, $content, $forceMode = null) {
		global $REX;

		$tmp      = '';
		$mode     = $forceMode === null ? $this->mode : $forceMode;
		$artslice = OOArticleSlice::_getSliceWhere('slice_id = '.intval($slice_id));

		foreach (sly_Core::getVarTypes() as $idx => $var) {
			if ($mode == 'edit') {
				if (($this->function == 'add' && $slice_id == '0') || ($this->function == 'edit' && $artslice && $artslice->getId() == $this->slice_id)) {
					if (isset($REX['ACTION']['SAVE']) && $REX['ACTION']['SAVE'] === false) {
						// Wenn der aktuelle Slice nicht gespeichert werden soll
						// (via Action wurde das Nicht-Speichern-Flag gesetzt)
						// Dann die Werte manuell aus dem Post übernehmen
						// und anschließend die Werte wieder zurücksetzen,
						// damit die nächsten Slices wieder die Werte aus der DB verwenden
						$var->setACValues($slice_id, $REX['ACTION']);
						$tmp = $var->getBEInput($slice_id, $content);
					}
					else {
						// Slice normal parsen
						$tmp = $var->getBEInput($slice_id, $content);
					}
				}
				else {
					$tmp = $var->getBEOutput($slice_id, $content);
				}
			}

			// Rückgabewert nur auswerten wenn auch einer vorhanden ist

			if ($tmp !== null) {
				$content = $tmp;
			}
		}

		return $content;
	}

	/**
	 * artikelweite globale Variablen werden ersetzt
	 */
	function replaceCommonVars($content) {
		global $REX;

		static $user_id    = null;
		static $user_login = null;

		// UserId gibt's nur im Backend

		if ($user_id === null) {
			if (isset($REX['USER'])) {
				$user_id    = $REX['USER']->getValue('id');
				$user_login = $REX['USER']->getValue('login');
			}
			else {
				$user_id    = '';
				$user_login = '';
			}
		}

		static $search = array(
			'REX_ARTICLE_ID',
			'REX_CATEGORY_ID',
			'REX_CLANG_ID',
			'REX_TEMPLATE_NAME',
			'REX_USER_ID',
			'REX_USER_LOGIN'
		);

		$replace = array(
			$this->article_id,
			$this->category_id,
			$this->clang,
			$this->getTemplateName(),
			$user_id,
			$user_login
		);

		return str_replace($search, $replace,$content);
	}
}
