<?php

/**
 * Custom search validation for FindCase
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 30 May 2016
 * @license AGPL-3.0
 */
class CRM_Sectorsupportsearch_FindCase {

  /**
   * Method to process validateForm hook
   *
   * @param $fields
   * @param $errors
   */
  public static function validateForm($fields, &$errors) {
    self::validateContactName($fields, $errors);
    self::validateDateRanges($fields, $errors);
  }

  /**
   * Method to validate that contact name is at least 3 chars
   *
   * @param array $fields
   * @param array $errors
   * @access private
   * @static
   */
  private static function validateContactName($fields, &$errors) {
    if (isset($fields['contact_name']) && !empty($fields['contact_name'])) {
      if (strlen($fields['contact_name']) < 3) {
        $errors['contact_name'] = ts('You have to enter 3 characters or more to get search results (to avoid getting huge and time consuming searches).');
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
    if (isset($fields['start_date_from']) && isset($fields['start_date_to'])) {
      if (!empty($fields['start_date_to']) && !empty($fields['start_date_from'])) {
        $dateTo = new DateTime($fields['start_date_to']);
        $dateFrom = new DateTime($fields['start_date_from']);
        if ($dateTo < $dateFrom) {
          $errors['start_date_to'] = ts('Case start date to has to be later than the case start date from.');
        }
      }
    }
    if (isset($fields['end_date_from']) && isset($fields['end_date_to'])) {
      if (!empty($fields['end_date_to']) && !empty($fields['end_date_from'])) {
        $dateTo = new DateTime($fields['end_date_to']);
        $dateFrom = new DateTime($fields['end_date_from']);
        if ($dateTo < $dateFrom) {
          $errors['end_date_to'] = ts('Case end date to has to be later than the case end date from.');
        }
      }
    }
  }
}