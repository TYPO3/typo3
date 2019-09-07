/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

import * as $ from 'jquery';

interface FieldOptions {
  pageId: number;
  recordId: number;
  tableName: string;
  fieldName: string;
  config: { [key: string]: any };
  listenerFieldNames: { [key: string]: string };
  language: number;
  originalValue: string;
  signature: string;
  command: string;
  parentPageId: number;
}

interface Response {
  hasConflicts: boolean;
  manual: string;
  proposal: ProposalModes;
}

enum Selectors {
  toggleButton = '.t3js-form-field-slug-toggle',
  recreateButton = '.t3js-form-field-slug-recreate',
  inputField = '.t3js-form-field-slug-input',
  readOnlyField = '.t3js-form-field-slug-readonly',
  hiddenField = '.t3js-form-field-slug-hidden',
}

enum ProposalModes {
  AUTO = 'auto',
  RECREATE = 'recreate',
  MANUAL = 'manual',
}

/**
 * Module: TYPO3/CMS/Backend/FormEngine/Element/SlugElement
 * Logic for a TCA type "slug"
 *
 * For new records, changes on the other fields of the record (typically the record title) are listened
 * on as well and the response is put in as "placeholder" into the input field.
 *
 * For new and existing records, the toggle switch will allow editors to modify the slug
 *  - for new records, we only need to see if that is already in use or not (uniqueInSite), if it is taken, show a message.
 *  - for existing records, we also check for conflicts, and check if we have subpges, or if we want to add a redirect (todo)
 */
class SlugElement {
  private options: FieldOptions = null;
  private $fullElement: JQuery = null;
  private manuallyChanged: boolean = false;
  private $readOnlyField: JQuery = null;
  private $inputField: JQuery = null;
  private $hiddenField: JQuery = null;
  private xhr: JQueryXHR = null;
  private readonly fieldsToListenOn: { [key: string]: string } = {};

  constructor(selector: string, options: FieldOptions) {
    this.options = options;
    this.fieldsToListenOn = this.options.listenerFieldNames || {};

    $((): void => {
      this.$fullElement = $(selector);
      this.$inputField = this.$fullElement.find(Selectors.inputField);
      this.$readOnlyField = this.$fullElement.find(Selectors.readOnlyField);
      this.$hiddenField = this.$fullElement.find(Selectors.hiddenField);

      this.registerEvents();
    });
  }

  private registerEvents(): void {
    const fieldsToListenOnList = Object.keys(this.getAvailableFieldsForProposalGeneration()).map(k => this.fieldsToListenOn[k]);

    // Listen on 'listenerFieldNames' for new pages. This is typically the 'title' field
    // of a page to create slugs from the title when title is set / changed.
    if (fieldsToListenOnList.length > 0) {
      if (this.options.command === 'new') {
        $(this.$fullElement).on('keyup', fieldsToListenOnList.join(','), (): void => {
          if (!this.manuallyChanged) {
            this.sendSlugProposal(ProposalModes.AUTO);
          }
        });
      }

      // Clicking the recreate button makes new slug proposal created from 'title' field
      $(this.$fullElement).on('click', Selectors.recreateButton, (e): void => {
        e.preventDefault();
        if (this.$readOnlyField.hasClass('hidden')) {
          // Switch to readonly version - similar to 'new' page where field is
          // written on the fly with title change
          this.$readOnlyField.toggleClass('hidden', false);
          this.$inputField.toggleClass('hidden', true);
        }
        this.sendSlugProposal(ProposalModes.RECREATE);
      });
    } else {
      $(this.$fullElement).find(Selectors.recreateButton).addClass('disabled').prop('disabled', true);
    }

    // Scenario for new pages: Usually, slug is created from the page title. However, if user toggles the
    // input field and feeds an own slug, and then changes title again, the slug should stay. manuallyChanged
    // is used to track this.
    $(this.$inputField).on('keyup', (): void => {
      this.manuallyChanged = true;
      this.sendSlugProposal(ProposalModes.MANUAL);
    });

    // Clicking the toggle button toggles the read only field and the input field.
    // Also set the value of either the read only or the input field to the hidden field
    // and update the value of the read only field after manual change of the input field.
    $(this.$fullElement).on('click', Selectors.toggleButton, (e): void => {
      e.preventDefault();
      const showReadOnlyField = this.$readOnlyField.hasClass('hidden');
      this.$readOnlyField.toggleClass('hidden', !showReadOnlyField);
      this.$inputField.toggleClass('hidden', showReadOnlyField);
      if (!showReadOnlyField) {
        this.$hiddenField.val(this.$inputField.val());
        return;
      }
      if (this.$inputField.val() !== this.$readOnlyField.val()) {
        this.$readOnlyField.val(this.$inputField.val());
      } else {
        this.manuallyChanged = false;
        this.$fullElement.find('.t3js-form-proposal-accepted').addClass('hidden');
        this.$fullElement.find('.t3js-form-proposal-different').addClass('hidden');
      }
      this.$hiddenField.val(this.$readOnlyField.val());
    });
  }

  /**
   * @param {ProposalModes} mode
   */
  private sendSlugProposal(mode: ProposalModes): void {
    const input: { [key: string]: string } = {};
    if (mode === ProposalModes.AUTO || mode === ProposalModes.RECREATE) {
      $.each(this.getAvailableFieldsForProposalGeneration(), (fieldName: string, field: string): void => {
        input[fieldName] = $('[data-formengine-input-name="' + field + '"]').val();
      });
    } else {
      input.manual = this.$inputField.val();
    }
    if (this.xhr !== null && this.xhr.readyState !== 4) {
      this.xhr.abort();
    }
    this.xhr = $.post(
      TYPO3.settings.ajaxUrls.record_slug_suggest,
      {
        values: input,
        mode: mode,
        tableName: this.options.tableName,
        pageId: this.options.pageId,
        parentPageId: this.options.parentPageId,
        recordId: this.options.recordId,
        language: this.options.language,
        fieldName: this.options.fieldName,
        command: this.options.command,
        signature: this.options.signature,
      },
      (response: Response): void => {
        if (response.hasConflicts) {
          this.$fullElement.find('.t3js-form-proposal-accepted').addClass('hidden');
          this.$fullElement.find('.t3js-form-proposal-different').removeClass('hidden').find('span').text(response.proposal);
        } else {
          this.$fullElement.find('.t3js-form-proposal-accepted').removeClass('hidden').find('span').text(response.proposal);
          this.$fullElement.find('.t3js-form-proposal-different').addClass('hidden');
        }
        const isChanged = this.$hiddenField.val() !== response.proposal;
        if (isChanged) {
          this.$fullElement.find('input').trigger('change');
        }
        if (mode === ProposalModes.AUTO || mode === ProposalModes.RECREATE) {
          this.$readOnlyField.val(response.proposal);
          this.$hiddenField.val(response.proposal);
          this.$inputField.val(response.proposal);
        } else {
          this.$hiddenField.val(response.proposal);
        }
      },
      'json',
    );
  }

  /**
   * Gets a list of all available fields that can be used for slug generation
   *
   * @return { [key: string]: string }
   */
  private getAvailableFieldsForProposalGeneration(): { [key: string]: string } {
    const availableFields: { [key: string]: string } = {};

    $.each(this.fieldsToListenOn, (fieldName: string, field: string): void => {
      const $selector = $('[data-formengine-input-name="' + field + '"]');
      if ($selector.length > 0) {
        availableFields[fieldName] = field;
      }
    });

    return availableFields;
  }
}

export = SlugElement;
