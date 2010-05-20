<?php

/**
 * Artikel Objekt.
 * Zuständig für die Verarbeitung eines Artikel
 *
 * @package redaxo4
 * @version svn:$Id$
 */

class rex_article
{
  var $slice_id;
  var $article_id;
  var $mode;
  var $content;
  var $function;

  var $category_id;  
  
  var $template_id;
  var $template_attributes;
  
  var $save;
  var $ctype;
  var $clang;
  var $getSlice;
  
  var $eval;
  var $viasql;

  var $article_revision;
  var $slice_revision;

  var $warning;
  var $info;
  var $debug;
  
  private $slices       = array();
  private $predecessors = array();
  
  public function __construct($article_id = null, $clang = null)
  {
    $this->article_id = 0;
    $this->template_id = 0;
    $this->ctype = -1; // zeigt alles an
    $this->slice_id = 0;

    $this->mode = "view";
    $this->content = "";

    $this->eval = false;
    $this->viasql = false;

    $this->article_revision = 0;
    $this->slice_revision = 0;

    $this->debug = FALSE;
    $this->ARTICLE = null;
        
    if($clang === null)
      $clang = sly_Core::getCurrentClang();

    $this->setClang($clang);

    // ----- EXTENSION POINT
    rex_register_extension_point('ART_INIT', "",
      array (
        'article' => &$this,
        'article_id' => $article_id,
        'clang' => $this->clang
      )
    );

    if ($article_id !== null)
      $this->setArticleId($article_id);

  }

  function setSliceRevision($sr)
  {
    $this->slice_revision = (int) $sr;
  }

  function getContentAsQuery($viasql = TRUE)
  {
    if ($viasql !== TRUE) $viasql = FALSE;
    $this->viasql = $viasql;
  }

  // ----- Slice Id setzen für Editiermodus
  function setSliceId($value)
  {
    $this->slice_id = $value;
  }

  function setClang($value)
  {
    global $REX;
    if (!isset($REX['CLANG'][$value]) || empty($REX['CLANG'][$value])) $value = sly_Core::getCurrentClang();
    $this->clang = $value;
    $this->slices = array();
  }
  
  function getArticleId()
  {
    return $this->article_id;
  }

  function getClang()
  {
    return $this->clang;
  }
  
  function setArticleId($article_id)
  {
    global $REX;

    $this->article_id = (int) $article_id;

    if ($this->viasql)
    {
      // ---------- select article
      $this->ARTICLE = new rex_sql();
      if($this->debug)
      	$this->ARTICLE->debugsql = 1;
      $qry = "SELECT * FROM ".$REX['TABLE_PREFIX']."article WHERE ".$REX['TABLE_PREFIX']."article.id='$this->article_id' AND clang='".$this->clang."' LIMIT 1";
      $this->ARTICLE->setQuery($qry);

      if ($this->ARTICLE->getRows() > 0)
      {
        $this->template_id = $this->getValue('template_id');
        $this->category_id = $this->getValue('category_id');
        return TRUE;
      }
    }
    else
    {
      $this->ARTICLE = OOArticle::getArticleById($this->article_id, $this->clang);
      if(OOArticle::isValid($this->ARTICLE))
      {
      	$this->template_id = $this->ARTICLE->getTemplateId();
        $this->category_id = $this->ARTICLE->getCategoryId();
        return TRUE;
      }
    }
    
    $this->article_id = 0;
    $this->template_id = 0;
    $this->category_id = 0;
    $this->slices = array();
    return FALSE;
  }

  function setTemplateId($template_id)
  {
    $this->template_id = $template_id;
  }

  function getTemplateId()
  {
    return $this->template_id;
  }

  function setMode($mode)
  {
    $this->mode = $mode;
  }

  function setFunction($function)
  {
    $this->function = $function;
  }

  function setEval($value)
  {
    $this->eval = $value;
  }

  function correctValue($value)
  {
    if ($value == 'category_id')
    {
      if ($this->getValue('startpage')!=1) $value = 're_id';
      else $value = 'id';
    }
    // Nicht generated, oder über SQL muss article_id -> id heißen
    else if ($value == 'article_id')
    {
      $value = 'id';
    }

    return $value;
  }

  function _getValue($value)
  {
    global $REX;
    $value = $this->correctValue($value);
    return $this->ARTICLE->getValue($value);
  }

  function getValue($value)
  {
    // damit alte rex_article felder wie teaser, online_from etc
    // noch funktionieren
    // gleicher BC code nochmals in OOREDAXO::getValue
    foreach(array('', 'art_', 'cat_') as $prefix)
    {
      $val = $prefix . $value;
      if($this->hasValue($val))
      {
      	return $this->_getValue($val);
      }
    }
    return '['. $value .' not found]';
  }

  function hasValue($value)
  {
    $value = $this->correctValue($value);
    return $this->ARTICLE->hasValue($value);
  }

	function getArticle($curctype = -1) {
		// Einzelnes Slice ausgeben. Besser: direkt $this->getSliceOutput aufrufen.
		if ($this->getSlice) return $this->getSliceOutput($this->getSlice);
		if ($this->content != "") return $this->content;
		
		global $REX,$I18N;

		
		$this->ctype = $curctype;
		$module_id   = rex_request('module_id', 'int');

		// ----- start: article caching
		ob_start();
		ob_implicit_flush(0);
		
		
		if (!$this->viasql && !$this->getSlice) $this->printArticleContent();
		else {
			if ($this->article_id != 0) {
				// Initialize $this->CONT, $this->slices, $this->predecessors
				$this->initSlices();
				$predecessors = $this->predecessors;
				$slices       = $this->slices;
				
				// ---------- SLICE IDS SORTIEREN UND AUSGEBEN
				$currentPredecessorID = $this->getSlice ? $slices[$this->getSlice]['Previous'] : 0;
				$lastSliceID   = 0;
				$this->content = "";

				while (isset($predecessors[$currentPredecessorID]) && isset($slices[$predecessors[$currentPredecessorID]])) {
					// ------------- EINZELNER SLICE - AUSGABE
					$slice_content       = "";
					$SLICE_SHOW          = TRUE;
					$currentSlice        = $slices[$predecessors[$currentPredecessorID]];
					$this->CONT->counter = $currentSlice['Counter'];
					
					if ($this->mode == "edit") { // BACKEND
						// ----- add select box einbauen
						if ($this->function=="add" && $this->slice_id == $currentPredecessorID) $slice_content = $this->addSlice($currentPredecessorID, $module_id);
						else $slice_content = $this->getAddModuleForm($currentSlice); // ----- BLOCKAUSWAHL - SELECT
						
						// ----- EDIT/DELETE BLOCK - Wenn Rechte vorhanden
						if ($REX['USER']->isAdmin() || $REX['USER']->hasPerm("module[".$currentSlice['ModuleID']."]")) {
							$slice_content = $this->getEditSliceMarkup($currentSlice, $slice_content);
						}
						else {
							// ----- hat keine rechte an diesem modul
							$mne = '
								<div class="rex-content-editmode-module-name">
									<h3 class="rex-hl4" id="slice'.$currentSlice['ID'].'">'.htmlspecialchars($currentSlice['ModuleName']).'</h3>
									<div class="rex-navi-slice">
										<ul>
											<li>'.$I18N->msg('no_editing_rights').' <span>'.htmlspecialchars($currentSlice['ModuleName']).'</span></li>
										</ul>
									</div>
								</div>';
							$slice_content .= $mne.$this->getSliceOutput($currentSlice['ID']);
						}
					}
					else {
						throw new Exception('es gibt offensichtlich doch einen fall, in dem mode nicht edit ist und trotzdem ein einzelnes slice gerendert wird.');
						// sollte diese exception mal fliegen, könnte es sein, dass die kommende zeile doch wichtig ist ;)
						// $slice_content.= $this->getSliceOutput($currentSlice['ID']);
					}
					// --------------- ENDE EINZELNER SLICE
					
					// ---------- slice in ausgabe speichern wenn ctype richtig
					if ($this->ctype == -1 or $this->ctype == $currentSlice['CType']) {
						$this->content .= $slice_content;
						
						// last content type slice id
						$lastSliceID = $currentSlice['ID'];
					}
					
					// zum nachsten slice
					$currentPredecessorID = $currentSlice['ID'];
				}
				
				// ----- add module im edit mode
				if ($this->mode == "edit") {
					if ($this->function=="add" && $this->slice_id == $lastSliceID) $slice_content = $this->addSlice($lastSliceID, $module_id);
					else $slice_content = $this->getAddModuleForm(array('ID' => $lastSliceID), true);
					$this->content .= $slice_content;
				}
				
				// -------------------------- schreibe content
				if ($this->eval === FALSE) echo $this->content;
				else eval("?>".$this->content);
			}
			else echo $I18N->msg('no_article_available');
		}
		
		// ----- end: article caching
		$CONTENT = ob_get_clean();
		return $CONTENT;
	}
	
	private function printArticleContent() {
		if ($this->article_id != 0) {
			global $REX;
			$article_content_file = $REX['INCLUDE_PATH'].'/generated/articles/'.$this->article_id.'.'.$this->clang.'.content';
			if (!file_exists($article_content_file)) {
				include_once ($REX["INCLUDE_PATH"]."/functions/function_rex_generate.inc.php");
				$generated = rex_generateArticleContent($this->article_id, $this->clang);
				if ($generated !== true) {
					// fehlermeldung ausgeben
					echo $generated;
				}
			}
			if (file_exists($article_content_file)) include $article_content_file;
		}
	}

	/**
	 * Holt alle Slices des aktuellen Artikels aus der Datenbank und legt das 
	 * Result-Array in $this->CONT ab.
	 */
	private function fetchArticleSlices() {
		global $REX;
		// ---------- alle teile/slices eines artikels auswaehlen
		// slice + value + modul + artikel
		$query = "SELECT ".$REX['TABLE_PREFIX']."module.id, ".$REX['TABLE_PREFIX']."module.name, ".$REX['TABLE_PREFIX']."module.ausgabe, ".$REX['TABLE_PREFIX']."module.eingabe, ".$REX['TABLE_PREFIX']."article_slice.*, ".$REX['TABLE_PREFIX']."article.re_id
			FROM
				".$REX['TABLE_PREFIX']."article_slice
				LEFT JOIN ".$REX['TABLE_PREFIX']."module ON ".$REX['TABLE_PREFIX']."article_slice.modultyp_id=".$REX['TABLE_PREFIX']."module.id
				LEFT JOIN ".$REX['TABLE_PREFIX']."article ON ".$REX['TABLE_PREFIX']."article_slice.article_id=".$REX['TABLE_PREFIX']."article.id
			WHERE
				".$REX['TABLE_PREFIX']."article_slice.article_id='".$this->article_id."' AND
				".$REX['TABLE_PREFIX']."article_slice.clang='".$this->clang."' AND
				".$REX['TABLE_PREFIX']."article.clang='".$this->clang."' AND
				".$REX['TABLE_PREFIX']."article_slice.revision='".$this->slice_revision."'
				ORDER BY ".$REX['TABLE_PREFIX']."article_slice.re_article_slice_id";
		
		$sql = new rex_sql;
		if ($this->debug) $sql->debugsql = 1;
		$sql->setQuery($query);
		$this->CONT = $sql;
	}
	
	private function initSlices() {
		if (!empty($this->slices)) return;
		global $REX;
		$this->fetchArticleSlices();
		
		$slices = null;
		$predecessors = null;
		// ---------- SLICE IDS/MODUL SETZEN - speichern der daten
		for ($i=0; $i < $this->CONT->getRows(); $i++) {
			$previousSliceID = $this->CONT->getValue('re_article_slice_id');
			$sliceId         = $this->CONT->getValue($REX['TABLE_PREFIX'].'article_slice.id');
			
			$slices[$sliceId]['ID']           = $sliceId;
			$slices[$sliceId]['Previous']     = $previousSliceID;
			$slices[$sliceId]['CType']        = $this->CONT->getValue($REX['TABLE_PREFIX'].'article_slice.ctype');
			$slices[$sliceId]['sliceId']      = $this->CONT->getValue($REX['TABLE_PREFIX'].'article_slice.slice_id');
			$slices[$sliceId]['ModuleInput']  = $this->CONT->getValue($REX['TABLE_PREFIX'].'module.eingabe');
			$slices[$sliceId]['ModuleOutput'] = $this->CONT->getValue($REX['TABLE_PREFIX'].'module.ausgabe');
			$slices[$sliceId]['ModuleID']     = $this->CONT->getValue($REX['TABLE_PREFIX'].'module.id');
			$slices[$sliceId]['ModuleName']   = $this->CONT->getValue($REX['TABLE_PREFIX'].'module.name');
			$slices[$sliceId]['Counter']      = $i;
			
			$predecessors[$previousSliceID]   = $sliceId;
			
			$this->CONT->next();
		}
		
		$this->CONT->reset();
		$this->slices = $slices;
		$this->predecessors = $predecessors;
	}
	
	private function getSliceOutput($sliceID) {
		//damit $REX verfügbar ist
		global $REX;
		
		ob_start();
		ob_implicit_flush(0);
		
		$this->initSlices();
		$slice = $this->slices[$sliceID];
		$sliceContent = $slice['ModuleOutput'];
		// TODO: abhängigkeit zu CONT auflösen
		$this->CONT->counter = $slice['Counter'];

		$sliceContent = $this->replaceObjectVars($slice['sliceId'], $sliceContent);
		
		// --------------- EP: SLICE_SHOW
		$sliceContent = $this->triggerSliceShowEP($sliceContent, $slice);
		
		if ($this->eval === FALSE) echo $sliceContent;
		else eval("?>".$sliceContent);
		
		$CONTENT = ob_get_clean();
		return $CONTENT;
	}
	
	private function triggerSliceShowEP($content, $slice) {
		return rex_register_extension_point(
						'SLICE_SHOW',
						$content,
						array(
							'article_id'        => $this->article_id,
							'clang'             => $this->clang,
							'ctype'             => $slice['CType'],
							'module_id'         => $slice['ModuleID'],
							'slice_id'          => $slice['ID'],
							'function'          => $this->function,
							'function_slice_id' => $this->slice_id
						)
					);
	}

	public function getModuleSelect() {
		static $moduleSelect;
		if (empty($moduleSelect)) {
			global $REX, $I18N;
			
			$moduleService = sly_Service_Factory::getService('Module');
			$modules = $moduleService->find(null, null, 'name');
			
			$template_ctypes = rex_getAttributes('ctype', $this->template_attributes, array ());
			// wenn keine ctyes definiert sind, gibt es immer den CTYPE=1
			if (count($template_ctypes) == 0) $template_ctypes = array(1 => 'default');
			
			$moduleSelect = array();
			foreach ($template_ctypes as $ct_id => $ct_name) {
				$moduleSelect[$ct_id] = new rex_select;
				$moduleSelect[$ct_id]->setName('module_id');
				$moduleSelect[$ct_id]->setSize('1');
				$moduleSelect[$ct_id]->setStyle('class="rex-form-select"');
				$moduleSelect[$ct_id]->setAttribute('onchange', 'this.form.submit();');
				$moduleSelect[$ct_id]->addOption('----------------------------  '.$I18N->msg('add_block'),'');
				
				foreach($modules as $module) {
					if ($REX['USER']->isAdmin() || $REX['USER']->hasPerm('module['.$module->getId().']')) {
						if(rex_template::hasModule($this->template_attributes,$ct_id,$module->getId())) {
							$moduleSelect[$ct_id]->addOption(rex_translate($module->getName(),NULL,FALSE),$module->getId());
						}
					}
				}
			}
		}
		return $moduleSelect;
	}

	private function getAddModuleForm($currentSlice, $last = false) {
		global $I18N;
		$formURL      = 'index.php';
		$moduleSelect = $this->getModuleSelect();
		$sliceID      = $last ? $currentSlice['ID'] : $currentSlice['Previous'];
		$formID       = $last ? '' : ' id="slice'.$currentSlice['ID'].'"';
		$moduleSelect[$this->ctype]->setId("module_id".$sliceID);

		
		$sliceContent = '
			<div class="rex-form rex-form-content-editmode">
				<form action="'.$formURL.'" method="get"'.$formID.'>
					<fieldset class="rex-form-col-1">
						<legend><span>'. $I18N->msg("add_block") .'</span></legend>
						<input type="hidden" name="article_id" value="'. $this->article_id .'" />
						<input type="hidden" name="page" value="content" />
						<input type="hidden" name="mode" value="'. $this->mode .'" />
						<input type="hidden" name="slice_id" value="'.$sliceID.'" />
						<input type="hidden" name="function" value="add" />
						<input type="hidden" name="clang" value="'.$this->clang.'" />
						<input type="hidden" name="ctype" value="'.$this->ctype.'" />
						
						<div class="rex-form-wrapper">
							<div class="rex-form-row">
								<p class="rex-form-col-a rex-form-select">
									'.$moduleSelect[$this->ctype]->get().'
									<noscript><input class="rex-form-submit" type="submit" name="btn_add" value="'. $I18N->msg("add_block") .'" /></noscript>
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
			if ($this->warning != '') $msg .= rex_warning($this->warning);
			if ($this->info != '')    $msg .= rex_info($this->info);
		}
		
		$sliceUrl = 'index.php?page=content&amp;article_id='.$this->article_id.'&amp;mode=edit&amp;slice_id='.$currentSlice['ID'].'&amp;clang='.$this->clang.'&amp;ctype='.$this->ctype.'%s#slice'.$currentSlice['ID'];
		$listElements = array();
		$listElements[] = '<a href="'.sprintf($sliceUrl, '&amp;function=edit').'" class="rex-tx3">'.$I18N->msg('edit').' <span>'.htmlspecialchars($currentSlice['ModuleName']).'</span></a>';
		$listElements[] = '<a href="'.sprintf($sliceUrl, '&amp;function=delete&amp;save=1').'" class="rex-tx2" onclick="return confirm(\''.$I18N->msg('delete').' ?\')">'.$I18N->msg('delete').' <span>'.htmlspecialchars($currentSlice['ModuleName']).'</span></a>';
		
		if ($REX['USER']->isAdmin() || $REX['USER']->hasPerm('moveSlice[]')) {
			$moveUp = $I18N->msg('move_slice_up');
			$moveDown = $I18N->msg('move_slice_down');
			// upd stamp übergeben, da sonst ein block nicht mehrfach hintereindander verschoben werden kann
			// (Links wären sonst gleich und der Browser lässt das klicken auf den gleichen Link nicht zu)
			$listElements[] = '<a href="'. sprintf($sliceUrl, '&amp;upd='.time().'&amp;function=moveup')  .'" title="'.$moveUp  .'" class="rex-slice-move-up"><span>'.  $currentSlice['ModuleName'].'</span></a>';
			$listElements[] = '<a href="'. sprintf($sliceUrl, '&amp;upd='.time().'&amp;function=movedown').'" title="'.$moveDown.'" class="rex-slice-move-down"><span>'.$currentSlice['ModuleName'].'</span></a>';
		}
		
		// ----- EXTENSION POINT
		$listElements = rex_register_extension_point('ART_SLICE_MENU', $listElements,
			array(
				'article_id' => $this->article_id,
				'clang'      => $this->clang,
				'ctype'      => $currentSlice['CType'],
				'module_id'  => $currentSlice['ModuleID'],
				'slice_id'   => $currentSlice['ID']
			)
		);
		
		$mne = $msg;
		
		if ($this->function=="edit" && $this->slice_id == $currentSlice['ID']) $mne .= '<div class="rex-content-editmode-module-name rex-form-content-editmode-edit-slice">';
		else $mne .= '<div class="rex-content-editmode-module-name">';
		
		$mne .= '
			<h3 class="rex-hl4">'.htmlspecialchars($currentSlice['ModuleName']).'</h3>
			<div class="rex-navi-slice">
				<ul>';
		
		$listElementFlag = true;
		foreach ($listElements as $listElement) {
			$class = '';
			if ($listElementFlag) {
				$class = ' class="rex-navi-first"';
				$listElementFlag = false;
			}
			$mne  .= '<li'.$class.'>'.$listElement.'</li>';
		}
		
		$mne .= '</ul></div></div>';
		
		$slice_content .= $mne;
		if ($this->function=="edit" && $this->slice_id == $currentSlice['ID']) {
			// **************** Aktueller Slice
			
			// ----- PRE VIEW ACTION [EDIT]
			$REX_ACTION = array ();
			
			// nach klick auf den Übernehmen button,
			// die POST werte übernehmen
			if (rex_var::isEditEvent()) {
				foreach (sly_Core::getVarTypes() as $obj) $REX_ACTION = $obj->getACRequestValues($REX_ACTION);
			}
			// Sonst die Werte aus der DB holen
			// (1. Aufruf via Editieren Link)
			else {
				// TODO: Abhängigkeit von CONT aufheben.
				foreach (sly_Core::getVarTypes() as $obj) $REX_ACTION = $obj->getACDatabaseValues($REX_ACTION, $this->CONT);
			}
			
			if     ($this->function == 'edit')   $modebit = '2'; // pre-action and edit
			elseif ($this->function == 'delete') $modebit = '4'; // pre-action and delete
			else                                 $modebit = '1'; // pre-action and add
			
			$ga = new rex_sql;
			if ($this->debug) $ga->debugsql = 1;
			
			$ga->setQuery('SELECT preview FROM '.$REX['TABLE_PREFIX'].'module_action ma,'. $REX['TABLE_PREFIX']. 'action a WHERE preview != "" AND ma.action_id=a.id AND module_id='.$currentSlice['ModuleID'].' AND ((a.previewmode & '.$modebit.') = '.$modebit.')');
			
			for ($t=0; $t < $ga->getRows(); $t++) {
				$iaction = $ga->getValue('preview');
				
				// ****************** VARIABLEN ERSETZEN
				foreach (sly_Core::getVarTypes() as $obj) $iaction = $obj->getACOutput($REX_ACTION, $iaction);
				eval('?>'.$iaction);
				
				// ****************** SPEICHERN FALLS NOETIG
				// TODO: abhängigkeit zu CONT auflösen
				foreach (sly_Core::getVarTypes() as $obj) $obj->setACValues($this->CONT, $REX_ACTION);
				
				$ga->next();
			}
			
			// ----- / PRE VIEW ACTION
			$slice_content .= $this->editSlice($currentSlice['ID'], $currentSlice['ModuleInput'], $currentSlice['CType'], $currentSlice['ModuleID']);
			$slice_content = $this->triggerSliceShowEP($slice_content, $currentSlice);
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
  function getArticleTemplate()
  {
    // global $REX hier wichtig, damit in den Artikeln die Variable vorhanden ist!
    global $REX;

    if ($this->template_id != 0 && $this->article_id != 0)
    {

      $templateFile = rex_template::getFilePath($this->template_id);
      
      if (!file_exists($templateFile)) {
        $tpl = new rex_template($this->template_id);
        $tpl->generate();
        unset($tpl);
      }
      
      ob_start();
      ob_implicit_flush(0);
      include $templateFile;
      $CONTENT = ob_get_clean();
    }
    else
    {
      $CONTENT = "no template";
    }

    return $CONTENT;
  }

  // ----- ADD Slice
  function addSlice($I_ID,$module_id)
  {
    global $REX,$I18N;

	$moduleService = sly_Service_Factory::getService('Module');
	$module = $moduleService->findById($module_id); 
    if (!$module)
    {
      $slice_content = rex_warning($I18N->msg('module_doesnt_exist'));
    }else
    {
      $slice_content = '
        <a name="addslice"></a>
        <div class="rex-form rex-form-content-editmode-add-slice">
        <form action="index.php#slice'. $I_ID .'" method="post" id="REX_FORM" enctype="multipart/form-data">
          <fieldset class="rex-form-col-1">
            <legend><span>'. $I18N->msg('add_block').'</span></legend>
            <input type="hidden" name="article_id" value="'. $this->article_id .'" />
            <input type="hidden" name="page" value="content" />
            <input type="hidden" name="mode" value="'. $this->mode .'" />
            <input type="hidden" name="slice_id" value="'. $I_ID .'" />
            <input type="hidden" name="function" value="add" />
            <input type="hidden" name="module_id" value="'. $module_id .'" />
            <input type="hidden" name="save" value="1" />
            <input type="hidden" name="clang" value="'. $this->clang .'" />
            <input type="hidden" name="ctype" value="'.$this->ctype .'" />
            
            <div class="rex-content-editmode-module-name">
              <h3 class="rex-hl4">
                '. $I18N->msg("module") .': <span>'. htmlspecialchars($module->getName()) .'</span>
              </h3>
            </div>
              
            <div class="rex-form-wrapper">
              
              <div class="rex-form-row">
                <div class="rex-content-editmode-slice-input">
                <div class="rex-content-editmode-slice-input-2">
                  '. $module->getInput() .'
                </div>
                </div>
              </div>
              
            </div>
          </fieldset>
          
          <fieldset class="rex-form-col-1">
             <div class="rex-form-wrapper">              
              <div class="rex-form-row">
                <p class="rex-form-col-a rex-form-submit">
                  <input class="rex-form-submit" type="submit" name="btn_save" value="'. $I18N->msg('add_block') .'" />
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
      if($this->slice_id == 0)
      {
         if($this->warning != '')
         {
           echo rex_warning($this->warning);
         }
         if($this->info != '')
         {
           echo rex_info($this->info);
         }
      }

/*     $dummysql = new rex_sql();

      // Den Dummy mit allen Feldern aus rex_article_slice füllen
      $slice_fields = new rex_sql();
      $slice_fields->setQuery('SELECT * FROM '. $REX['TABLE_PREFIX'].'article_slice LIMIT 1');
      foreach($slice_fields->getFieldnames() as $fieldname)
      {
        switch($fieldname)
        {
          case 'clang'        : $def_value = $this->clang; break;
          case 'ctype'        : $def_value = $this->ctype; break;
          case 'modultyp_id'  : $def_value = $module_id; break;
          case 'article_id'   : $def_value = $this->article_id; break;
          case 'id'           : $def_value = 0; break;
          case 'slice_id'     : $def_value = 0; break;
          default             : $def_value = '';
        }
        $dummysql->setValue($REX['TABLE_PREFIX']. 'article_slice.'. $fieldname, $def_value);
      }*/
      $slice_content = $this->replaceVars(0, $slice_content);
    }
    return $slice_content;
  }

  // ----- EDIT Slice
  function editSlice($RE_CONTS, $RE_MODUL_IN, $RE_CTYPE, $RE_MODUL_ID)
  {
    global $REX, $I18N;

    $slice_content = '
      <a name="editslice"></a>
      <div class="rex-form rex-form-content-editmode-edit-slice">
      <form enctype="multipart/form-data" action="index.php#slice'.$RE_CONTS.'" method="post" id="REX_FORM">
        <fieldset class="rex-form-col-1">
          <legend><span>'. $I18N->msg('edit_block') .'</span></legend>
          <input type="hidden" name="article_id" value="'.$this->article_id.'" />
          <input type="hidden" name="page" value="content" />
          <input type="hidden" name="mode" value="'.$this->mode.'" />
          <input type="hidden" name="slice_id" value="'.$RE_CONTS.'" />
          <input type="hidden" name="ctype" value="'.$RE_CTYPE.'" />
          <input type="hidden" name="module_id" value="'. $RE_MODUL_ID .'" />
          <input type="hidden" name="function" value="edit" />
          <input type="hidden" name="save" value="1" />
          <input type="hidden" name="update" value="0" />
          <input type="hidden" name="clang" value="'.$this->clang.'" />
            
          <div class="rex-form-wrapper">
            <div class="rex-form-row">
              <div class="rex-content-editmode-slice-input">
              <div class="rex-content-editmode-slice-input-2">
              '. $RE_MODUL_IN .'
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

	$slice_id = $this->CONT->getValue($REX['TABLE_PREFIX'].'article_slice.slice_id');

	$slice_content = $this->replaceVars($slice_id, $slice_content);
    return $slice_content;
  }

  // ----- Modulvariablen werden ersetzt
  function replaceVars($slice_id, $content, $forceMode = null)
  {
    $content = $this->replaceObjectVars($slice_id,$content);
    $content = $this->replaceCommonVars($content, $forceMode);
    return $content;
  }

  // ----- REX_VAR Ersetzungen
  function replaceObjectVars($slice_id, $content, $forceMode = null)
  {
    global $REX;

    $tmp = '';
    $mode    = $forceMode === null ? $this->mode : $forceMode;

	$artslice = OOArticleSlice::_getSliceWhere('slice_id = '. $slice_id);


    foreach(sly_Core::getVarTypes() as $idx => $var)
    {
      if ($mode == 'edit')
      {
        if (($this->function == 'add' && $slice_id == '0') ||
            ($this->function == 'edit' && $artslice && $artslice->getId() == $this->slice_id))
        {
          if (isset($REX['ACTION']['SAVE']) && $REX['ACTION']['SAVE'] === false)
          {
            // Wenn der aktuelle Slice nicht gespeichert werden soll
            // (via Action wurde das Nicht-Speichern-Flag gesetzt)
            // Dann die Werte manuell aus dem Post übernehmen
            // und anschließend die Werte wieder zurücksetzen,
            // damit die nächsten Slices wieder die Werte aus der DB verwenden
            $var->setACValues($slice_id, $REX['ACTION']);
            $tmp = $var->getBEInput($slice_id, $content);
          }
          else
          {
            // Slice normal parsen
            $tmp = $var->getBEInput($slice_id, $content);
          }
        }else
        {
          $tmp = $var->getBEOutput($slice_id, $content);
        }
      }

      // Rückgabewert nur auswerten wenn auch einer vorhanden ist
      // damit $content nicht verfälscht wird
      // null ist default Rückgabewert, falls kein RETURN in einer Funktion ist
      if($tmp !== null)
      {
        $content = $tmp;
      }
    }

    return $content;
  }

  // ---- Artikelweite globale variablen werden ersetzt
  function replaceCommonVars($content)
  {
    global $REX;

    static $user_id = null;
    static $user_login = null;

    // UserId gibts nur im Backend
    if($user_id === null)
    {
      if(isset($REX['USER']))
      {
        $user_id = $REX['USER']->getValue('user_id');
        $user_login = $REX['USER']->getValue('login');
      }else
      {
        $user_id = '';
        $user_login = '';
      }
    }

    static $search = array(
       'REX_ARTICLE_ID',
       'REX_CATEGORY_ID',
       'REX_CLANG_ID',
       'REX_TEMPLATE_ID',
       'REX_USER_ID',
       'REX_USER_LOGIN'
    );

    $replace = array(
      $this->article_id,
      $this->category_id,
      $this->clang,
      $this->getTemplateId(),
      $user_id,
      $user_login
    );
    
    return str_replace($search, $replace,$content);
  }

}