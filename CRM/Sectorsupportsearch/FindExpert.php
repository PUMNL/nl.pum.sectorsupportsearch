<?php

/**
 * Custom search validation for FindExpert
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 30 May 2016
 * @license AGPL-3.0
 */
class CRM_Sectorsupportsearch_FindExpert {
  
  /**
   * Method to process validateForm hook
   * 
   * @param $fields
   * @param $errors
   */
  public static function validateForm($fields, &$errors) {
    if (!self::validateCriteriaEntered($fields)) {
      $errors['sector_id'] = ts("You have to enter at least one criterium to search on otherwise the list will get too long and take too much time");
    }
    self::validateDeceased($fields, $errors);
    self::validateAgeRange($fields, $errors);
    self::validateDateRanges($fields, $errors);
  }
  /**
   * Method to check that at least one criterium is selected
   *
   * @param array $fields
   * @return bool
   * @access private
   * @static
   */
  private static function validateCriteriaEntered($fields) {
    // if at least one of the select lists has been selected return true
    $selectedSelects = array('sector_id', 'expertise_id', 'generic_id', 'language_id', 'gender_id', 'group_id', 'expert_status_id');
    foreach ($selectedSelects as $selectedSelect) {
      if (array_key_exists($selectedSelect, $fields)) {
        return TRUE;
      }
    }
    // if either age range or deceased range has been entered return true
    $enteredFields = array('age_from', 'age_to', 'deceased_date_from', 'deceased_date_to');
    foreach ($enteredFields as $enteredField) {
      if (!empty($fields[$enteredField])) {
        return TRUE;
      }
    }
    // if nothing else has been entered, return true if deceased or cv mutation are not set to default values
    if ($fields['cv_mutation_id'] != 3 || $fields['deceased_id'] != 1) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Method to validate that no deceased dates are set if only non-deceased selected
   *
   * @param array $fields
   * @param array $errors
   * @access private
   * @static
   */
  private static function validateDeceased($fields, &$errors) {
    if (isset($fields['deceased_id']) && $fields['deceased_id'] == 1) {
      if (isset($fields['deceased_date_from']) && !empty($fields['deceased_date_from'])) {
        $errors['deceased_date_from'] = ts('You can not set a deceased date range if you are only searching for contacts that are not deceased.');
      }
      if (isset($fields['deceased_date_to']) && !empty($fields['deceased_date_to'])) {
        $errors['deceased_date_to'] = ts('You can not set a deceased date range if you are only searching for contacts that are not deceased.');
      }
    }
  }

  /**
   * Method to validate age range
   *
   * @param array $fields
   * @param array $errors
   * @access private
   * @static
   */
  private static function validateAgeRange($fields, &$errors) {
    if (isset($fields['age_from']) && isset($fields['age_to'])) {
      if (!empty($fields['age_to'])) {
        if ($fields['age_to'] < $fields['age_from']) {
          $errors['age_to'] = ts('Age to has to be bigger than the age from.');
       }
      }
    }
  }

  /**
   * Validate date ranges: to date can not be earlier than from date
   *
   * @param array $fields
   * @param array $errors
   * @access private
   * @static
   */
  private static function validateDateRanges($fields, &$errors) {
    if (isset($fields['deceased_date_from']) && isset($fields['deceased_date_to'])) {
      if (!empty($fields['deceased_date_to']) && !empty($fields['deceased_date_from'])) {
        $dateTo = new DateTime($fields['deceased_date_to']);
        $dateFrom = new DateTime($fields['deceased_date_from']);
        if ($dateTo < $dateFrom) {
          $errors['deceased_date_to'] = ts('Deceased date to has to be later than the deceased date from.');
        }
      }
    }
    if (isset($fields['expert_status_date_from']) && isset($fields['expert_status_date_to'])) {
      if (!empty($fields['expert_status_date_to']) && !empty($fields['expert_status_from'])) {
        if ($fields['expert_status_date_to'] < $fields['expert_status_date_from']) {
          $errors['expert_status_date_to'] = ts('Expert status date to has to be later than the expert status date date from.');
        }
      }
    }
  }
}