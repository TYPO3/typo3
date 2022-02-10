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

import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import DocumentService from '@typo3/core/document-service';
import DebounceEvent from '@typo3/core/event/debounce-event';
import RegularEvent from '@typo3/core/event/regular-event';

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
  includeUidInValues: boolean;
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
 * Module: @typo3/backend/form-engine/element/slug-element
 * Logic for a TCA type "slug"
 *
 * For new records, changes on the other fields of the record (typically the record title) are listened
 * on as well and the response is put in as "placeholder" into the input field.
 *
 * For new and existing records, the toggle switch will allow editors to modify the slug
 *  - for new records, we only need to see if that is already in use or not (uniqueInSite), if it is taken, show a message.
 *  - for existing records, we also check for conflicts, and check if we have subpages, or if we want to add a redirect (todo)
 */
class SlugElement {
  private options: FieldOptions = null;
  private fullElement: HTMLElement = null;
  private manuallyChanged: boolean = false;
  private readOnlyField: HTMLInputElement = null;
  private inputField: HTMLInputElement = null;
  private hiddenField: HTMLInputElement = null;
  private request: AjaxRequest = null;
  private readonly fieldsToListenOn: { [key: string]: string } = {};

  constructor(selector: string, options: FieldOptions) {
    this.options = options;
    this.fieldsToListenOn = this.options.listenerFieldNames || {};

    DocumentService.ready().then((document: Document): void => {
      this.fullElement = document.querySelector(selector);
      this.inputField = this.fullElement.querySelector(Selectors.inputField);
      this.readOnlyField = this.fullElement.querySelector(Selectors.readOnlyField);
      this.hiddenField = this.fullElement.querySelector(Selectors.hiddenField);

      this.registerEvents();
    });
  }

  private registerEvents(): void {
    const fieldsToListenOnList = Object.values(this.getAvailableFieldsForProposalGeneration()).map((selector: string) => `[data-formengine-input-name="${selector}"]`);
    const recreateButton: HTMLButtonElement = this.fullElement.querySelector(Selectors.recreateButton);

    // Listen on 'listenerFieldNames' for new pages. This is typically the 'title' field
    // of a page to create slugs from the title when title is set / changed.
    if (fieldsToListenOnList.length > 0) {
      if (this.options.command === 'new') {
        new DebounceEvent('input', (): void => {
          if (!this.manuallyChanged) {
            this.sendSlugProposal(ProposalModes.AUTO);
          }
        }).delegateTo(document, fieldsToListenOnList.join(','));
      }
    }

    // Clicking the recreate button makes new slug proposal created from 'title' field or any defined postModifiers
    if (fieldsToListenOnList.length > 0 || this.hasPostModifiersDefined()) {
      new RegularEvent('click', (e: Event): void => {
        e.preventDefault();
        if (this.readOnlyField.classList.contains('hidden')) {
          // Switch to readonly version - similar to 'new' page where field is
          // written on the fly with title change
          this.readOnlyField.classList.toggle('hidden', false);
          this.inputField.classList.toggle('hidden', true);
        }
        this.sendSlugProposal(ProposalModes.RECREATE);
      }).bindTo(recreateButton);
    } else {
      recreateButton.classList.add('disabled');
      recreateButton.disabled = true;
    }

    // Scenario for new pages: Usually, slug is created from the page title. However, if user toggles the
    // input field and feeds an own slug, and then changes title again, the slug should stay. manuallyChanged
    // is used to track this.
    new DebounceEvent('input', (): void => {
      this.manuallyChanged = true;
      this.sendSlugProposal(ProposalModes.MANUAL);
    }).bindTo(this.inputField);

    // Clicking the toggle button toggles the read only field and the input field.
    // Also set the value of either the read only or the input field to the hidden field
    // and update the value of the read only field after manual change of the input field.
    const toggleButton = this.fullElement.querySelector(Selectors.toggleButton);
    new RegularEvent('click', (e: Event): void => {
      e.preventDefault();
      const showReadOnlyField = this.readOnlyField.classList.contains('hidden');
      this.readOnlyField.classList.toggle('hidden', !showReadOnlyField);
      this.inputField.classList.toggle('hidden', showReadOnlyField);
      if (!showReadOnlyField) {
        this.hiddenField.value = this.inputField.value;
        return;
      }
      if (this.inputField.value !== this.readOnlyField.value) {
        this.readOnlyField.value = this.inputField.value;
      } else {
        this.manuallyChanged = false;
        this.fullElement.querySelector('.t3js-form-proposal-accepted').classList.add('hidden');
        this.fullElement.querySelector('.t3js-form-proposal-different').classList.add('hidden');
      }
      this.hiddenField.value = this.readOnlyField.value;
    }).bindTo(toggleButton);
  }

  /**
   * @param {ProposalModes} mode
   */
  private sendSlugProposal(mode: ProposalModes): void {
    const input: { [key: string]: string } = {};
    if (mode === ProposalModes.AUTO || mode === ProposalModes.RECREATE) {
      for (const [fieldName, selector] of Object.entries(this.getAvailableFieldsForProposalGeneration())) {
        input[fieldName] = (document.querySelector('[data-formengine-input-name="' + selector + '"]') as HTMLInputElement).value;
      }
      if (this.options.includeUidInValues === true) {
        input.uid = this.options.recordId.toString();
      }
    } else {
      input.manual = this.inputField.value;
    }
    if (this.request instanceof AjaxRequest) {
      this.request.abort();
    }
    this.request = (new AjaxRequest(TYPO3.settings.ajaxUrls.record_slug_suggest));
    this.request.post({
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
    }).then(async (response: AjaxResponse): Promise<any> => {
      const data: Response = await response.resolve();
      const visualProposal = '/' + data.proposal.replace(/^\//, '');
      const acceptedProposalField: HTMLElement = this.fullElement.querySelector('.t3js-form-proposal-accepted');
      const differentProposalField: HTMLElement = this.fullElement.querySelector('.t3js-form-proposal-different');

      acceptedProposalField.classList.toggle('hidden', data.hasConflicts);
      differentProposalField.classList.toggle('hidden', !data.hasConflicts);
      (data.hasConflicts ? differentProposalField : acceptedProposalField).querySelector('span').innerText = visualProposal;

      const isChanged = this.hiddenField.value !== data.proposal;
      if (isChanged) {
        this.fullElement.querySelector('input[data-formengine-input-name]')
          .dispatchEvent(new Event('change', {bubbles: true, cancelable: true}));
      }
      if (mode === ProposalModes.AUTO || mode === ProposalModes.RECREATE) {
        this.readOnlyField.value = data.proposal;
        this.hiddenField.value = data.proposal;
        this.inputField.value = data.proposal;
      } else {
        this.hiddenField.value = data.proposal;
      }
    }).finally((): void => {
      this.request = null;
    });
  }

  /**
   * Gets a list of all available fields that can be used for slug generation
   *
   * @return { [key: string]: string }
   */
  private getAvailableFieldsForProposalGeneration(): { [key: string]: string } {
    const availableFields: { [key: string]: string } = {};
    for (const [fieldName, selector] of Object.entries(this.fieldsToListenOn)) {
      const field = document.querySelector('[data-formengine-input-name="' + selector + '"]');
      if (field !== null) {
        availableFields[fieldName] = selector;
      }
    }

    return availableFields;
  }

  /**
   * Check whether the slug element has post modifiers defined for slug generation
   *
   * @return boolean
   */
  private hasPostModifiersDefined(): boolean {
    return Array.isArray(this.options.config.generatorOptions.postModifiers) && this.options.config.generatorOptions.postModifiers.length > 0;
  }
}

export default SlugElement;
