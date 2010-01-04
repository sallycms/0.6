<?php

/**
 * Klasse zum pr�fen ob Addons installiert/aktiviert sind
 * @package redaxo4
 * @version svn:$Id$
 */

class OOAddon extends rex_addon
{
  /*
   * Pr�ft, ob ein System-Addon vorliegt
   * 
   * @param string $addon Name des Addons
   * 
   * @return boolean TRUE, wenn es sich um ein System-Addon handelt, sonst FALSE
   */
  public static function isSystemAddon($addon)
  {
    global $REX;
    return in_array($addon, $REX['SYSTEM_ADDONS']);
  }

  /**
   * Gibt ein Array von verf�gbaren Addons zur�ck.
   * 
   * @return array Array der verf�gbaren Addons
   */
  public static function getAvailableAddons()
  {
    $avail = array();
    foreach(OOAddon::getRegisteredAddons() as $addonName)
    {
      if(OOAddon::isAvailable($addonName))
        $avail[] = $addonName;
    }

    return $avail;
  }
  
  /**
   * Gibt ein Array aller registrierten Addons zur�ck.
   * Ein Addon ist registriert, wenn es dem System bekannt ist (addons.inc.php).
   * 
   * @return array Array aller registrierten Addons
   */
  public static function getRegisteredAddons()
  {
    global $REX;
    
    $addons = array();
    if(isset($REX['ADDON']) && is_array($REX['ADDON']) &&
       isset($REX['ADDON']['install']) && is_array($REX['ADDON']['install']))
    {
      $addons = array_keys($REX['ADDON']['install']);
    }
    
    return $addons;
  }
}