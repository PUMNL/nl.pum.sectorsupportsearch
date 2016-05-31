{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{* Search criteria form elements - Find Case *}

{* Set title for search criteria accordion *}
{capture assign=editTitle}{ts}Edit Search Criteria for Case(s){/ts}{/capture}

{strip}
  <div class="crm-block crm-form-block crm-basic-criteria-form-block">
    <div class="crm-accordion-wrapper crm-case_search-accordion {if $rows}collapsed{/if}">
      <div class="crm-accordion-header crm-master-accordion-header">
        {$editTitle}
      </div><!-- /.crm-accordion-header -->
      <div class="crm-accordion-body">

        {if $form.contact_name}
          <div class="crm-section contact_name-section">
            <div class="label">
              <label for="contact-name">{$form.contact_name.label}</label>
            </div>
            <div class="content" id="contact-name">
              {$form.contact_name.html}
            </div>
            <div class="clear"></div>
          </div>
        {/if}

        {if $form.expert_status_id}
          <div class="crm-section expert-status-section">
            <div class="label">
              <label for="expert-status-select">{$form.expert_status_id.label}</label>
            </div>
            <div class="content" id="group-select">
              {$form.expert_status_id.html}
              {literal}
                <script type="text/javascript">
                  cj("select#expert_status_id").crmasmSelect({
                    respectParents: true
                  });
                </script>
              {/literal}
            </div>
            <div class="clear"></div>
          </div>
        {/if}

        {if $form.group_id}
          <div class="crm-section group-section">
            <div class="label">
              <label for="group-select">{$form.group_id.label}</label>
            </div>
            <div class="content" id="group-select">
              {$form.group_id.html}
              {literal}
                <script type="text/javascript">
                  cj("select#group_id").crmasmSelect({
                    respectParents: true
                  });
                </script>
              {/literal}
            </div>
            <div class="clear"></div>
          </div>
        {/if}

        {if $form.case_type_id}
          <div class="crm-section case-type-section">
            <div class="label">
              <label for="case-type-select">{$form.case_type_id.label}</label>
            </div>
            <div class="content" id="case_type-select">
              {$form.case_type_id.html}
              {literal}
                <script type="text/javascript">
                  cj("select#case_type_id").crmasmSelect({
                    respectParents: true
                  });
                </script>
              {/literal}
            </div>
            <div class="clear"></div>
          </div>
        {/if}

        {if $form.sector_id}
          <div class="crm-section sector-section">
            <div class="label">
              <label for="sector-select">{ts}Sector(s){/ts}</label>
            </div>
            <div class="content" id="sector-select">
              {$form.sector_id.html}
              {literal}
                <script type="text/javascript">
                  cj("select#sector_id").crmasmSelect({
                    respectParents: true
                  });
                </script>
              {/literal}
            </div>
            <div class="clear"></div>
          </div>
        {/if}

        {if $form.start_date_from and $form.start_date_to}
          <div class="crm-section start-date-from-section">
            <div class="label">
              <label for="start-date-from">{$form.start_date_from.label}</label>
            </div>
            <div class="content" id="start-date-from">
              {include file="CRM/common/jcalendar.tpl" elementName='start_date_from'}
            </div>
            <div class="clear"></div>
          </div>
          <div class="crm-section start-date-to-section">
            <div class="label">
              <label for="start-date-to">{$form.start_date_to.label}</label>
            </div>
            <div class="content" id="start-date-to">
              {include file="CRM/common/jcalendar.tpl" elementName='start_date_to'}
            </div>
            <div class="clear"></div>
          </div>
        {/if}

        {if $form.end_date_from and $form.end_date_to}
          <div class="crm-section end-date-from-section">
            <div class="label">
              <label for="end-date-from">{$form.end_date_from.label}</label>
            </div>
            <div class="content" id="end-date-from">
              {include file="CRM/common/jcalendar.tpl" elementName='end_date_from'}
            </div>
            <div class="clear"></div>
          </div>
          <div class="crm-section end-date-to-section">
            <div class="label">
              <label for="end-date-to">{$form.end_date_to.label}</label>
            </div>
            <div class="content" id="end-date-to">
              {include file="CRM/common/jcalendar.tpl" elementName='end_date_to'}
            </div>
            <div class="clear"></div>
          </div>
        {/if}

        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
      </div><!-- /.crm-accordion-body -->
    </div><!-- /.crm-accordion-wrapper -->
  </div><!-- /.crm-form-block -->
{/strip}
{literal}
  <script type="text/javascript">
    cj(function() {
      cj().crmAccordions();
    });
  </script>
{/literal}


