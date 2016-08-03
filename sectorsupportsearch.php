<?php

require_once 'sectorsupportsearch.civix.php';

/**
 * Implments hook_civicrm_validateForm().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_validateForm
 *
 */
function sectorsupportsearch_civicrm_validateForm( $formName, &$fields, &$files, &$form, &$errors ) {
  if ($formName == 'CRM_Contact_Form_Search_Custom') {
    $customClass = $form->getVar('_customClass');
    $className = get_class($customClass);
    switch ($className) {
      case "CRM_Sectorsupportsearch_Form_Search_FindExpert":
        CRM_Sectorsupportsearch_FindExpert::validateForm($fields, $errors);
        break;
      case "CRM_Sectorsupportsearch_Form_Search_FindCase":
        CRM_Sectorsupportsearch_FindCase::validateForm($fields, $errors);
        break;
    }
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function sectorsupportsearch_civicrm_config(&$config) {
  _sectorsupportsearch_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param array $files
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function sectorsupportsearch_civicrm_xmlMenu(&$files) {
  _sectorsupportsearch_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function sectorsupportsearch_civicrm_install() {
  _sectorsupportsearch_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function sectorsupportsearch_civicrm_uninstall() {
  _sectorsupportsearch_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function sectorsupportsearch_civicrm_enable() {
  _sectorsupportsearch_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function sectorsupportsearch_civicrm_disable() {
  _sectorsupportsearch_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function sectorsupportsearch_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _sectorsupportsearch_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function sectorsupportsearch_civicrm_managed(&$entities) {
  _sectorsupportsearch_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * @param array $caseTypes
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function sectorsupportsearch_civicrm_caseTypes(&$caseTypes) {
  _sectorsupportsearch_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function sectorsupportsearch_civicrm_angularModules(&$angularModules) {
_sectorsupportsearch_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function sectorsupportsearch_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _sectorsupportsearch_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Functions below this ship commented out. Uncomment as required.
 *

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function sectorsupportsearch_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function sectorsupportsearch_civicrm_navigationMenu(&$params) {
  $maxKey = CRM_Sectorsupportsearch_Utils::getMaxMenuKey($params);
  //NOTE: For some reason the name and label is switched in Civi Core, label = 'Search', name = 'Search...'
  $menuSearchId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Search...', 'id', 'name');
  
  //Retrieve ID of custom search 'Find Case'
  $cs_pumcaseshrm_params = array(
    'version' => 3,
    'sequential' => 1,
    'name' => 'CRM_Sectorsupportsearch_Form_Search_FindCase',
  );
  $result_cs_pumcaseshrm = civicrm_api('CustomSearch', 'get', $cs_pumcaseshrm_params);
  
  if(isset($result_cs_pumcaseshrm['values'][0]['value'])) {
    _sectorsupportsearch_civix_insert_navigation_menu($params, 'Search...', array(
      'label' => ts('Find PUM Cases for HRM', array('domain' => 'nl.pum.sectorsupportsearch')),
      'name' => 'Find PUM Cases for HRM',
      'url' => 'civicrm/contact/search/custom?csid='.$result_cs_pumcaseshrm['values'][0]['value'].'&reset=1',
      'permission' => 'access CiviReport,access CiviContribute',
      'operator' => 'OR',
      'separator' => 0,
      'parentID' => $menuSearchId,
      'navID' => $maxKey+1,
      'is_active' => '1',
    ));
  }
  
  //Retrieve ID of custom search 'Find Expert'
  $cs_pumcontactshrm_params = array(
    'version' => 3,
    'sequential' => 1,
    'name' => 'CRM_Sectorsupportsearch_Form_Search_FindExpert',
  );
  $result_cs_pumcontactshrm = civicrm_api('CustomSearch', 'get', $cs_pumcontactshrm_params);
  
  if(isset($result_cs_pumcontactshrm['values'][0]['value'])) {
    _sectorsupportsearch_civix_insert_navigation_menu($params, 'Search...', array(
      'label' => ts('Find experts by age and status', array('domain' => 'nl.pum.sectorsupportsearch')),
      'name' => 'Find PUM Contact for HRM',
      'url' => 'civicrm/contact/search/custom?csid='.$result_cs_pumcontactshrm['values'][0]['value'].'&reset=1',
      'permission' => 'access CiviReport,access CiviContribute',
      'operator' => 'OR',
      'separator' => 0,
      'parentID' => $menuSearchId,
      'navID' => $maxKey+1,
      'is_active' => '1',
    ));
  }
  _sectorsupportsearch_civix_navigationMenu($params);
}
