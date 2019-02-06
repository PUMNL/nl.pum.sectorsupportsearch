<?php
/**
 * Custom search to Find Case from Sector Support role
 * PUM Senior Experts
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 30 May 2016
 * @license AGPL-3.0
 */
class CRM_Sectorsupportsearch_Form_Search_FindCase extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  // custom table names needed
  private $_expertDataCustomGroupTable = NULL;
  private $_expertDataCustomGroupId = NULL;

  // custom field column names needed
  private $_expStatusColumn = NULL;

  // properties for clauses, params, searchColumns and likes
  private $_whereClauses = array();
  private $_whereParams = array();
  private $_whereIndex = NULL;

  // properties for valid case types
  private $_validCaseTypes = array();
  private $_validCaseStatus = array();

  // properties for option groups and relationship types
  private $_caseStatusOptionGroupId = NULL;
  private $_caseTypeOptionGroupId = NULL;
  private $_expStatusOptionGroupId = NULL;
  private $_scRelationshipTypeId = NULL;


  /**
   * CRM_Sectorsupportsearch_Form_Search_FindCase constructor.
   * @param $formValues
   */
  function __construct(&$formValues) {
    $this->setRequiredOptionGroups();
    $this->setRequiredRelationshipTypes();
    $this->setRequiredCustomTables();
    $this->setRequiredCustomColumns();
    $this->setValidCaseTypes();

    parent::__construct($formValues);
  }

  /**
   * Prepare a set of search fields
   *
   * @param CRM_Core_Form $form modifiable
   * @return void
   */
  function buildForm(&$form) {
    CRM_Utils_System::setTitle(ts('Find PUM Case(s) for HRM'));

    // search on expert status
    $expertStatusList = $this->getExpertStatusList();
    $form->add('select', 'expert_status_id', ts('Contact Status(es)'), $expertStatusList, FALSE,
      array('id' => 'expert_status_id', 'multiple' => 'multiple', 'title' => ts('- select -'))
    );

    // search on group
    $groupList = $this->getGroupList();
    $form->add('select', 'group_id', ts('Group(s)'), $groupList, FALSE,
      array('id' => 'group_id', 'multiple' => 'multiple', 'title' => ts('- select -'))
    );

    // search on case type
    $form->add('select', 'case_type_id', ts('Case Type(s)'), $this->_validCaseTypes, FALSE,
      array('id' => 'case_type_id', 'multiple' => 'multiple', 'title' => ts('- select -'))
    );

    // search on sector
    $sectorList = $this->getSectorList();
    $form->add('select', 'sector_id', ts('Sector(s)'), $sectorList, FALSE,
      array('id' => 'sector_id', 'multiple' => 'multiple', 'title' => ts('- select -'))
    );

    // search on case start date
    $form->addDate('start_date_from', ts('Case Start Date From'), FALSE, array('formatType' => 'custom'));
    $form->addDate('start_date_to', ts('...to'), FALSE, array('formatType' => 'custom'));
    
    // search on case end date
    $form->addDate('end_date_from', ts('Case End Date From'), FALSE, array('formatType' => 'custom'));
    $form->addDate('end_date_to', ts('...to'), FALSE, array('formatType' => 'custom'));
    
    $form->assign('elements', array('contact_name', 'expertise_status_id', 'group_id', 'case_type_id',
      'sector_id', 'expertise_id', 'sector_coordinator_id', 'start_date_from', 'start_date_to', 'end_date_from',
      'end_date_to'));

    $form->addButtons(array(array('type' => 'refresh', 'name' => ts('Search'), 'isDefault' => TRUE,),));
  }

  /**
   * Method to get the list of sectors
   *
   * @return array
   * @access private
   */
  private function getSectorList() {
    $result = array();
    $sectors = civicrm_api3('Segment', 'Get', array('parent_id' => 'null'));
    foreach ($sectors['values'] as $sectorId => $sector) {
      $result[$sectorId] = $sector['label'];
      if ($sector['is_active'] == 0) {
        $result[$sectorId] .= ts(' (inactive) ');
      }

    }
    asort($result);
    return $result;
  }

  /**
   * Method to get the list of expert status options
   *
   * @return array
   * @access private
   */
  private function getExpertStatusList() {
    $result = array();
    try {
      $expertStatusOptionGroupId = civicrm_api3('CustomField', 'Getvalue', array(
        'custom_group_id' => $this->_expertDataCustomGroupId,
        'name' => 'expert_status',
        'return' => 'option_group_id'));
      $optionValues = civicrm_api3('OptionValue', 'Get', array(
        'option_group_id' => $expertStatusOptionGroupId,
        'is_active' => 1,
        'options' => array('limit' => 9999)));
      foreach ($optionValues['values'] as $optionValue) {
        $result[$optionValue['value']] = $optionValue['label'];
      }
    } catch (CiviCRM_API3_Exception $ex) {}
    asort($result);
    return $result;
  }
  /**
   * Method to get the list of groups
   *
   * @return array
   * @access private
   */
  private function getGroupList() {
    $result = array();
    try {
      $groups = civicrm_api3('Group', 'Get', array('is_active' => 1, 'options' => array('limit' => '9999')));
      foreach ($groups['values'] as $group) {
        $result[$group['id']] = $group['title'];
      }
    } catch (CiviCRM_API3_Exception $ex) {}
    asort($result);
    return $result;
  }

  /**
   * Get a list of displayable columns
   *
   * @return array, keys are printable column headers and values are SQL column names
   */
  function &columns() {
    // return by reference
    $columns = array(
      ts('Case') => 'case_subject',
      ts('Case Type') => 'case_type',
      ts('Start Date') => 'case_start_date',
      ts('End Date') => 'case_end_date',
      ts('Case Status') => 'case_status',
      ts('Case Manager') => 'case_manager',
      ts('Case Client') => 'case_client',
      ts('Client Status') => 'client_status',
      ts('Age') => 'client_age',
      // hidden row elements
      ts('CaseID') => 'case_id',
      ts('Case ClientID') => 'case_client_id'
    );
    return $columns;
  }

  /**
   * Construct a full SQL query which returns one page worth of results
   *
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   * @return string, sql
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    // delegate to $this->sql(), $this->select(), $this->from(), $this->where(), etc.
    return $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
  }

  /**
   * Construct a SQL SELECT clause
   *
   * @return string, sql fragment with SELECT arguments
   */
  function select() {
    return "DISTINCT(cc.id) AS case_id, cc.case_type_id AS case_type_id, ct.label AS case_type, 
      cc.subject AS case_subject, cc.start_date AS case_start_date, cc.end_date AS case_end_date, 
      cc.status_id AS case_status_id, cs.label AS case_status, exp.display_name AS case_client, exp.id AS case_client_id, exp.id AS contact_id,
	    sc.id AS case_manager_id, sc.display_name AS case_manager, exp.birth_date AS client_age, 
	    expdata.".$this->_expStatusColumn." AS client_status";
  }

  /**
   * Construct a SQL FROM clause
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  function from() {
    return "FROM civicrm_case cc
      LEFT JOIN civicrm_option_value cs ON cc.status_id = cs.value AND cs.option_group_id = ".$this->_caseStatusOptionGroupId
      ." LEFT JOIN civicrm_option_value ct ON cc.case_type_id = ct.value AND ct.option_group_id = ".$this->_caseTypeOptionGroupId
      ." LEFT JOIN civicrm_case_contact cascon ON cascon.case_id = cc.id
      LEFT JOIN civicrm_contact exp ON cascon.contact_id = exp.id
      LEFT JOIN civicrm_relationship rel ON rel.case_id = cc.id AND rel.relationship_type_id = ".$this->_scRelationshipTypeId
      ." LEFT JOIN civicrm_contact sc ON rel.contact_id_b = sc.id
      LEFT JOIN ".$this->_expertDataCustomGroupTable." expdata ON expdata.entity_id = exp.id
      LEFT JOIN pum_expert_main_sector main ON exp.id = main.contact_id
      LEFT JOIN pum_expert_other_sector other ON exp.id = other.contact_id
      LEFT JOIN civicrm_group_contact gc ON exp.id = gc.contact_id AND gc.status = 'Added'";
  }

  /**
   * Construct a SQL WHERE clause
   *
   * @param bool $includeContactIDs
   * @return string, sql fragment with conditional expressions
   */
  function where($includeContactIDs = FALSE) {
    $this->_whereClauses = array();
    $this->_whereParams = array();
    // basic where clauses that always apply: contact is expert and not deceased
    $this->addInitialWhereClauses();
    // sector clauses if selected
    $this->addSectorWhereClauses();
    // case types clauses if selected
    $this->addCaseTypesWhereClauses();
    // groups clauses if selected
    $this->addGroupsWhereClauses();
    // start date clauses if selected
    $this->addStartDateWhereClauses();
    // end date clauses if selected
    $this->addEndDateWhereClauses();
    // expert status clauses if selected
    $this->addExpertStatusWhereClauses();

    if (!empty($this->_whereClauses)) {
      $where = implode(' AND ', $this->_whereClauses);
    }
    return $this->whereClause($where, $this->_whereParams);
  }

  /**
   * Method to set the expert status where clauses
   */
  private function addExpertStatusWhereClauses() {
    if (isset($this->_formValues['expert_status_id'])) {
      $statusIds = array();
      foreach ($this->_formValues['expert_status_id'] as $statusId) {
        $this->_whereIndex++;
        $statusIds[$this->_whereIndex] = $statusId;
        $this->_whereParams[$this->_whereIndex] = array($statusId, 'String');
      }
      if (!empty($statusIds)) {
        $this->_whereClauses[] = '(expdata.'.$this->_expStatusColumn.' IN("'.implode('", "', $statusIds).'"))';
      }
    }
  }

  /**
   * Method to add the group where clauses
   */
  private function addGroupsWhereClauses() {
    if (isset($this->_formValues['group_id'])) {
      $groupIds = array();
      foreach ($this->_formValues['group_id'] as $groupId) {
        $this->_whereIndex++;
        $groupIds[$this->_whereIndex] = $groupId;
        $this->_whereParams[$this->_whereIndex] = array($groupId, 'Integer');
      }
      if (!empty($groupIds)) {
        $this->_whereClauses[] = '(gc.group_id IN('.implode(', ', $groupIds).'))';
      }
    }
  }

  /**
   * Method to set the start date where clauses
   */
  private function addStartDateWhereClauses() {
    if (isset($this->_formValues['start_date_from']) || isset($this->_formValues['start_date_to'])) {
      $this->setDateRangeClauses('start_date', 'cc.start_date');
    }
  }

  /**
   * Method to set the end date where clauses
   */
  private function addEndDateWhereClauses() {
    if (isset($this->_formValues['end_date_from']) || isset($this->_formValues['end_date_to'])) {
      $this->setDateRangeClauses('end_date', 'cc.end_date');
    }
  }

  /**
   * Method to set date range clauses
   *
   * @param $fieldName
   * @param $columnName
   */
  private function setDateRangeClauses($fieldName, $columnName) {
    if (isset($this->_formValues[$fieldName.'_from']) && !empty($this->_formValues[$fieldName.'_from'])) {
      $fromDate = new DateTime($this->_formValues[$fieldName.'_from']);
      $this->_whereIndex++;
      $fromIndex = $this->_whereIndex;
      $this->_whereParams[$fromIndex] = array($fromDate->format('Y-m-d'), 'String');
    }
    if (isset($this->_formValues[$fieldName.'_to']) && !empty($this->_formValues[$fieldName.'_to'])) {
      $toDate = new DateTime($this->_formValues[$fieldName.'_to']);
      $this->_whereIndex++;
      $toIndex = $this->_whereIndex;
      $this->_whereParams[$toIndex] = array($toDate->format('Y-m-d'), 'String');
    }
    if (isset($fromIndex) && isset($toIndex)) {
      $this->_whereClauses[] = '('.$columnName.' BETWEEN %'.$fromIndex.' AND %'.$toIndex.')';
    } else {
      if (isset($fromIndex)) {
        $this->_whereClauses[] = '('.$columnName.' >= %'.$fromIndex.')';
      }
      if (isset($toIndex)) {
        $this->_whereClauses[] = '('.$columnName.' <= %'.$toIndex.')';
      }
    }
  }

  /**
   * Method to add the case types where clauses
   */
  private function addCaseTypesWhereClauses() {
    if (isset($this->_formValues['case_type_id'])) {
      if (empty($this->_formValues['case_type_id'])) {
        foreach ($this->_validCaseTypes as $caseTypeId => $caseTypeLabel) {
          $this->_whereIndex++;
          $caseTypeClauses[] = 'cc.case_type_id LIKE %'.$this->_whereIndex;
          $this->_whereParams[$this->_whereIndex] = array('%'.$caseTypeId.'%', 'String');
        }
      } else {
        $caseTypeClauses = array();
        foreach ($this->_formValues['case_type_id'] as $caseTypeId) {
          $this->_whereIndex++;
          $caseTypeClauses[] = 'cc.case_type_id LIKE %' . $this->_whereIndex;
          $this->_whereParams[$this->_whereIndex] = array('%' . $caseTypeId . '%', 'String');
        }
      }
      if (!empty($caseTypeClauses)) {
        $this->_whereClauses[] = '(' . implode(' OR ', $caseTypeClauses) . ')';
      }
    }
  }

  /**
   * Method to add the sector where clauses
   */
  private function addSectorWhereClauses() {
    if (isset($this->_formValues['sector_id'])) {
      $sectorIds = array();
      foreach ($this->_formValues['sector_id'] as $sectorId) {
        $this->_whereIndex++;
        $sectorIds[$this->_whereIndex] = $sectorId;
        $this->_whereParams[$this->_whereIndex] = array($sectorId, 'Integer');
      }
      if (!empty($sectorIds)) {
        $this->_whereClauses[] = '(main.segment_id IN('.implode(', ', $sectorIds).') OR other.segment_id IN('.implode(', ', $sectorIds).'))';
      }
    }
  }

  /**
   * Determine the Smarty template for the search screen
   *
   * @return string, template path (findable through Smarty template path)
   */
  function templateFile() {
    return 'CRM/Sectorsupportsearch/FindCase.tpl';
  }

  /**
   * Modify the content of each row
   *
   * @param array $row modifiable SQL result row
   * @throws exception if function getOptionGroup not found
   * @return void
   */
  function alterRow(&$row) {
    $row['client_age'] = $this->calculateContactAge($row['case_client_id']);
  }

  /**
   * Method to calculate contact age
   * @param $contactId
   * @return bool|int
   * @throws CiviCRM_API3_Exception
   */
  private function calculateContactAge($contactId) {
    try {
      $birthDate = civicrm_api3('Contact', 'Getvalue', array('id' => $contactId, 'return' => 'birth_date'));
      if (!empty($birthDate)) {
        $dateOfBirth = new DateTime($birthDate);
        $age = $dateOfBirth->diff(new DateTime('now'));
        return $age->y;
      }
    } catch (CiviCRM_API3_Exception $ex) {}
    return "";
  }

  /**
   * Method to set the table names of the required custom groups
   *
   * @return void
   */
  private function setRequiredCustomTables() {
    // define custom table names required
    $customGroups = array(
      array('name' => 'expert_data', 'property' => '_expertData'),
    );
    foreach ($customGroups as $customGroupData) {
      try {
        $apiData = civicrm_api3('CustomGroup', 'Getsingle', array('name' => $customGroupData['name']));
        $propertyTableLabel = $customGroupData['property'].'CustomGroupTable';
        $propertyIdLabel = $customGroupData['property'].'CustomGroupId';
        $this->$propertyIdLabel = $apiData['id'];
        $this->$propertyTableLabel = $apiData['table_name'];
      } catch (CiviCRM_API3_Exception $ex) {}
    }
  }

  /**
   * Method to set the column names required
   *
   * @return void
   */
  private function setRequiredCustomColumns() {

    // required columns from expert_data
    $customField = civicrm_api3('CustomField', 'Getsingle', array(
      'custom_group_id' => $this->_expertDataCustomGroupId, 'name' => 'expert_status'));
    if (!empty($customField)) {
      $this->_expStatusColumn = $customField['column_name'];
      $this->_expStatusOptionGroupId = $customField['option_group_id'];
    }
  }

  /**
   * Method to set the initial where clauses that apply to each instance
   */
  private function addInitialWhereClauses() {
    $this->_whereClauses[] = '(exp.contact_sub_type LIKE %1)';
    $this->_whereParams[1] = array('%Expert%', 'String');
    $this->_whereIndex = 1;
  }

  /**
   * Method to set the valid case types for latest main activity
   *
   * @throws Exception when no option group case type found
   */
  private function setValidCaseTypes() {
    $requiredCaseTypes = array('Expertapplication', 'ExitExpert');
    try {
      $foundCaseTypes = civicrm_api3('OptionValue', 'Get', array('option_group_id' => $this->_caseTypeOptionGroupId, 'is_active' => 1));
      foreach ($foundCaseTypes['values'] as $caseType) {
        if (in_array($caseType['name'], $requiredCaseTypes)) {
          $this->_validCaseTypes[$caseType['value']] = $caseType['name'];
        }
      }
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find option group for case type in '.__METHOD__.', error from API OptionGroup Getvalue: '.$ex->getMessage());
    }
  }

  /**
   * Method to set the required option group ids
   */
  private function setRequiredOptionGroups() {
    $this->_caseStatusOptionGroupId = civicrm_api3('OptionGroup', 'Getvalue', array('name' => 'case_status', 'return' => 'id'));
    $this->_caseTypeOptionGroupId = civicrm_api3('OptionGroup', 'Getvalue', array('name' => 'case_type', 'return' => 'id'));
  }

  /**
   * Method to set the required relationship type ids
   */
  private function setRequiredRelationshipTypes() {
    $this->_scRelationshipTypeId = civicrm_api3('RelationshipType', 'Getvalue',
      array('name_a_b' => 'Sector Coordinator', 'return' => 'id'));
  }

  /**
   * Method to count selected cases
   *
   * @return string
   */
  function count() {
    return CRM_Core_DAO::singleValueQuery($this->sql('COUNT(DISTINCT cc.id) as total'));
  }
}
