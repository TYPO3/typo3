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

import AjaxRequest from '@typo3/core/ajax/ajax-request';
import {MessageUtility} from '../../utility/message-utility';
import {AjaxDispatcher} from './../inline-relation/ajax-dispatcher';
import {InlineResponseInterface} from './../inline-relation/inline-response-interface';
import NProgress from 'nprogress';
import FormEngine from '@typo3/backend/form-engine';
import FormEngineValidation from '@typo3/backend/form-engine-validation';
import Modal from '../../modal';
import Notification from '../../notification';
import RegularEvent from '@typo3/core/event/regular-event';
import Severity from '../../severity';
import Utility from '../../utility';

enum Selectors {
  toggleSelector = '[data-bs-toggle="formengine-inline"]',
  controlSectionSelector = '.t3js-formengine-irre-control',
  createNewRecordButtonSelector = '.t3js-create-new-button',
  createNewRecordBySelectorSelector = '.t3js-create-new-selector',
  deleteRecordButtonSelector = '.t3js-editform-delete-inline-record',
}

enum States {
  new = 'inlineIsNewRecord',
  visible = 'panel-visible',
  collapsed = 'panel-collapsed',
  notLoaded = 't3js-not-loaded',
}

enum Separators {
  structureSeparator = '-',
}

interface RequestQueue {
  [key: string]: AjaxRequest;
}

interface ProgressQueue {
  [key: string]: any;
}

interface UniqueDefinition {
  elTable: string;
  field: string;
  max: number;
  possible: { [key: string]: string };
  table: string;
  used: UniqueDefinitionCollection;
}

interface UniqueDefinitionCollection {
  [key: string]: UniqueDefinitionUsed;
}

interface UniqueDefinitionUsed {
  table: string;
  uid: string | number;
}

/**
 * Module: @typo3/backend/form-engine/container/site-language-container
 *
 * Functionality for the site language container
 *
 * @example
 * <typo3-formengine-container-sitelanguage identifier="some-id">
 *   ...
 * </typo3-formengine-container-sitelanguage>
 *
 * This is based on W3C custom elements ("web components") specification, see
 * https://developer.mozilla.org/en-US/docs/Web/Web_Components/Using_custom_elements
 */
class SiteLanguageContainer extends HTMLElement {
  private container: HTMLElement = null;
  private ajaxDispatcher: AjaxDispatcher = null;
  private requestQueue: RequestQueue = {};
  private progessQueue: ProgressQueue = {};

  private static getInlineRecordContainer(objectId: string): HTMLDivElement {
    return <HTMLDivElement>document.querySelector('[data-object-id="' + objectId + '"]');
  }

  private static getValuesFromHashMap(hashmap: UniqueDefinitionCollection): Array<any> {
    return Object.keys(hashmap).map((key: string) => hashmap[key]);
  }

  private static selectOptionValueExists(selectElement: HTMLSelectElement, value: string): boolean {
    return selectElement.querySelector('option[value="' + value + '"]') !== null;
  }

  private static removeSelectOptionByValue(selectElement: HTMLSelectElement, value: string): void {
    const option = selectElement.querySelector('option[value="' + value + '"]');
    if (option !== null) {
      option.remove();
    }
  }

  private static reAddSelectOption(selectElement: HTMLSelectElement, value: string, unique: UniqueDefinition): void {
    if (SiteLanguageContainer.selectOptionValueExists(selectElement, value)) {
      return;
    }

    const options: NodeListOf<HTMLOptionElement> = selectElement.querySelectorAll('option');
    let index: number = -1;

    for (let possibleValue of Object.keys(unique.possible)) {
      if (possibleValue === value) {
        break;
      }

      for (let i = 0; i < options.length; ++i) {
        const option = options[i];
        if (option.value === possibleValue) {
          index = i;
          break;
        }
      }
    }

    if (index === -1) {
      index = 0;
    } else if (index < options.length) {
      index++;
    }

    const readdOption = document.createElement('option');
    readdOption.text = unique.possible[value];
    readdOption.value = value;
    selectElement.insertBefore(readdOption, selectElement.options[index]);
  }

  private static collapseExpandRecord(objectId: string): void {
    const recordContainer = SiteLanguageContainer.getInlineRecordContainer(objectId);
    const collapseButton = <HTMLButtonElement>document.querySelector('[aria-controls="' + objectId + '_fields"]');
    if (recordContainer.classList.contains(States.collapsed)) {
      recordContainer.classList.remove(States.collapsed);
      recordContainer.classList.add(States.visible);
      collapseButton.setAttribute('aria-expanded', 'true');
    } else {
      recordContainer.classList.remove(States.visible);
      recordContainer.classList.add(States.collapsed);
      collapseButton.setAttribute('aria-expanded', 'false');
    }
  }

  public connectedCallback(): void {
    const identifier = this.getAttribute('identifier') || '' as string;
    this.container = <HTMLElement>this.querySelector('#' + identifier);

    if (this.container !== null) {
      this.ajaxDispatcher = new AjaxDispatcher(this.container.dataset.objectGroup);
      this.registerEvents();
    }
  }

  private registerEvents(): void {
    this.registerCreateRecordButton();
    this.registerCreateRecordBySelector();
    this.registerRecordToggle();
    this.registerDeleteButton();

    new RegularEvent('message', this.handlePostMessage).bindTo(window);
  }

  private registerCreateRecordButton(): void {
    const me = this;
    new RegularEvent('click', function(this: HTMLElement, e: Event) {
      e.preventDefault();
      e.stopImmediatePropagation();

      let objectId = me.container.dataset.objectGroup;
      if (typeof this.dataset.recordUid !== 'undefined') {
        objectId += Separators.structureSeparator + this.dataset.recordUid;
      }

      me.importRecord([objectId, (me.container.querySelector(Selectors.createNewRecordBySelectorSelector) as HTMLInputElement)?.value], this.dataset.recordUid ?? null);
    }).delegateTo(this.container, Selectors.createNewRecordButtonSelector);
  }

  private registerCreateRecordBySelector(): void {
    const me = this;
    new RegularEvent('change', function(this: HTMLElement, e: Event) {
      e.preventDefault();
      e.stopImmediatePropagation();

      const selectTarget = <HTMLSelectElement>this;
      const recordUid = selectTarget.options[selectTarget.selectedIndex].getAttribute('value');

      me.importRecord([me.container.dataset.objectGroup, recordUid]);
    }).delegateTo(this.container, Selectors.createNewRecordBySelectorSelector);
  }

  private registerRecordToggle(): void {
    const me = this;
    new RegularEvent('click', function(this: HTMLElement, e: Event) {
      e.preventDefault();
      e.stopImmediatePropagation();

      me.loadRecordDetails(this.closest(Selectors.toggleSelector).parentElement.dataset.objectId);
    }).delegateTo(this.container, `${Selectors.toggleSelector} .form-irre-header-cell:not(${Selectors.controlSectionSelector}`);
  }

  private registerDeleteButton(): void {
    const me = this;
    new RegularEvent('click', function(this: HTMLElement, e: Event) {
      e.preventDefault();
      e.stopImmediatePropagation();

      const title = TYPO3.lang['label.confirm.delete_record.title'] || 'Delete this record?';
      const content = TYPO3.lang['label.confirm.delete_record.content'] || 'Are you sure you want to delete this record?';
      Modal.confirm(title, content, Severity.warning, [
        {
          text: TYPO3.lang['buttons.confirm.delete_record.no'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'no',
          trigger: (): void => {
            Modal.currentModal.trigger('modal-dismiss');
          }
        },
        {
          text: TYPO3.lang['buttons.confirm.delete_record.yes'] || 'Yes, delete this record',
          btnClass: 'btn-warning',
          name: 'yes',
          trigger: (): void => {
            me.deleteRecord((<HTMLDivElement>this.closest('[data-object-id]')).dataset.objectId);
            Modal.currentModal.trigger('modal-dismiss');
          }
        },
      ]);
    }).delegateTo(this.container, Selectors.deleteRecordButtonSelector);
  }

  private handlePostMessage = (e: MessageEvent): void => {
    if (!MessageUtility.verifyOrigin(e.origin)) {
      throw 'Denied message sent by ' + e.origin;
    }

    if (e.data.actionName === 'typo3:foreignRelation:insert') {
      if (typeof e.data.objectGroup === 'undefined') {
        throw 'No object group defined for message';
      }

      if (e.data.objectGroup !== this.container.dataset.objectGroup) {
        // Received message isn't provisioned for currentSiteLanguageContainer instance
        return;
      }

      if (this.isUniqueElementUsed(parseInt(e.data.uid, 10))) {
        Notification.error('There is already a relation to the selected element');
        return;
      }

      this.importRecord([e.data.objectGroup, e.data.uid]).then((): void => {
        if (e.source) {
          const message = {
            actionName: 'typo3:foreignRelation:inserted',
            objectGroup: e.data.objectId,
            table: e.data.table,
            uid: e.data.uid,
          };
          MessageUtility.send(message, e.source as Window);
        }
      });
    }
  }

  private createRecord(uid: string, markup: string, afterUid: string = null, selectedValue: string = null): void {
    let objectId = this.container.dataset.objectGroup;
    if (afterUid !== null) {
      objectId += Separators.structureSeparator + afterUid;
      SiteLanguageContainer.getInlineRecordContainer(objectId).insertAdjacentHTML('afterend', markup);
      this.memorizeAddRecord(uid, afterUid, selectedValue);
    } else {
      document.getElementById(this.container.getAttribute('id') + '_records').insertAdjacentHTML('beforeend', markup);
      this.memorizeAddRecord(uid, null, selectedValue);
    }
  }

  private async importRecord(params: Array<any>, afterUid?: string): Promise<void> {
    return this.ajaxDispatcher.send(
      this.ajaxDispatcher.newRequest(this.ajaxDispatcher.getEndpoint('site_configuration_inline_create')),
      params,
    ).then(async (response: InlineResponseInterface): Promise<void> => {
      this.createRecord(
        response.compilerInput.uid,
        response.data,
        typeof afterUid !== 'undefined' ? afterUid : null,
        typeof response.compilerInput.childChildUid !== 'undefined' ? response.compilerInput.childChildUid : null,
      );
    });
  }

  private loadRecordDetails(objectId: string): void {
    const recordFieldsContainer = document.getElementById(objectId + '_fields');
    const recordContainer = SiteLanguageContainer.getInlineRecordContainer(objectId);
    const isLoading = typeof this.requestQueue[objectId] !== 'undefined';
    const isLoaded = recordFieldsContainer !== null && !recordContainer.classList.contains(States.notLoaded);

    if (!isLoaded) {
      const progress = this.getProgress(objectId, recordContainer.dataset.objectIdHash);

      if (!isLoading) {
        const ajaxRequest = this.ajaxDispatcher.newRequest(this.ajaxDispatcher.getEndpoint('site_configuration_inline_details'));
        const request = this.ajaxDispatcher.send(ajaxRequest, [objectId]);

        request.then(async (response: InlineResponseInterface): Promise<any> => {
          delete this.requestQueue[objectId];
          delete this.progessQueue[objectId];

          recordContainer.classList.remove(States.notLoaded);
          recordFieldsContainer.innerHTML = response.data;
          SiteLanguageContainer.collapseExpandRecord(objectId);

          progress.done();

          FormEngine.reinitialize();
          FormEngineValidation.initializeInputFields();
          FormEngineValidation.validate(this.container);

          this.removeUsed(SiteLanguageContainer.getInlineRecordContainer(objectId));
        });

        this.requestQueue[objectId] = ajaxRequest;
        progress.start();
      } else {
        // Abort loading if collapsed again
        this.requestQueue[objectId].abort();
        delete this.requestQueue[objectId];
        delete this.progessQueue[objectId];
        progress.done();
      }

      return;
    }

    SiteLanguageContainer.collapseExpandRecord(objectId);
  }

  private memorizeAddRecord(newUid: string, afterUid: string = null, selectedValue: string = null): void {
    const formField = this.getFormFieldForElements();
    if (formField === null) {
      return;
    }

    let records = Utility.trimExplode(',', (<HTMLInputElement>formField).value);
    if (afterUid) {
      const newRecords = [];
      for (let i = 0; i < records.length; i++) {
        if (records[i].length) {
          newRecords.push(records[i]);
        }
        if (afterUid === records[i]) {
          newRecords.push(newUid);
        }
      }
      records = newRecords;
    } else {
      records.push(newUid);
    }

    (<HTMLInputElement>formField).value = records.join(',');
    (<HTMLInputElement>formField).classList.add('has-change');
    document.dispatchEvent(new Event('change'));

    this.setUnique(newUid, selectedValue);

    FormEngine.reinitialize();
    FormEngineValidation.initializeInputFields();
    FormEngineValidation.validate(this.container);
  }

  private memorizeRemoveRecord(objectUid: string): Array<string> {
    const formField = this.getFormFieldForElements();
    if (formField === null) {
      return [];
    }

    let records = Utility.trimExplode(',', (<HTMLInputElement>formField).value);
    const indexOfRemoveUid = records.indexOf(objectUid);
    if (indexOfRemoveUid > -1) {
      delete records[indexOfRemoveUid];

      (<HTMLInputElement>formField).value = records.join(',');
      (<HTMLInputElement>formField).classList.add('has-change');
      document.dispatchEvent(new Event('change'));
    }

    return records;
  }

  private deleteRecord(objectId: string, forceDirectRemoval: boolean = false): void {
    const recordContainer = SiteLanguageContainer.getInlineRecordContainer(objectId);
    const objectUid = recordContainer.dataset.objectUid;

    recordContainer.classList.add('t3js-inline-record-deleted');

    if (!recordContainer.classList.contains(States.new) && !forceDirectRemoval) {
      const deleteCommandInput = this.container.querySelector('[name="cmd' + recordContainer.dataset.fieldName + '[delete]"]');
      deleteCommandInput.removeAttribute('disabled');

      // Move input field to inline container so we can remove the record container
      recordContainer.parentElement.insertAdjacentElement('afterbegin', deleteCommandInput);
    }

    new RegularEvent('transitionend', (): void => {
      recordContainer.parentElement.removeChild(recordContainer);
      FormEngineValidation.validate(this.container);
    }).bindTo(recordContainer);

    this.revertUnique(objectUid);
    this.memorizeRemoveRecord(objectUid);
    recordContainer.classList.add('form-irre-object--deleted');
  }

  private getProgress(objectId: string, objectIdHash: string): any {
    const headerIdentifier = '#' + objectIdHash + '_header';
    let progress: any;

    if (typeof this.progessQueue[objectId] !== 'undefined') {
      progress = this.progessQueue[objectId];
    } else {
      progress = NProgress;
      progress.configure({parent: headerIdentifier, showSpinner: false});
      this.progessQueue[objectId] = progress;
    }

    return progress;
  }

  private getFormFieldForElements(): HTMLInputElement | null {
    const formFields = document.getElementsByName(this.container.dataset.formField);
    if (formFields.length > 0) {
      return <HTMLInputElement>formFields[0];
    }

    return null;
  }

  private isUniqueElementUsed(uid: number): boolean {
    const unique: UniqueDefinition = TYPO3.settings.FormEngineInline.unique[this.container.dataset.objectGroup];
    return SiteLanguageContainer.getValuesFromHashMap(unique.used).indexOf(uid) !== -1;
  }

  private removeUsed(recordContainer: HTMLDivElement): void {
    const unique: UniqueDefinition = TYPO3.settings.FormEngineInline.unique[this.container.dataset.objectGroup];
    const values = SiteLanguageContainer.getValuesFromHashMap(unique.used);

    let uniqueValueField = <HTMLSelectElement>recordContainer.querySelector(
      '[name="data[' + unique.table + '][' + recordContainer.dataset.objectUid + '][' + unique.field + ']"]',
    );

    if (uniqueValueField !== null) {
      const selectedValue = uniqueValueField.options[uniqueValueField.selectedIndex].value;
      for (let value of values) {
        if (value !== selectedValue) {
          SiteLanguageContainer.removeSelectOptionByValue(uniqueValueField, value);
        }
      }
    }
  }

  private setUnique(recordUid: string, selectedValue: string): void {
    const unique: UniqueDefinition = TYPO3.settings.FormEngineInline.unique[this.container.dataset.objectGroup];
    const selectorElement: HTMLSelectElement = <HTMLSelectElement>document.getElementById(
      this.container.dataset.objectGroup + '_selector',
    );
    if (unique.max !== -1) {
      const formField = this.getFormFieldForElements();
      const recordObjectId = this.container.dataset.objectGroup + Separators.structureSeparator + recordUid;
      const recordContainer = SiteLanguageContainer.getInlineRecordContainer(recordObjectId);
      let uniqueValueField = <HTMLSelectElement>recordContainer.querySelector(
        '[name="data[' + unique.table + '][' + recordUid + '][' + unique.field + ']"]',
      );
      const values = SiteLanguageContainer.getValuesFromHashMap(unique.used);
      if (selectorElement !== null) {
        // remove all items from the new select-item which are already used in other children
        if (uniqueValueField !== null) {
          for (let value of values) {
            SiteLanguageContainer.removeSelectOptionByValue(uniqueValueField, value);
          }
        }
        for (let value of values) {
          SiteLanguageContainer.removeSelectOptionByValue(uniqueValueField, value);
        }
        if (typeof unique.used.length !== 'undefined') {
          unique.used = {};
        }
        unique.used[recordUid] = {
          table: unique.elTable,
          uid: selectedValue,
        };
      }
      // remove the newly used item from each select-field of the child records
      if (formField !== null && SiteLanguageContainer.selectOptionValueExists(selectorElement, selectedValue)) {
        const records = Utility.trimExplode(',', (<HTMLInputElement>formField).value);
        for (let record of records) {
          uniqueValueField = <HTMLSelectElement>document.querySelector(
            '[name="data[' + unique.table + '][' + record + '][' + unique.field + ']"]',
          );
          if (uniqueValueField !== null && record !== recordUid) {
            SiteLanguageContainer.removeSelectOptionByValue(uniqueValueField, selectedValue);
          }
        }
      }
    }

    // remove used items from the selector
    if (SiteLanguageContainer.selectOptionValueExists(selectorElement, selectedValue)) {
      SiteLanguageContainer.removeSelectOptionByValue(selectorElement, selectedValue);
      unique.used[recordUid] = {
        table: unique.elTable,
        uid: selectedValue,
      };
    }
  }

  private revertUnique(recordUid: string): void {
    const unique = TYPO3.settings.FormEngineInline.unique[this.container.dataset.objectGroup];
    const recordObjectId = this.container.dataset.objectGroup + Separators.structureSeparator + recordUid;
    const recordContainer = SiteLanguageContainer.getInlineRecordContainer(recordObjectId);

    let uniqueValueField = <HTMLSelectElement>recordContainer.querySelector(
      '[name="data[' + unique.table + '][' + recordContainer.dataset.objectUid + '][' + unique.field + ']"]',
    );
    let uniqueValue;
    if (uniqueValueField !== null) {
      uniqueValue = uniqueValueField.value;
    } else if (recordContainer.dataset.tableUniqueOriginalValue !== '') {
      uniqueValue = recordContainer.dataset.tableUniqueOriginalValue.replace(unique.table + '_', '');
    } else {
      return;
    }

    // 9223372036854775807 is the PHP_INT_MAX placeholder, used to allow creation of new records.
    // This option however should never be displayed in the selector box at is therefore checked.
    if (!isNaN(parseInt(uniqueValue, 10)) && parseInt(uniqueValue, 10) !== 9223372036854775807) {
      const selectorElement: HTMLSelectElement = <HTMLSelectElement>document.getElementById(
        this.container.dataset.objectGroup + '_selector',
      );
      SiteLanguageContainer.reAddSelectOption(selectorElement, uniqueValue, unique);
    }

    if (unique.max === -1) {
      return;
    }

    const formField = this.getFormFieldForElements();
    if (formField === null) {
      return;
    }

    const records = Utility.trimExplode(',', formField.value);
    let recordObj;
    // walk through all records on that level and get the select field
    for (let i = 0; i < records.length; i++) {
      recordObj = <HTMLSelectElement>document.querySelector(
        '[name="data[' + unique.table + '][' + records[i] + '][' + unique.field + ']"]',
      );
      if (recordObj !== null) {
        SiteLanguageContainer.reAddSelectOption(recordObj, uniqueValue, unique);
      }
    }

    delete unique.used[recordUid];
  }
}

window.customElements.define('typo3-formengine-container-sitelanguage', SiteLanguageContainer);
