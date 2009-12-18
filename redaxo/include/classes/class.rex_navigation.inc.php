<?php

/**
 * Klasse zum Erstellen von Navigationen, v0.1
 *
 * @package redaxo4
 * @version svn:$Id$
 */

/*
 * Beispiel:
 *
 * UL, LI Navigation von der Rootebene aus,
 * 2 Ebenen durchgehen, Alle unternavis offen
 * und offline categorien nicht beachten
 *
 * Navigation:
 * 
 * $nav = rex_navigation::factory();
 * $nav->setClasses(array('lev1', 'lev2', 'lev3'));
 * echo $nav->get(0,2,TRUE,TRUE);
 *
 * Sitemap:
 *
 * $nav = rex_navigation::factory();
 * $nav->show(0,-1,TRUE,TRUE);
 * 
 * Breadcrump:
 * 
 * $nav = rex_navigation::factory();
 * $nav->showBreadcrump(true);
 */

class rex_navigation
{
	var $depth; // Wieviele Ebene tief, ab der Startebene
	var $open; // alles aufgeklappt, z.b. Sitemap
	var $ignore_offlines;
	var $path = array();
	var $classes = array();

	var $current_article_id = -1; // Aktueller Artikel
	var $current_category_id = -1; // Aktuelle Katgorie

	/*private*/ function rex_navigation()
	{
	}

  /*public*/ function factory()
  {
    static $class = null;

    if(!$class)
    {
      // ----- EXTENSION POINT
      $class = rex_register_extension_point('REX_NAVI_CLASSNAME', 'rex_navigation');
    }

    return new $class();
  }
  
  /**
   * Generiert eine Navigation
   * 
   * @param $category_id Id der Wurzelkategorie
   * @param $depth Anzahl der Ebenen die angezeigt werden sollen
   * @param $open True, wenn nur Elemente der aktiven Kategorie angezeigt werden sollen, sonst FALSE
   * @param $ignore_offlines FALSE, wenn offline Elemente angezeigt werden, sonst TRUE
   */
	/*public*/ function get($category_id = 0,$depth = 3,$open = FALSE, $ignore_offlines = FALSE)
	{
    if(!$this->_setActivePath()) return FALSE;
    
	  $this->depth = $depth;
    $this->open = $open;
    $this->ignore_offlines = $ignore_offlines;
	  
		return $this->_getNavigation($category_id,$this->ignore_offlines);
	}

  /**
   * @see get()
   */
	/*public*/ function show($category_id = 0,$depth = 3,$open = FALSE, $ignore_offlines = FALSE)
	{
		echo $this->get($category_id, $depth, $open, $ignore_offlines);
	}
	
  /**
   * Generiert eine Breadcrumb-Navigation
   * 
   * @param $startPageLabel Label der Startseite, falls FALSE keine Start-Page anzeigen
   * @param $includeCurrent True wenn der aktuelle Artikel enthalten sein soll, sonst FALSE
   * @param $category_id Id der Wurzelkategorie
   */
	/*public*/ function getBreadcrumb($startPageLabel, $includeCurrent = FALSE, $category_id = 0)
	{
	  if(!$this->_setActivePath()) return FALSE;
	  
	  global $REX;
    
	  $path = $this->path;
            
    $i = 1;
    $lis = '';
    
    if($startPageLabel)
    {
      $lis .= '<li class="rex-lvl'. $i .'"><a href="'. rex_getUrl($REX['START_ARTICLE_ID']) .'">'. htmlspecialchars($startPageLabel) .'</a></li>';
      $i++;

      // StartArticle nicht doppelt anzeigen
      if(isset($path[0]) && $path[0] == $REX['START_ARTICLE_ID'])
      {
        unset($path[0]);
      }
    }
    
    foreach($path as $pathItem)
    {
      $cat = OOCategory::getCategoryById($pathItem);
      $lis .= '<li class="rex-lvl'. $i .'"><a href="'. $cat->getUrl() .'">'. htmlspecialchars($cat->getName()) .'</a></li>';
      $i++;
    }
    
    if($includeCurrent)
    {
      if($art = OOArticle::getArticleById($this->current_article_id))
        if(!$art->isStartpage())
        {
          $lis .= '<li class="rex-lvl'. $i .'">'. htmlspecialchars($art->getName()) .'</li>';
        }else
        {
        	$cat = OOCategory::getCategoryById($this->current_article_id);
          $lis .= '<li class="rex-lvl'. $i .'">'. htmlspecialchars($cat->getName()) .'</li>';
        }
    }
    
    return '<ul class="rex-breadcrumb">'. $lis .'</ul>';
	}
	
	/**
	 * @see getBreadcrumb()
	 */
  /*public*/ function showBreadcrumb($includeCurrent = FALSE, $category_id = 0)
  {
    echo $this->getBreadcrumb($includeCurrent, $category_id);
  }
  
	/*public*/ function setClasses($classes)
	{
	  $this->classes = $classes;
	}

	/*private*/ function _setActivePath()
	{
		global $REX;

		$article_id = $REX["ARTICLE_ID"];
		if($OOArt = OOArticle::getArticleById($article_id))
		{
		  $path = trim($OOArt->getValue("path"), '|');
		  
		  $this->path = array();
		  if($path != "")
			 $this->path = explode("|",$path);
			 
      $this->current_article_id = $article_id;
			$this->current_category_id = $OOArt->getCategoryId();
			return TRUE;
		}
		
		return FALSE;
	}

	/*protected*/ function _getNavigation($category_id,$ignore_offlines = TRUE)
	{
	  static $depth = 0;
	  
    if($category_id < 1)
	  	$nav_obj = OOCategory::getRootCategories($ignore_offlines);
		else
	  	$nav_obj = OOCategory::getChildrenById($category_id, $ignore_offlines);

  	$return = "";

		if(count($nav_obj)>0)
		  $return .= '<ul class="rex-navi'. ($depth+1) .'">';

		foreach($nav_obj as $nav)
		{
		  $liClass = '';
		  $linkClass = '';
		  
		  // classes abhaengig vom pfad
			if($nav->getId() == $this->current_category_id)
			{
			  $liClass .= ' rex-current';
			  $linkClass .= ' rex-current';
			}
			elseif (in_array($nav->getId(),$this->path))
			{
        $liClass .= ' rex-active';
			  $linkClass .= ' rex-active';
			}
			else
			{
        $liClass .= ' rex-normal';
			}
			
      // classes abhaengig vom level
      if(isset($this->classes[$depth]))
        $liClass .= ' '. $this->classes[$depth];
      
			$liClass   = $liClass   == '' ? '' : ' class="'. ltrim($liClass) .'"';
			$linkClass = $linkClass == '' ? '' : ' class="'. ltrim($linkClass) .'"';
			  
      $return .= '<li id="rex-article-'. $nav->getId() .'"'. $liClass .'>';
			$return .= '<a'. $linkClass .' href="'.$nav->getUrl().'">'.htmlspecialchars($nav->getName()).'</a>';

			$depth++;
			if(($this->open || 
			    $nav->getId() == $this->current_category_id || 
			    in_array($nav->getId(),$this->path))
         && ($this->depth > $depth || $this->depth < 0))
			{
				$return .= $this->_getNavigation($nav->getId(),$ignore_offlines);
			}
			$depth--;

			$return .= '</li>';
		}

		if(count($nav_obj)>0)
	  	$return .= '</ul>';

		return $return;
	}
}