<?php
/**
 * Class with general static util functions for extension
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @license AGPL-V3.0
 */
class CRM_Sectorsupportsearch_Utils {
  /**
   * Method to determine max key in navigation menu (core solutions do not cater for child keys!)
   *
   * @param array $menuItems
   * @return int $maxKey
   */
  public static function getMaxMenuKey($menuItems) {
    $maxKey = 0;
    if (is_array($menuItems)) {
      foreach ($menuItems as $menuKey => $menuItem) {
        if ($menuKey > $maxKey) {
          $maxKey = $menuKey;
        }
        if (isset($menuItem['child'])) {
          foreach ($menuItem['child'] as $childKey => $child) {
            if ($childKey > $maxKey) {
              $maxKey = $childKey;
            }
          }
        }
      }
    }
    return $maxKey;
  }
}