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
{* Search criteria form elements - Find Experts *}

{* Set title for search criteria accordion *}
{capture assign=editTitle}{ts}Edit Search Criteria for Expert(s){/ts}{/capture}

{strip}
  <div class="crm-block crm-form-block crm-basic-criteria-form-block">
    <div class="crm-accordion-wrapper crm-case_search-accordion {if $rows}collapsed{/if}">
      <div class="crm-accordion-header crm-master-accordion-header">
        {$editTitle}
      </div><!-- /.crm-accordion-header -->
      <div class="crm-accordion-body">

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

        {if $form.expertise_id}
          <div class="crm-section expertise-section">
            <div class="label">
              <label for="expertise-select">{ts}Area(s) of Expertise{/ts}</label>
            </div>
            <div class="content" id="expertise-select">
              {$form.expertise_id.html}
              {literal}
                <script type="text/javascript">
                  cj("select#expertise_id").crmasmSelect({
                    respectParents: true
                  });
                </script>
              {/literal}
            </div>
            <div class="clear"></div>
          </div>
        {/if}

        {if $form.generic_id}
          <div class="crm-section generic-section">
            <div class="label">
              <label for="generic-select">{ts}Generic Skill(s){/ts}</label>
            </div>
            <div class="content" id="generic-select">
              {$form.generic_id.html}
              {literal}
                <script type="text/javascript">
                  cj("select#generic_id").crmasmSelect({
                    respectParents: true
                  });
                </script>
              {/literal}
            </div>
            <div class="clear"></div>
          </div>
        {/if}

        {if $form.language_id}
          <div class="crm-section language-section">
            <div class="label">
              <label for="language-select">{ts}Language(s){/ts}</label>
            </div>
            <div class="content" id="language-select">
              {$form.language_id.html}
              {literal}
                <script type="text/javascript">
                  cj("select#language_id").crmasmSelect({
                    respectParents: true
                  });
                </script>
              {/literal}
            </div>
            <div class="clear"></div>
          </div>
        {/if}

        {if $form.age_from or $form.age_to}
          <div class="crm-section age-range">
            <div class="label">
              <label for="age_from">{ts}Age Range from...{/ts}</label>
            </div>
            <div class="content" id="age-from">
              {$form.age_from.html}
            </div>
            <div class="clear"></div>
            <div class="label">
              <label for="age_to">{ts}...to{/ts}</label>
            </div>
            <div class="content" id="age-to">
              {$form.age_to.html}
            </div>
            <div class="clear"></div>
          </div>
        {/if}

        {if $form.gender_id}
          <div class="crm-section gender-section">
            <div class="label">
              <label for="gender-select">{ts}Gender(s){/ts}</label>
            </div>
            <div class="content" id="gender-select">
              {$form.gender_id.html}
              {literal}
                <script type="text/javascript">
                  cj("select#gender_id").crmasmSelect({
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
              <label for="group-select">{ts}Group(s){/ts}</label>
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

        {if $form.expert_status_id}
          <div class="crm-section expert-status-section">
            <div class="label">
              <label for="expert-status-select">{ts}Expert Status(es){/ts}</label>
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

        {if $form.cv_mutation_id}
          <fieldset><legend>{ts}CV in Mutation{/ts}</legend>
            <div class="crm-section cv-mutation-section">
              <div class="label">
                <label for="cv-mutation-radio">{ts}CV in Mutation{/ts}</label>
              </div>
              <div class="content" id="cv-mutation-radio">
                {$form.cv_mutation_id.html}
              </div>
              <div class="clear"></div>
            </div>
          </fieldset>
        {/if}

        {if $form.deceased_id}
          <fieldset><legend>{ts}What to do with Deceased Contacts{/ts}</legend>
            <div class="crm-section deceased-radio-section">
              <div class="label">
                <label for="deceased-radio">{ts}Include/Exclude{/ts}</label>
              </div>
              <div class="content" id="deceased-radio">
                {$form.deceased_id.html}
              </div>
              <div class="clear"></div>
            </div>
            <div class="crm-section deceased-date-from-section">
              <div class="label">
                <label for="deceased-date-from">{ts}Deceased Date from...{/ts}</label>
              </div>
              <div class="content" id="deceased-date-from">
                {include file="CRM/common/jcalendar.tpl" elementName='deceased_date_from'}
              </div>
              <div class="clear"></div>
            </div>
            <div class="crm-section deceased-date-to-section">
              <div class="label">
                <label for="deceased-date-to">{ts}to...{/ts}</label>
              </div>
              <div class="content" id="deceased-date-to">
                {include file="CRM/common/jcalendar.tpl" elementName='deceased_date_to'}
              </div>
              <div class="clear"></div>
            </div>
          </fieldset>
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


