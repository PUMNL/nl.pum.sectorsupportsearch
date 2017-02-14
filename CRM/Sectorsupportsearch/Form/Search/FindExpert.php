<?php
/**
 * Custom search to Find Expert from Sector Support role
 * PUM Senior Experts
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 24 May 2016
 * @license AGPL-3.0
 */
class CRM_Sectorsupportsearch_Form_Search_FindExpert extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  // select list that need to be at class level
  private $_languagesWithLevelList = array();
  private $_genericSkillsList = array();

  // custom table names needed
  private $_expertDataCustomGroupTable = NULL;
  private $_languageCustomGroupTable = NULL;
  private $_expertDataCustomGroupId = NULL;
  private $_languageCustomGroupId = NULL;

  // custom field column names needed
  private $_llLanguagesColumn = NULL;
  private $_llLevelColumn = NULL;
  private $_expStatusColumn = NULL;
  private $_expGenericColumn = NULL;
  private $_expStatusStartDateColumn = NULL;
  private $_expStatusEndDateColumn = NULL;
  private $_expCvMutationColumn = NULL;

  // properties for clauses, params, searchColumns and likes
  private $_whereClauses = array();
  private $_whereParams = array();
  private $_whereIndex = NULL;

  // property for restriction activity type id
  private $_restrictionsActivityTypeId = NULL;
  private $_scheduledActivityStatusValue = NULL;

  // properties for valid case types and case status for latest main activity
  private $_validCaseTypes = array();
  private $_validCaseStatus = array();

  /**
   * CRM_Sectorsupportsearch_Form_Search_FindExpert constructor.
   * @param $formValues
   */
  function __construct(&$formValues) {
    $this->setLanguagesWithLevels();
    $this->getGenericSkillsList();
    $this->setRequiredCustomTables();
    $this->setRequiredCustomColumns();
    $this->setActivityTypes();
    $this->setActivityStatus();
    $this->setValidCaseStatus();
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
    CRM_Utils_System::setTitle(ts('Find experts by age and status'));

    // search on sector
    $sectorList = $this->getSectorList();
    $form->add('select', 'sector_id', ts('Sector(s)'), $sectorList, FALSE,
      array('id' => 'sector_id', 'multiple' => 'multiple', 'title' => ts('- select -'))
    );

    // search on area of expertise
    $areasOfExpertiseList = $this->getAreasOfExpertiseList();
    $form->add('select', 'expertise_id', ts('Areas(s) of Expertise'), $areasOfExpertiseList, FALSE,
      array('id' => 'expertise_id', 'multiple' => 'multiple', 'title' => ts('- select -'))
    );

    // search on general skills
    $form->add('select', 'generic_id', ts('Generic Skill(s)'), $this->_genericSkillsList, FALSE,
      array('id' => 'generic_id', 'multiple' => 'multiple', 'title' => ts('- select -'))
    );

    // search on language
    $languageList = $this->getLanguageList();
    $form->add('select', 'language_id', ts('Language(s)'), $languageList, FALSE,
      array('id' => 'language_id', 'multiple' => 'multiple', 'title' => ts('- select -'))
    );

    // search on age from .... to
    $form->addDate('birth_date_from', ts('Birth Date From'), FALSE, array('formatType' => 'birth'));
    $form->addDate('birth_date_to', ts('...to'), FALSE, array('formatType' => 'birth'));

    // search on deceased and deceased date range
    $deceasedList = array(
      1 => ts('Only contacts that are not deceased'),
      2 => ts('All contacts'),
      3 => ts('Only deceased contacts'),
    );
    $form->addRadio('deceased_id', ts('Deceased?'), $deceasedList, NULL, '<br />', TRUE);
    $defaults['deceased_id'] = 1;
    $form->addDate('deceased_date_from', ts('Deceased Date From'), FALSE, array('formatType' => 'birth'));
    $form->addDate('deceased_date_to', ts('...to'), FALSE, array('formatType' => 'birth'));

    // search on gender
    $genderList = $this->getGenderList();
    $form->add('select', 'gender_id', ts('Gender(s)'), $genderList, FALSE,
      array('id' => 'gender_id', 'multiple' => 'multiple', 'title' => ts('- select -'))
    );

    // search on group
    $groupList = $this->getGroupList();
    $form->add('select', 'group_id', ts('Group(s)'), $groupList, FALSE,
      array('id' => 'group_id', 'multiple' => 'multiple', 'title' => ts('- select -'))
    );

    // search on expert status
    $expertStatusList = $this->getExpertStatusList();
    $form->add('select', 'expert_status_id', ts('Expert Status(es)'), $expertStatusList, FALSE,
      array('id' => 'expert_status_id', 'multiple' => 'multiple', 'title' => ts('- select -'))
    );

    // search on CV Mutation
    $cvMutationList = array(
      1 => ts('Only contacts with CV In Mutation YES'),
      2 => ts('Only contacts with CV In Mutation NO and clear'),
      3 => ts('All contacts')
    );
    $form->addRadio('cv_mutation_id', ts('CV in Mutation?'), $cvMutationList, NULL, '<br />', TRUE);
    $defaults['cv_mutation_id'] = 3;

    $form->setDefaults($defaults);

    $form->assign('elements', array('sector_id', 'expertise_id', 'generic_id', 'language_id',
      'birth_date_from', 'birth_date_to', 'deceased_id', 'deceased_date_from', 'deceased_date_to', 'gender_id',
      'group_id', 'expert_status_id', 'expert_status_date_from', 'expert_status_date_to', 'cv_mutation_id'));

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
   * Method to get the list of genders
   *
   * @return array
   * @access private
   */
  private function getGenderList() {
    $result = array();
    try {
      $optionValues = civicrm_api3('OptionValue', 'Get', array('option_group_id' => 'gender'));
      foreach ($optionValues['values'] as $optionValue) {
        $result[$optionValue['value']] = $optionValue['label'];
      }
    } catch (CiviCRM_API3_Exception $ex) {}
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
   * Method to get list of areas of expertise. Initially all, jQuery in tpl will
   * determine what will be available based on selected sectors
   *
   * @return array
   * @access private
   */
  private function getAreasOfExpertiseList() {
    $result = array();
    $areas = civicrm_api3('Segment', 'Get', array());
    foreach ($areas['values'] as $areaId => $area) {
      if (!empty($area['parent_id'])) {
        $result[$areaId] = $area['label'];
        if ($area['is_active'] == 0) {
          $result[$areaId] .= ts(' (inactive) ');
        }
      }
    }
    asort($result);
    return $result;
  }

  /**
   * Method to get generic skills
   *
   * @return void
   * @throws Exception when option group not found
   */
  private function getGenericSkillsList() {
    $genericSkillsParams = array('name' => 'generic_skilss_20140825142210', 'return' => 'id');
    try {
      $genericSkillsOptionGroupId = civicrm_api3('OptionGroup', 'Getvalue', $genericSkillsParams);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find an option group for generic skills with name
      generic_skilss_20140825142210 in extension nl.pum.findexpert, contact your system administrator.
      Error from API OptionGroup Getvalue: '.$ex->getMessage().' with params '.implode('; ', $genericSkillsParams));
    }
    $this->_genericSkillsList = array();
    try {
      $optionValues = civicrm_api3('OptionValue', 'Get',
        array('option_group_id' => $genericSkillsOptionGroupId, 'is_active' => 1));
      foreach ($optionValues['values'] as $genericSkill) {
        $this->_genericSkillsList[$genericSkill['value']] = $genericSkill['label'];
      }
    } catch (CiviCRM_API3_Exception $ex) {}
    asort($this->_genericSkillsList);
  }


  /**
   * Method to build languages select list with levels
   *
   * @return array
   * @throws Exception when option group not found
   */
  private function getLanguageList() {
    $result = array();
    foreach ($this->_languagesWithLevelList as $languageLevelId => $languageLevel) {
      $result[$languageLevelId] = $languageLevel['language_label'].' ('.$languageLevel['level_label'].')';
    }
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
      ts('Contact Id') => 'contact_id',
      ts('Name') => 'display_name',
      ts('Age') => 'contact_age',
      ts('Main Sector') => 'main_sector',
      ts('Expert Status') => 'expert_status',
      //ts('Expert Status Date From - To') => 'expert_status_date_range',
      ts('Has Restrictions') => 'restrictions'
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
    return "DISTINCT(contact_a.id) AS contact_id, contact_a.display_name AS display_name, 
    main.main_sector, exp.".$this->_expStatusColumn." AS expert_status, '' AS restrictions, '' as contact_age,
    '' as expert_status_date_range, contact_a.gender_id AS gender_id";
  }

  /**
   * Construct a SQL FROM clause
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  function from() {
    return "FROM civicrm_contact contact_a
    LEFT JOIN pum_expert_main_sector main ON contact_a.id = main.contact_id
    LEFT JOIN pum_expert_other_sector other ON contact_a.id = other.contact_id
    LEFT JOIN pum_expert_areas_expertise areas ON contact_a.id = areas.contact_id
    LEFT JOIN ".$this->_expertDataCustomGroupTable." exp ON contact_a.id = exp.entity_id
    LEFT JOIN ".$this->_languageCustomGroupTable." ll ON contact_a.id = ll.entity_id
    LEFT JOIN civicrm_group_contact gc ON contact_a.id = gc.contact_id AND gc.status = 'Added'";
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
    // area of expertise clauses if selected
    $this->addExpertiseWhereClauses();
    // generic skills clauses if selected
    $this->addGenericSkillsWhereClauses();
    // language and level clauses if selected
    $this->addLanguageLevelWhereClauses();
    // gender clause if selected
    $this->addGenderWhereClauses();
    // groups clauses if selected
    $this->addGroupsWhereClauses();
    // birth date range clauses if selected
    $this->addBirthDateWhereClauses();
    // deceased clauses if selected
    $this->addDeceasedWhereClauses();
    // expert status clauses if selected
    $this->addExpertStatusWhereClauses();
    // cv mutation clauses if selected
    $this->addCvMutationWhereClauses();

    if (!empty($this->_whereClauses)) {
      $where = implode(' AND ', $this->_whereClauses);
    }
    return $this->whereClause($where, $this->_whereParams);
  }

  /**
   * Method to add cv mutation where clauses
   */
  private function addCvMutationWhereClauses() {
    if (isset($this->_formValues['cv_mutation_id']) && !empty($this->_formValues['cv_mutation_id'])) {
      $this->_whereIndex++;
      $this->_whereParams[$this->_whereIndex] = array(1, 'Integer');
      if ($this->_formValues['cv_mutation_id'] == 1) {
        $this->_whereClauses[] = 'exp.'.$this->_expCvMutationColumn.' = %'.$this->_whereIndex;
      }
      if ($this->_formValues['cv_mutation_id'] == 2) {
        $this->_whereClauses[] = 'exp.'.$this->_expCvMutationColumn.' <> %'.$this->_whereIndex;
      }
    }
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
        $this->_whereClauses[] = '(exp.'.$this->_expStatusColumn.' IN("'.implode('", "', $statusIds).'"))';
      }
    }
  }
  /**
   * Method to add the language and level where clauses
   */
  private function addLanguageLevelWhereClauses() {
    if (isset($this->_formValues['language_id'])) {
      $languageLevelClauses = array();
      foreach ($this->_formValues['language_id'] as $languageLevelId) {
        $this->_whereIndex++;
        $clause = '('.$this->_llLanguagesColumn.' = %'.$this->_whereIndex;
        $this->_whereParams[$this->_whereIndex] = array($this->_languagesWithLevelList[$languageLevelId]['language_id'], 'String');
        // only if a language with another level than 'Any' is selected a level part of the clause is required
        if (!empty($this->_languagesWithLevelList[$languageLevelId]['level_id'])) {
          $this->_whereIndex++;
          $clause .= ' AND ' . $this->_llLevelColumn .' = %'.$this->_whereIndex;
          $this->_whereParams[$this->_whereIndex] = array($this->_languagesWithLevelList[$languageLevelId]['level_id'], 'String');
        }
        $languageLevelClauses[] = $clause.')';
      }
      if (!empty($languageLevelClauses)) {
        $this->_whereClauses[] = '('.implode(' OR ', $languageLevelClauses).')';
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
   * Method to set the deceased where clauses
   */
  private function addDeceasedWhereClauses() {
    if (isset($this->_formValues['deceased_id'])) {
      switch ($this->_formValues['deceased_id']) {
        // only not deceased contacts, ignore date range
        case 1:
          $this->_whereIndex++;
          $this->_whereParams[$this->_whereIndex] = array(0, 'Integer');
          $this->_whereClauses[] = 'contact_a.is_deceased = %'.$this->_whereIndex;
          break;
        // all contacts, only test date range
        case 2:
          $this->setDateRangeClauses('deceased_date', 'contact_a.deceased_date');
          break;
        // only deceased
        case 3:
          $this->_whereIndex++;
          $this->_whereParams[$this->_whereIndex] = array(1, 'Integer');
          $this->_whereClauses[] = 'contact_a.is_deceased = %'.$this->_whereIndex;
          $this->setDateRangeClauses('deceased_date', 'contact_a.deceased_date');
          break;
      }
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
      $this->_whereClauses[] = $columnName.' BETWEEN %'.$fromIndex.' AND %'.$toIndex;
    } else {
      if (isset($fromIndex)) {
        $this->_whereClauses[] = $columnName.' >= %'.$fromIndex;
      }
      if (isset($toIndex)) {
        $this->_whereClauses[] = $columnName.' <= %'.$toIndex;
      }
    }
  }

  /**
   * Method to add the generic skills where clauses
   */
  private function addGenericSkillsWhereClauses() {
    if (isset($this->_formValues['generic_id'])) {
      $genericClauses = array();
      foreach ($this->_formValues['generic_id'] as $genericId) {
        $this->_whereIndex++;
        $genericClauses[] = $this->_expGenericColumn.' LIKE %'.$this->_whereIndex;
        $this->_whereParams[$this->_whereIndex] = array('%'.$this->_genericSkillsList[$genericId].'%', 'String');
      }
      if (!empty($genericClauses)) {
        $this->_whereClauses[] = '('.implode(' OR ', $genericClauses).')';
      }
    }
  }

  /**
   * Method to add the area of expertise where clauses
   */
  private function addExpertiseWhereClauses() {
    if (isset($this->_formValues['expertise_id'])) {
      $expertiseIds = array();
      foreach ($this->_formValues['expertise_id'] as $expertiseId) {
        $this->_whereIndex++;
        $expertiseIds[$this->_whereIndex] = $expertiseId;
        $this->_whereParams[$this->_whereIndex] = array($expertiseId, 'Integer');
      }
      if (!empty($expertiseIds)) {
        $this->_whereClauses[] = '(areas.segment_id IN('.implode(', ', $expertiseIds).'))';
      }
    }
  }

  /**
   * Method to add the birth date where clauses
   * 
   * @access private
   */
  private function addBirthDateWhereClauses() {
    if (isset($this->_formValues['birth_date_from']) || isset($this->_formValues['birth_date_to'])) {
      $this->setDateRangeClauses('birth_date', 'contact_a.birth_date');
    }
  }

 /**
   * Method to add the gender where clauses
   */
  private function addGenderWhereClauses() {
    if (isset($this->_formValues['gender_id'])) {
      $genderIds = array();
      foreach ($this->_formValues['gender_id'] as $genderId) {
        $this->_whereIndex++;
        $genderIds[$this->_whereIndex] = $genderId;
        $this->_whereParams[$this->_whereIndex] = array($genderId, 'Integer');
      }
      if (!empty($genderIds)) {
        $this->_whereClauses[] = '(contact_a.gender_id IN('.implode(', ', $genderIds).'))';
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
    return 'CRM/Sectorsupportsearch/FindExpert.tpl';
  }

  /**
   * Modify the content of each row
   *
   * @param array $row modifiable SQL result row
   * @throws exception if function getOptionGroup not found
   * @return void
   */
  function alterRow(&$row) {
    $row['restrictions'] = $this->setRestrictions($row['contact_id']);
    $row['contact_age'] = $this->calculateContactAge($row['contact_id']);
    //$row['expert_status_date_range'] = $this->buildExpertStatusDateRange();
  }

  private function buildExpertStatusDateRange() {
    $result = "";
    if (isset($this->_formValues['expert_status_date_from']) && !empty($this->_formValues['expert_status_date_from'])) {
      $result = 'Start Date from '.date('d M Y', strtotime($this->_formValues['expert_status_date_from']));
    }
  }

  /**
   * Method to calculate contact age
   * @param $contactId
   * @return bool|int
   * @throws CiviCRM_API3_Exception
   */
  private function calculateContactAge($contactId) {
    $birthDate = civicrm_api3('Contact', 'Getvalue', array('id' => $contactId, 'return' => 'birth_date'));
    if (!empty($birthDate)) {
      $dateOfBirth = new DateTime($birthDate);
      $age = $dateOfBirth->diff(new DateTime('now'));
      return $age->y;
    }
    return FALSE;
  }

  /**
   * Method to check if there are active restrictions for expert
   *
   * @param $contactId
   * @return string
   */
  private function setRestrictions($contactId) {
    try {
      $activities = civicrm_api3('Activity', 'getcount', array(
        'activity_type_id' => $this->_restrictionsActivityTypeId,
        'target_contact_id' => $contactId,
        'is_current_revision' => 1,
        'is_deleted' => 0,
        'status_id' => $this->_scheduledActivityStatusValue
      ));
      
      if ($activities > 0) {
        return 'Yes';
      } else {
        return 'No';
      }
    } catch (CiviCRM_API3_Exception $ex) {
      return 'No';
    }
  }

  /**
   * Method to initialize the list of languageLevels
   *
   * @throws Exception when error from API Option Value get
   * @return void
   */
  private function setLanguagesWithLevels() {
    $levelValues = array();
    $levelParams = array('name' => 'level_432_20140806134147', 'return' => 'id');
    try {
      $levelOptionGroupId = civicrm_api3('OptionGroup', 'Getvalue', $levelParams);
      try {
        $levelOptionValues = civicrm_api3('OptionValue', 'Get',
          array('option_group_id' => $levelOptionGroupId, 'is_active' => 1));
        foreach ($levelOptionValues['values'] as $level) {
          $levelValues[$level['value']] = $level['label'];
        }
      } catch (CiviCRM_API3_Exception $ex) {}
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find an option group for level languages with name
      level_432_20140806134147 in extension nl.pum.findexpert ('.__METHOD__.'), contact your 
      system administrator. Error from API OptionGroup Getvalue: '.$ex->getMessage()
        .' with params '.implode('; ', $levelParams));
    }
    $languageParams = array('name' => 'language_20140716104058', 'return' => 'id');
    try {
      $languageOptionGroupId = civicrm_api3('OptionGroup', 'Getvalue', $languageParams);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find an option group for expert languages with name
      language_20140716104058 in extension nl.pum.findexpert ('.__METHOD__.'), contact your 
      system administrator. Error from API OptionGroup Getvalue: '.$ex->getMessage()
        .' with params '.implode('; ', $languageParams));
    }
    try {
      $languageOptionValues = civicrm_api3('OptionValue', 'Get',
        array('option_group_id' => $languageOptionGroupId, 'is_active' => 1));
      foreach ($languageOptionValues['values'] as $language) {
        $languageLevel = array();
        $languageLevel['language_id'] = $language['value'];
        $languageLevel['language_label'] = $language['label'];
        $languageLevel['level_id'] = 0;
        $languageLevel['level_label'] = 'Any level';
        $this->_languagesWithLevelList[] = $languageLevel;
        foreach ($levelValues as $levelId => $levelLabel) {
          $languageLevel = array();
          $languageLevel['language_id'] = $language['value'];
          $languageLevel['language_label'] = $language['label'];
          $languageLevel['level_id'] = $levelId;
          $languageLevel['level_label'] = $levelLabel;
          $this->_languagesWithLevelList[] = $languageLevel;
        }
      }
    } catch (CiviCRM_API3_Exception $ex) {}
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
      array('name' => 'Languages', 'property' => '_language'),
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
    $this->_expGenericColumn = civicrm_api3('CustomField', 'Getvalue', array(
      'custom_group_id' => $this->_expertDataCustomGroupId, 'name' => 'generic_skills',
      'return' => 'column_name'));
    $this->_expStatusColumn = civicrm_api3('CustomField', 'Getvalue', array(
      'custom_group_id' => $this->_expertDataCustomGroupId, 'name' => 'expert_status',
      'return' => 'column_name'));
    $this->_expStatusStartDateColumn = civicrm_api3('CustomField', 'Getvalue', array(
      'custom_group_id' => $this->_expertDataCustomGroupId, 'name' => 'expert_status_start_date',
      'return' => 'column_name'));
    $this->_expStatusEndDateColumn = civicrm_api3('CustomField', 'Getvalue', array(
      'custom_group_id' => $this->_expertDataCustomGroupId, 'name' => 'expert_status_end_date',
      'return' => 'column_name'));
    $this->_expCvMutationColumn = civicrm_api3('CustomField', 'Getvalue', array(
      'custom_group_id' => $this->_expertDataCustomGroupId, 'name' => 'CV_in_Mutation',
      'return' => 'column_name'));

    // required columns for languages
    $this->_llLanguagesColumn = civicrm_api3('CustomField', 'Getvalue', array(
      'custom_group_id' => $this->_languageCustomGroupId, 'name' => 'Language',
      'return' => 'column_name'));
    $this->_llLevelColumn = civicrm_api3('CustomField', 'Getvalue', array(
      'custom_group_id' => $this->_languageCustomGroupId, 'name' => 'Level',
      'return' => 'column_name'));
  }

  /**
   * Method to set the initial where clauses that apply to each instance
   */
  private function addInitialWhereClauses() {
    $this->_whereClauses[] = '(contact_a.contact_sub_type LIKE %1)';
    $this->_whereParams[1] = array('%Expert%', 'String');
    $this->_whereIndex = 1;
  }

  /**
   * Method to set activity type properties
   *
   * @throws Exception when no option group activity type found
   */
  private function setActivityTypes() {
    try {
      $activityTypeOptionGroupId = civicrm_api3('OptionGroup', 'Getvalue', array('name' => 'activity_type', 'return' => 'id'));
      $restrictionsParams = array(
        'option_group_id' => $activityTypeOptionGroupId,
        'name' => 'Restrictions',
        'return' => 'value'
      );
      try {
        $this->_restrictionsActivityTypeId = civicrm_api3('OptionValue', 'Getvalue', $restrictionsParams);
      } catch (CiviCRM_API3_Exception $ex) {
        $this->_restrictionsActivityTypeId = NULL;
      }
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find option group for activity type in '.__METHOD__.', error from API OptionGroup Getvalue: '.$ex->getMessage());
    }
  }

  /**
   * Method to set activity status properties
   *
   * @throws Exception when no option group activity status found
   */
  private function setActivityStatus() {
    try {
      $activityStatusOptionGroupId = civicrm_api3('OptionGroup', 'Getvalue', array('name' => 'activity_status', 'return' => 'id'));
      $scheduledParams = array(
        'option_group_id' => $activityStatusOptionGroupId,
        'name' => 'Scheduled',
        'return' => 'value'
      );
      try {
        $this->_scheduledActivityStatusValue = civicrm_api3('OptionValue', 'Getvalue', $scheduledParams);
      } catch (CiviCRM_API3_Exception $ex) {
        $this->_scheduledActivityStatusValue = NULL;
      }
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find option group for activity status in '.__METHOD__.', error from API OptionGroup Getvalue: '.$ex->getMessage());
    }
  }

  /**
   * Method to set the valid case status for latest main activity
   *
   * @throws Exception when no option group case status found
   */
  private function setValidCaseStatus() {
    $requiredCaseStatus = array('Completed', 'Debriefing', 'Execution', 'Matching', 'Preparation');
    try {
      $caseStatusOptionGroupId = civicrm_api3('OptionGroup', 'Getvalue', array('name' => 'case_status', 'return' => 'id'));
      $foundCaseStatus = civicrm_api3('OptionValue', 'Get', array('option_group_id' => $caseStatusOptionGroupId, 'is_active' => 1));
      foreach ($foundCaseStatus['values'] as $caseStatus) {
        if (in_array($caseStatus['name'], $requiredCaseStatus)) {
          $this->_validCaseStatus[$caseStatus['value']] = $caseStatus['name'];
        }
      }
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find option group for case status in '.__METHOD__.', error from API OptionGroup Getvalue: '.$ex->getMessage());
    }
  }

  /**
   * Method to set the valid case types for latest main activity
   *
   * @throws Exception when no option group case type found
   */
  private function setValidCaseTypes() {
    $requiredCaseTypes = array('Advice', 'Business', 'RemoteCoaching', 'Seminar');
    try {
      $caseTypeOptionGroupId = civicrm_api3('OptionGroup', 'Getvalue', array('name' => 'case_type', 'return' => 'id'));
      $foundCaseTypes = civicrm_api3('OptionValue', 'Get', array('option_group_id' => $caseTypeOptionGroupId, 'is_active' => 1));
      foreach ($foundCaseTypes['values'] as $caseType) {
        if (in_array($caseType['name'], $requiredCaseTypes)) {
          $this->_validCaseTypes[$caseType['value']] = $caseType['name'];
        }
      }
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find option group for case type in '.__METHOD__.', error from API OptionGroup Getvalue: '.$ex->getMessage());
    }
  }
}
