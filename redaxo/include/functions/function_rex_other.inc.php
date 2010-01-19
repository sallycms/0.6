<?php

/**
 * Funktionen zur Ausgabe der Titel Leiste und Subnavigation
 * @package redaxo4
 * @version svn:$Id$
 */

/**
 * Funktion zum Anlegen eines Sprache-Objekts
 * 
 * @param $locale Locale der Sprache
 * @param $searchpath Pfad zum Ordner indem die Sprachdatei gesucht werden soll
 * @param $setlocale TRUE, wenn die locale f�r die Umgebung gesetzt werden soll, sonst FALSE
 * @return unknown_type
 */
function rex_create_lang($locale = "de_de", $searchpath = '', $setlocale = TRUE)
{
  global $REX;

  $_searchpath = $searchpath;

  if ($searchpath == '')
  {
    $searchpath = $REX['INCLUDE_PATH'] .DIRECTORY_SEPARATOR. "lang";
  }
  $lang_object = new i18n($locale, $searchpath);

  if ($_searchpath == '')
  {
    $REX['LOCALES'] = $lang_object->getLocales($searchpath);
  }

  if($setlocale)
  {
    $locales = array();
    foreach(explode(',', trim($lang_object->msg('setlocale'))) as $locale)
    {
      $locales[]= $locale .'.'. strtoupper(str_replace('iso-', 'iso', $lang_object->msg('htmlcharset')));
      $locales[]= $locale .'.'. strtoupper(str_replace('iso-', 'iso', str_replace("-","",$lang_object->msg('htmlcharset'))));
      $locales[]= $locale .'.'. strtolower(str_replace('iso-', 'iso', $lang_object->msg('htmlcharset')));
      $locales[]= $locale .'.'. strtolower(str_replace('iso-', 'iso', str_replace("-","",$lang_object->msg('htmlcharset'))));
    }
    
    foreach(explode(',', trim($lang_object->msg('setlocale'))) as $locale)
      $locales[]= $locale;
    
    setlocale(LC_ALL, $locales);
  }

  return $lang_object;
}

/**
 * Returns the truncated $string
 *
 * @param $string String Searchstring
 * @param $start String Suffix to search for
 */
function truncate($string, $length = 80, $etc = '...', $break_words = false)
{
  if ($length == 0)
    return '';

  if (strlen($string) > $length)
  {
    $length -= strlen($etc);
    if (!$break_words)
      $string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length +1));

    return substr($string, 0, $length).$etc;
  }
  else
    return $string;
}

/**
 * Berechnet aus einem Relativen Pfad einen Absoluten
 */
function rex_absPath($rel_path, $rel_to_current = false)
{
  $stack = array();
  // Pfad relativ zum aktuellen Verzeichnis?
  // z.b. ../../files
  if($rel_to_current)
  {
    $path = realpath('.');
    $stack = explode(DIRECTORY_SEPARATOR, $path);
  }

  foreach (explode('/', $rel_path) as $dir)
  {
    // Aktuelles Verzeichnis, oder Ordner ohne Namen
    if ($dir == '.' || $dir == '')
      continue;

    // Zum Parent
    if ($dir == '..')
      array_pop($stack);
    // Normaler Ordner
    else
      array_push($stack, $dir);
  }

  return implode('/', $stack);
}

/**
 * Pr�fen ob ein/e Datei/Ordner beschreibbar ist
 *
 * @access public
 * @param string $item Datei oder Verzeichnis
 * @return mixed true bei Erfolg, sonst Fehlermeldung
 */
function rex_is_writable($item)
{
  return _rex_is_writable_info(_rex_is_writable($item), $item);
}

function _rex_is_writable_info($is_writable, $item = '')
{
  global $I18N;

  $state = true;
  $key = '';
  switch($is_writable)
  {
    case 1:
    {
      $key = 'setup_012';
      break;
    }
    case 2:
    {
      $key = 'setup_014';
      break;
    }
    case 3:
    {
      $key = 'setup_015';
      break;
    }
  }

  if($key != '')
  {
    $file = '';
    if($item != '')
      $file = '<b>'. $item .'</b>';

    $state = $I18N->msg($key, '<span class="rex-error">', '</span>', rex_absPath($file));
  }

  return $state;
}

function _rex_is_writable($item)
{
  // Fehler unterdr�cken, falls keine Berechtigung
  if (@ is_dir($item))
  {
    if (!@ is_writable($item . '/.'))
    {
      return 1;
    }
  }
  // Fehler unterdr�cken, falls keine Berechtigung
  elseif (@ is_file($item))
  {
    if (!@ is_writable($item))
    {
      return 2;
    }
  }
  else
  {
    return 3;
  }

  return 0;
}

function rex_getAttributes($name,$content,$default = null)
{
  $prop = unserialize($content);
  if (isset($prop[$name])) return $prop[$name];
  return $default;
}

function rex_setAttributes($name,$value,$content)
{
  $prop = unserialize($content);
  $prop[$name] = $value;
  return serialize($prop);
}

/**
 * Gibt den n�chsten freien Tabindex zur�ck.
 * Der Tabindex ist eine stetig fortlaufende Zahl,
 * welche die Priorit�t der Tabulatorspr�nge des Browsers regelt.
 *
 * @return integer n�chster freier Tabindex
 */
function rex_tabindex($html = true)
{
  global $REX;

  if (empty($REX['TABINDEX']))
  {
    $REX['TABINDEX'] = 0;
  }

  if($html === true)
  {
    return ' tabindex="'. ++$REX['TABINDEX'] .'"';
  }
  return ++$REX['TABINDEX'];
}


function array_insert($array, $index, $value)
{
  // In PHP5 akzeptiert array_merge nur arrays. Deshalb hier $value als Array verpacken
  return array_merge(array_slice($array, 0, $index), array($value), array_slice($array, $index));
}

function rex_message($message, $cssClass, $sorround_tag)
{
  $return = '';
  
  $return = '<div class="rex-message"><'. $sorround_tag .' class="'. $cssClass .'">';
  
  if ($sorround_tag != 'p')
    $return .= '<p>';
    
  $return .= '<span>'. $message .'</span>';
  
  if ($sorround_tag != 'p')
    $return .= '</p>';
    
  $return .= '</'. $sorround_tag .'></div>';
  
  return $return;
}

function rex_info($message, $cssClass = null, $sorround_tag = null)
{
  if(!$cssClass) $cssClass = 'rex-info';
  if(!$sorround_tag) $sorround_tag = 'div';
  return rex_message($message, $cssClass, $sorround_tag);
}

function rex_warning($message, $cssClass = null, $sorround_tag = null)
{
  if(!$cssClass) $cssClass = 'rex-warning';
  if(!$sorround_tag) $sorround_tag = 'div';
  return rex_message($message, $cssClass, $sorround_tag);
}

function rex_info_block($message, $cssClass = null, $sorround_tag = null)
{
  if(!$cssClass) $cssClass = 'rex-info-block';
  if(!$sorround_tag) $sorround_tag = 'div';
  return rex_message_block($message, $cssClass, $sorround_tag);
}

function rex_warning_block($message, $cssClass = null, $sorround_tag = null)
{
  if(!$cssClass) $cssClass = 'rex-warning-block';
  if(!$sorround_tag) $sorround_tag = 'div';
  return rex_message_block($message, $cssClass, $sorround_tag);
}

function rex_message_block($message, $cssClass, $sorround_tag)
{
  $return = '';
  
  $return = '<div class="rex-message-block"><'. $sorround_tag .' class="'. $cssClass .'">';
  
  $return .= '<div class="rex-message-content">';
    
  $return .= $message;
  
  $return .= '</div>';
    
  $return .= '</'. $sorround_tag .'></div>';
  
  return $return;
}

function rex_accesskey($title, $key)
{
  global $REX;

  if($REX['USER']->hasPerm('accesskeys[]'))
    return ' accesskey="'. $key .'" title="'. $title .' ['. $key .']"';

  return ' title="'. $title .'"';
}

function rex_ini_get($val)
{
  $val = trim(ini_get($val));
  if ($val != '') {
    $last = strtolower($val{strlen($val)-1});
  } else {
    $last = '';
  }
  switch($last) {
      // The 'G' modifier is available since PHP 5.1.0
      case 'g':
          $val *= 1024;
      case 'm':
          $val *= 1024;
      case 'k':
          $val *= 1024;
  }

  return $val;
}

/**
 * �bersetzt den text $text, falls dieser mit dem prefix "translate:" beginnt.
 */
function rex_translate($text, $I18N_Catalogue = null, $use_htmlspecialchars = true)
{
  if(!$I18N_Catalogue)
  {
    global $REX, $I18N;

    if(!$I18N)
      $I18N = rex_create_lang($REX['LANG']);
      
    if(!$I18N)
      trigger_error('Unable to create language "'. $REX['LANG'] .'"', E_USER_ERROR);

    return rex_translate($text, $I18N, $use_htmlspecialchars);
  }

  $tranKey = 'translate:';
  $transKeyLen = strlen($tranKey);
  if(substr($text, 0, $transKeyLen) == $tranKey)
  {
    $text = $I18N_Catalogue->msg(substr($text, $transKeyLen));
  }

  if($use_htmlspecialchars)
    return htmlspecialchars($text);

  return $text;
}

/**
 * Leitet auf einen anderen Artikel weiter
 */
function rex_redirect($article_id, $clang = '', $params = array())
{
  global $REX;

  // Alle OBs schlie�en
  while(@ob_end_clean());

  $divider = '&';

  header('Location: '. rex_getUrl($article_id, $clang, $params, $divider));
  exit();
}

/**
 * Trennt einen String an Leerzeichen auf.
 * Dabei wird beachtet, dass Strings in " zusammengeh�ren
 */
function rex_split_string($string)
{
  $spacer = '@@@REX_SPACER@@@';
  $result = array();

  // TODO mehrfachspaces hintereinander durch einfachen ersetzen
  $string = ' ' . trim($string) . ' ';

  // Strings mit Quotes heraussuchen
  $pattern = '!(["\'])(.*)\\1!U';
  preg_match_all($pattern, $string, $matches);
  $quoted = isset ($matches[2]) ? $matches[2] : array();

  // Strings mit Quotes maskieren
  $string = preg_replace($pattern, $spacer, $string);

  // ----------- z.b. 4 "av c" 'de f' ghi
  if (strpos($string, '=') === false)
  {
    $parts = explode(' ', $string);
    foreach ($parts as $part)
    {
      if (empty ($part))
        continue;

      if ($part == $spacer)
      {
        $result[] = array_shift($quoted);
      }
      else
      {
        $result[] = $part;
      }
    }
  }
  // ------------ z.b. a=4 b="av c" y='de f' z=ghi
  else
  {
    $parts = explode(' ', $string);
    foreach ($parts as $part)
    {
      if(empty($part))
        continue;

      $variable = explode('=', $part);

      if (empty ($variable[0]) || empty ($variable[1]))
        continue;

      $var_name = $variable[0];
      $var_value = $variable[1];
      
      if ($var_value == $spacer)
      {
        $var_value = array_shift($quoted);
      }

      $result[$var_name] = $var_value;
    }
  }
  return $result;
}

function rex_put_file_contents($path, $content)
{
  global $REX;

  $writtenBytes = file_put_contents($path, $content);
  @ chmod($path, $REX['FILEPERM']);

  return $writtenBytes;
}

function rex_get_file_contents($path)
{
  return file_get_contents($path);
}

function rex_replace_dynamic_contents($path, $content)
{
  if($fcontent = rex_get_file_contents($path))
  {
    $content = "// --- DYN\n". trim($content) ."\n// --- /DYN";
    $fcontent = preg_replace("#(//\s---\sDYN.*//\s---\s/DYN)#s", $content, $fcontent);
    return rex_put_file_contents($path, $fcontent);
  }
  return false;
}

/**
 * Allgemeine funktion die eine Datenbankspalte fortlaufend durchnummeriert.
 * Dies ist z.B. n�tzlich beim Umgang mit einer Priorit�ts-Spalte
 */
function rex_organize_priorities($tableName, $priorColumnName, $whereCondition = '', $orderBy = '', $id_field='id')
{
  $gu = new rex_sql;
  $qry = 'select * from '.$tableName;
  if($whereCondition != '')
    $qry .= ' WHERE '. $whereCondition;
  if($orderBy != '')
    $qry .= ' ORDER BY '. $orderBy;
  $gr = new rex_sql;
  $gr->setQuery($qry);
  for ($i = 0; $i < $gr->getRows(); $i ++)
  {
      $gu->setQuery('update '.$tableName.' set '.$priorColumnName.'='.($i+1).' where '.$id_field.'='.$gr->getValue($id_field));
      $gr->next();
  }
	return;
}

function rex_lang_is_utf8()
{
  global $REX;
  return strpos($REX['LANG'], 'utf8') !== false;
}

// ------------------------------------- Allgemeine PHP Functions

/* PHP5 Functions */

if (!function_exists("htmlspecialchars_decode"))
{
  function htmlspecialchars_decode($string, $quote_style = ENT_COMPAT) {
    return strtr($string, array_flip(get_html_translation_table(HTML_SPECIALCHARS, $quote_style)));
  }
}

if (!function_exists('file_put_contents'))
{
  function file_put_contents($path, $content)
  {
    $fp = @fopen($path, 'wb');
    if ($fp)
    {
      $writtenBytes = fwrite($fp, $content, strlen($content));

      if(fclose($fp))
        return $writtenBytes;
    }
    return false;
  }
}

function rex_highlight_string($string, $return = false)
{
  $s = '<p class="rex-code">'. highlight_string($string, true) .'</p>';
  if($return)
  {
    return $s;
  }
  echo $s;  
}

function rex_highlight_file($filename, $return = false)
{
  $s = '<p class="rex-code">'. highlight_file($filename, true) .'</p>';
  if($return)
  {
    return $s;
  }
  echo $s;  
}

function rex_exception(Exception $e){
	return rex_warning('Error in '.$e->getFile().' Line: '.$e->getLine().'<br />'.$e->getMessage());
}
