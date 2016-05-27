<?php
/**
 * Custom search to Find Contact from Sector Support role
 * PUM Senior Experts
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 24 May 2016
 * @license AGPL-3.0
 */
class CRM_Sectorsupportsearch_Form_Search_FindContact extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

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

  /**
   * CRM_Sectorsupportsearch_Form_Search_FindContact constructor.
   * @param $formValues
   */
  function __construct(&$formValues) {
    $this->setLanguagesWithLevels();
    $this->getGenericSkillsList();
    $this->setRequiredCustomTables();
    $this->setRequiredCustomColumns();

    parent::__construct($formValues);
  }

  /**
   * Prepare a set of search fields
   *
   * @param CRM_Core_Form $form modifiable
   * @return void
   */
  function buildForm(&$form) {
    CRM_Utils_System::setTitle(ts('Find Contact(s) for PUM Sector Support'));

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
    $form->add('text', 'age_from', ts('Age Range From'), false);
    $form->add('text', 'age_to', ts('... to'), false);

    // search on deceased and deceased date range
    $deceasedList = array(
      1 => ts('Only contacts that are not deceased)'),
      2 => ts('All contacts'),
      3 => ts('Only deceased contacts'),
    );
    $form->addRadio('deceased_id', ts('Deceased?'), $deceasedList, NULL, '<br />', TRUE);
    $defaults['deceased_id'] = 1;
    $form->addDate('deceased_date_from', ts('Deceased Date From'), FALSE, array('formatType' => 'custom'));
    $form->addDate('deceased_date_to', ts('...to'), FALSE, array('formatType' => 'custom'));

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

    // search on expert status and status start/end date
    $expertStatusList = $this->getExpertStatusList();
    $form->add('select', 'expert_status_id', ts('ExpertStatus(es)'), $expertStatusList, FALSE,
      array('id' => 'expert_status_id', 'multiple' => 'multiple', 'title' => ts('- select -'))
    );

    // search on CV Mutation
    $cvMutationList = array(
      1 => ts('Only contacts with CV In Mutation YES)'),
      2 => ts('Only contacts with CV In Mutation NO'),
      3 => ts('All contacts')
    );
    $form->addRadio('cv_mutation_id', ts('CV in Mutation?'), $cvMutationList, NULL, '<br />', TRUE);
    $defaults['cv_mutation_id'] = 3;

    $form->setDefaults($defaults);

    $form->assign('elements', array('sector_id', 'expertise_id', 'generic_id', 'language_id',
      'age_from', 'age_to', 'deceased_id', 'deceased_date_from', 'deceased_date_to', 'gender_id',
      'group_id', 'expert_status_id', 'expert_status_date_from', 'expert_status_date_to', 'cv_mutation_id'));

    $form->addButtons(array(array('type' => 'refresh', 'name' => ts('Search'), 'isDefault' => TRUE,),));
  }

  /**
   * Function to add validation rules
   */
  function addRules() {
    $this->addFormRule(array('CRM_Sectorsupportsearch_Form_Search_FindContact', 'validateDeceased'));
    $this->addFormRule(array('CRM_Sectorsupportsearch_Form_Search_FindContact', 'validateDateRange'));
    $this->addFormRule(array('CRM_Sectorsupportsearch_Form_Search_FindContact', 'validateAgeRange'));
  }

  /**
   * Method to validate that no deceased dates are set if only non-deceased selected
   *
   * @param $fields
   * @return bool
   * @static
   */
  public static function validateDeceased($fields) {
    if (isset($fields['deceased_id']) && $fields['deceased_id'] == 1) {
      if (isset($fields['deceased_date_from']) && !empty($fields['deceased_date_from'])) {
        $errors['deceased_date_from'] = ts('You can not set a deceased date range if you are only searching for contacts that are not deceased.');
        return $errors;
      }
      if (isset($fields['deceased_date_to']) && !empty($fields['deceased_date_to'])) {
        $errors['deceased_date_to'] = ts('You can not set a deceased date range if you are only searching for contacts that are not deceased.');
        return $errors;
      }
    }
    return TRUE;
  }

  /**
   * Method to validate age range
   *
   * @param $fields
   * @return bool
   * @static
   */
  public static function validateAgeRange($fields) {
    if (isset($fields['age_from']) && isset($fields['age_to'])) {
      if (!empty($fields['age_to'])) {
        if ($fields['age_to'] < $fields['age_from']) {
          $errors['age_to'] = ts('Age to has to be bigger than the age from.');
          return $errors;
        }
      }
    }
    return TRUE;
  }

  /**
   * Validate date ranges: to date can not be earlier than from date
   * 
   * @param $fields
   * @return bool
   * @static
   */
  public static function validateDateRange($fields) {
    if (isset($fields['deceased_date_from']) && isset($fields['deceased_date_to'])) {
      if (!empty($fields['deceased_date_to'])) {
        if ($fields['deceased_date_to'] < $fields['deceased_date_from']) {
          $errors['deceased_date_to'] = ts('Deceased date to has to be later than the deceased date from.');
          return $errors;
        }
      }
    }
    if (isset($fields['expert_status_date_from']) && isset($fields['expert_status_date_to'])) {
      if (!empty($fields['expert_status_date_to'])) {
        if ($fields['expert_status_date_to'] < $fields['expert_status_date_from']) {
          $errors['expert_status_date_to'] = ts('Expert status date to has to be later than the expert status date date from.');
          return $errors;
        }
      }
    }
    return TRUE;
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
        $result[$optionValue['value']] = $result[$optionValue['label']];
      }
    } catch (CiviCRM_API3_Exception $ex) {}
    asort($result);
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
      foreach ($optionValues as $optionValue) {
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
      $groups = civicrm_api3('Group', 'Get', array('is_active' => 1, 'option' => array('limit' => '9999')));
      foreach ($groups['values'] as $group) {
        $result[$groups['id']] = $result[$group['title']];
      }
    } catch (CiviCRM_API3_Exception $ex) {}
    asort($result);
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
      ts('Phone') => 'phone',
      ts('Contact Type') => 'contact_type',
      ts('Last Main Activity') => 'last_main',
      ts('Main Sector') => 'main_sector',
      ts('Expert Status') => 'expert_status',
      ts('Expert Status Date From - To') => 'expert_status_date_range',
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
    '' as expert_status_date_range, phone.phone AS phone, contact_a.contact_type AS contact_type, 
    contact_a.gender_id AS gender_id";
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
    LEFT JOIN civicrm_phone phone ON contact_a.id = phone.contact_id AND phone.is_primary = 1
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
    // age range clauses if selected
    $this->addAgeWhereClauses();
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
      if ($this->_formValues['cv_mutation_id'] == 1) {
        $this->_whereIndex++;
        $this->_whereParams[$this->_whereIndex] = array(1, 'Integer');
        $this->_whereClauses[] = 'exp.'.$this->_expCvMutationColumn.' = %'.$this->_whereIndex;
      }
      if ($this->_formValues['cv_mutation_id'] == 2) {
        $this->_whereIndex++;
        $this->_whereParams[$this->_whereIndex] = array(0, 'Integer');
        $this->_whereClauses[] = 'exp.'.$this->_expCvMutationColumn.' = %'.$this->_whereIndex;
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
        $this->_whereParams[$this->_whereIndex] = array($statusId, 'Integer');
      }
      if (!empty($statusIds)) {
        $this->_whereClauses[] = '(exp.'.$this->_expStatusColumn.' IN('.implode(', ', $statusIds).'))';
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
      $this->_whereIndex++;
      $fromIndex = $this->_whereIndex;
      $this->_whereParams[$fromIndex] = array($this->_formValues[$fieldName.'_from'], 'Date');
    }
    if (isset($this->_formValues[$fieldName.'_to']) && !empty($this->_formValues[$fieldName.'_to'])) {
      $this->_whereIndex++;
      $toIndex = $this->_whereIndex;
      $this->_whereParams[$toIndex] = array($this->_formValues[$fieldName.'_to'], 'Date');
    }
    if ($fromIndex && $toIndex) {
      $this->_whereClauses[] = $columnName.' BETWEEN %'.$fromIndex.' AND %'.$toIndex;
    } else {
      if ($fromIndex) {
        $this->_whereClauses[] = $columnName.' >= %'.$fromIndex;
      }
      if ($toIndex) {
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
   * Method to add the age where clauses
   * 
   * @access private
   */
  private function addAgeWhereClauses() {
    if (isset($this->_formValues['age_from']) || isset($this->_formValues['age_to'])) {
      $birthDates = $this->calculateBirthDatesForAge();
      $this->_whereIndex++;
      $fromIndex = $this->_whereIndex;
      $this->_whereParams[$this->_whereIndex] = array($birthDates['from'], 'Date');
      $this->_whereIndex++;
      $this->_whereParams[$this->_whereIndex] = array($birthDates['to'], 'Date');
      $this->_whereClauses[] = '(contact_a.birth_date BETWEEN %'.$this->_whereIndex.' AND %'.$fromIndex.')';
    }
  }

  /**
   * Method to calculate date range for birth date (age comparison)
   * 
   * @access private
   */
  private function calculateBirthDatesForAge() {
    $result = array();
    if (isset($this->_formValues['age_from']) && !empty($this->_formValues['age_from'])) {
      $fromDate = new DateTime();
      $ageFromYears = new DateInterval('P'.$this->_formValues['age_from'].'Y');
      $fromDate->sub($ageFromYears);
      $result['from'] = $fromDate->format('Y-m-d');
    }
    if (isset($this->_formValues['age_to']) && !empty($this->_formValues['age_to'])) {
      $toDate = new DateTime();
      $ageToYears = new DateInterval('P'.$this->_formValues['age_to'].'Y');
      $toDate->sub($ageToYears);
      $result['to'] = $toDate->format('Y-m-d');
    }
    return $result;
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
    return 'CRM/Sectorsupportsearch/FindContact.tpl';
  }

  /**
   * Modify the content of each row
   *
   * @param array $row modifiable SQL result row
   * @throws exception if function getOptionGroup not found
   * @return void
   */
  function alterRow(&$row) {
    // todo : add number of main
    $row['restrictions'] = $this->setRestrictions($row['contact_id']);
    $row['latest_main'] = $this->setLatestMain($row['contact_id']);
    $row['contact_age'] = $this->calculateContactAge($row['contact_id']);
    $row['expert_status_date_range'] = $this->buildExpertStatusDateRange();
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
      $birthDate = date('d-m-Y', strtotime($birthDate));
      return CRM_Utils_Date::calculateAge($birthDate);
    }
    return FALSE;
  }

  /**
   * Method to retrieve the latest case for the contact of case type
   * Advice, RemoteCoaching, Seminar or Business where case status is
   * either Matching, Execution, Debriefing, Preparation or Completed
   *
   * @param int $contactId
   * @return string
   * @throws Exception when no relationship type Expert found
   */
  private function setLatestMain($contactId) {
    // build query for civicrm_relationship where type = Expert and case id is not empty
    // joined with case data of the right case type and status
    try {
      $expertRelationshipTypeId = civicrm_api3('RelationshipType', 'Getvalue', array('name_a_b' => 'Expert', 'return' => 'id'));
      $query = "SELECT cc.subject 
        FROM civicrm_relationship rel 
        JOIN civicrm_case cc ON rel.case_id = cc.id
        LEFT JOIN civicrm_value_main_activity_info main ON rel.case_id = main.entity_id
        WHERE rel.relationship_type_id = %1 AND rel.contact_id_b = %2 AND cc.is_deleted = %3";
      $params = array(
        1 => array($expertRelationshipTypeId, 'Integer'),
        2 => array($contactId, 'Integer'),
        3 => array(0, 'Integer')
      );
      $index = 3;
      // set where clauses for case status
      if (!empty($this->_validCaseStatus)) {
        $statusValues = array();
        foreach ($this->_validCaseStatus as $statusId => $statusName) {
          $index++;
          $params[$index] = array($statusId, 'Integer');
          $statusValues[] = '%' . $index;
        }
        $query .= ' AND cc.status_id IN(' . implode(', ', $statusValues).')';
      }
      // set where clauses for case types
      if (!empty($this->_validCaseTypes)) {
        $typeValues = array();
        foreach ($this->_validCaseTypes as $caseTypeId => $caseTypeName) {
          $index++;
          $params[$index] = array('%' . $caseTypeId . '%', 'String');
          $typeValues[] = 'cc.case_type_id LIKE %' . $index;
        }
        $query .= ' AND ('.implode(' OR ', $typeValues).')';
      }
      $query .= ' ORDER BY main.start_date DESC LIMIT 1';
      return CRM_Core_DAO::singleValueQuery($query, $params);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find a relationship type with name Expert in '.__METHOD__
        .', error from API RelationshipType Getvalue: '.$ex->getMessage());
    }
  }

  /**
   * Method to check if there are active restrictions for expert
   *
   * @param $contactId
   * @return string
   */
  private function setRestrictions($contactId) {
    try {
      $activities = civicrm_api3('Activity', 'Getcount', array(
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
    $this->_whereClauses[] = '(contact_a.is_deceased = %2)';
    $this->_whereParams[2] = array(0, 'Integer');
    $this->_whereClauses[] = '(exp.'.$this->_expStatusColumn.' NOT IN(%3, %4))';
    $this->_whereParams[3] = array('Exit', 'String');
    $this->_whereParams[4] = array('Suspended', 'String');
    $this->_whereIndex = 4;
  }
}
