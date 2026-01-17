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

import { MessageUtility } from '../../utility/message-utility';
import { Collapse } from 'bootstrap';
import { AjaxDispatcher } from './../inline-relation/ajax-dispatcher';
import DocumentService from '@typo3/core/document-service';
import { ProgressBarElement } from '@typo3/backend/element/progress-bar-element';
import Sortable from 'sortablejs';
import FormEngine from '@typo3/backend/form-engine';
import FormEngineValidation from '@typo3/backend/form-engine-validation';
import Icons from '../../icons';
import InfoWindow from '../../info-window';
import Modal from '../../modal';
import Notification from '../../notification';
import RegularEvent from '@typo3/core/event/regular-event';
import Severity from '../../severity';
import Utility from '../../utility';
import { selector } from '@typo3/core/literals';
import type AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { InlineResponseInterface } from './../inline-relation/inline-response-interface';
import backendAltDocLabels from '~labels/backend.alt_doc';
import coreCoreLabels from '~labels/core.core';

enum Selectors {
  controlSectionSelector = '.t3js-formengine-irre-control',
  createNewRecordButtonSelector = '.t3js-create-new-button',
  createNewRecordBySelectorSelector = '.t3js-create-new-selector',
  createNewRecordByPresetSelector = '.t3js-create-new-preset',
  deleteRecordButtonSelector = '.t3js-editform-delete-inline-record',
  enableDisableRecordButtonSelector = '.t3js-toggle-visibility-button',
  infoWindowButton = '[data-action="infowindow"]',
  synchronizeLocalizeRecordButtonSelector = '.t3js-synchronizelocalize-button',
  uniqueValueSelectors = 'select.t3js-inline-unique',
  revertUniqueness = '.t3js-revert-unique',
  controlContainer = '.t3js-inline-controls',
}

enum States {
  new = 'inlineIsNewRecord',
  notLoaded = 't3js-not-loaded',
}

enum Separators {
  structureSeparator = '-',
}

enum SortDirections {
  DOWN = 'down',
  UP = 'up',
}

interface RequestQueue {
  [key: string]: AjaxRequest;
}

interface ProgressQueue {
  [key: string]: ProgressBarElement;
}

export interface UniqueDefinition {
  elTable: string;
  field: string;
  max: number;
  possible: Record<string, string>;
  selector: string;
  table: string;
  type: string;
  used: UniqueDefinitionCollection;
}

interface UniqueDefinitionCollection {
  [key: string]: UniqueDefinitionUsed;
}

interface UniqueDefinitionUsed {
  table: string;
  uid: string | number;
}

interface InlineEndpoints {
  create: string;
  details: string;
  synchronizelocalize: string | null;
  expandcollapse: string | null;
}

class InlineControlContainer extends HTMLElement {
  private ajaxDispatcher: AjaxDispatcher = null;
  private recordsContainer: HTMLDivElement = null;
  private requestQueue: RequestQueue = {};
  private progressQueue: ProgressQueue = {};
  private readonly noTitleString: string = (coreCoreLabels.get('labels.no_title'));

  public get objectGroup(): string {
    return this.dataset.objectGroup;
  }

  public get formField(): string {
    return this.dataset.formField;
  }

  public get expandSingle(): boolean {
    return this.dataset.expandSingle === 'true';
  }

  public get sortable(): boolean {
    return this.dataset.sortable === 'true';
  }

  public get min(): number {
    return parseInt(this.dataset.min, 10) || 0;
  }

  public get max(): number {
    return parseInt(this.dataset.max, 10) || 0;
  }

  public get type(): 'record' | 'file' | 'language' {
    const type = this.dataset.type;
    if (type === 'file' || type === 'language') {
      return type;
    }
    return 'record';
  }

  private get endpoints(): InlineEndpoints {
    switch (this.type) {
      case 'file':
        return {
          create: 'file_reference_create',
          details: 'file_reference_details',
          synchronizelocalize: 'file_reference_synchronizelocalize',
          expandcollapse: 'file_reference_expandcollapse',
        };
      case 'language':
        return {
          create: 'site_configuration_inline_create',
          details: 'site_configuration_inline_details',
          synchronizelocalize: null,
          expandcollapse: null,
        };
      default:
        return {
          create: 'record_inline_create',
          details: 'record_inline_details',
          synchronizelocalize: 'record_inline_synchronizelocalize',
          expandcollapse: 'record_inline_expandcollapse',
        };
    }
  }

  private static getValuesFromHashMap(hashmap: UniqueDefinitionCollection): Array<any> {
    return Object.keys(hashmap).map((key: string) => hashmap[key]);
  }

  private static selectOptionValueExists(selectElement: HTMLSelectElement, value: string): boolean {
    return selectElement.querySelector(selector`option[value="${value}"]`) !== null;
  }

  private static removeSelectOptionByValue(selectElement: HTMLSelectElement, value: string): void {
    const option = selectElement.querySelector(selector`option[value="${value}"]`);
    if (option !== null) {
      option.remove();
    }
  }

  private static reAddSelectOption(selectElement: HTMLSelectElement, value: string, unique: UniqueDefinition): void {
    if (InlineControlContainer.selectOptionValueExists(selectElement, value)) {
      return;
    }

    const options: NodeListOf<HTMLOptionElement> = selectElement.querySelectorAll('option');
    let index: number = -1;

    for (const possibleValue of Object.keys(unique.possible)) {
      if (possibleValue === value) {
        break;
      }

      for (let k = 0; k < options.length; ++k) {
        const option = options[k];
        if (option.value === possibleValue) {
          index = k;
          break;
        }
      }
    }

    if (index === -1) {
      index = 0;
    } else if (index < options.length) {
      index++;
    }
    // recreate the <option> tag
    const readdOption = document.createElement('option');
    readdOption.text = unique.possible[value];
    readdOption.value = value;
    // add the <option> at the right position
    selectElement.insertBefore(readdOption, selectElement.options[index]);
  }

  public connectedCallback(): void {
    this.ajaxDispatcher = new AjaxDispatcher(this.objectGroup);
    this.recordsContainer = <HTMLDivElement>this.querySelector(selector`[id="${this.id}_records"]`);
    this.registerEvents();
  }

  private getRecordContainer(objectId: string): HTMLDivElement {
    return <HTMLDivElement>this.querySelector(selector`[data-object-id="${objectId}"]`);
  }

  private getCollapseContent(objectId: string): HTMLDivElement {
    return <HTMLDivElement>this.querySelector(selector`[id="${objectId}_fields"]`);
  }

  private collapseElement(objectId: string): void {
    Collapse.getOrCreateInstance(this.getCollapseContent(objectId)).hide();
  }


  private isNewRecord(objectId: string): boolean {
    const recordContainer = this.getRecordContainer(objectId);
    return recordContainer.classList.contains(States.new);
  }

  private updateExpandedCollapsedStateLocally(objectId: string, value: boolean): void {
    const recordContainer = this.getRecordContainer(objectId);

    const ucFormObj = this.querySelectorAll(
      '[name="'
      + 'uc[inlineView]'
      + '[' + recordContainer.dataset.topmostParentTable + ']'
      + '[' + recordContainer.dataset.topmostParentUid + ']'
      + recordContainer.dataset.fieldName
      + '"]'
    );

    if (ucFormObj.length) {
      (<HTMLInputElement>ucFormObj[0]).value = value ? '1' : '0';
    }
  }

  private async registerEvents(): Promise<void> {
    await DocumentService.ready();
    this.registerInfoButton();
    this.registerSort();
    this.registerCreateRecordButton();
    this.registerEnableDisableButton();
    this.registerDeleteButton();
    this.registerSynchronizeLocalize();
    this.registerRevertUniquenessAction();
    this.registerToggle();

    this.registerCreateRecordBySelector();
    this.registerCreateRecordByPresetSelector();
    this.registerUniqueSelectFieldChanged();

    new RegularEvent('message', this.handlePostMessage).bindTo(window);

    if (this.sortable) {
      const recordListContainer = this.recordsContainer;
      new Sortable(recordListContainer, {
        group: recordListContainer.getAttribute('id'),
        handle: '.sortableHandle',
        onSort: (): void => {
          this.updateSorting();
        },
      });
    }
  }

  private registerToggle(): void {
    new RegularEvent('show.bs.collapse', (e: Event): void => {
      const panelCollapse = e.target as HTMLDivElement;
      const recordContainer = panelCollapse.parentElement as HTMLDivElement;
      if (recordContainer.closest('typo3-formengine-container-inline') !== this) {
        return;
      }
      const objectId = recordContainer.dataset.objectId;

      if (recordContainer.classList.contains(States.notLoaded)) {
        e.preventDefault();
        this.loadRecordDetails(objectId);
        return;
      }

      if (this.expandSingle) {
        this.collapseAllRecords(recordContainer.dataset.objectUid);
      }
    }).bindTo(this);

    new RegularEvent('shown.bs.collapse', (e: Event): void => {
      const panelCollapse = e.target as HTMLDivElement;
      const recordContainer = panelCollapse.closest<HTMLDivElement>('[data-object-id]');
      if (recordContainer === null || recordContainer.closest('typo3-formengine-container-inline') !== this) {
        return;
      }
      this.persistExpandCollapseState(recordContainer.dataset.objectId, true);
    }).bindTo(this);

    new RegularEvent('hidden.bs.collapse', (e: Event): void => {
      const panelCollapse = e.target as HTMLDivElement;
      const recordContainer = panelCollapse.closest<HTMLDivElement>('[data-object-id]');
      if (recordContainer === null || recordContainer.closest('typo3-formengine-container-inline') !== this) {
        return;
      }
      this.persistExpandCollapseState(recordContainer.dataset.objectId, false);
    }).bindTo(this);
  }

  private registerSort(): void {
    new RegularEvent('click', (e: Event, targetElement: HTMLElement): void => {
      e.preventDefault();
      e.stopImmediatePropagation();

      this.changeSortingByButton(
        (<HTMLDivElement>targetElement.closest('[data-object-id]')).dataset.objectId,
        <SortDirections>targetElement.dataset.direction,
      );
    }).delegateTo(this, Selectors.controlSectionSelector + ' [data-action="sort"]');
  }

  private registerCreateRecordButton(): void {
    new RegularEvent('click', (e: Event, targetElement: HTMLElement): void => {
      e.preventDefault();
      e.stopImmediatePropagation();

      if (this.isBelowMax()) {
        let objectId = this.objectGroup;
        if (typeof targetElement.dataset.recordUid !== 'undefined') {
          objectId += Separators.structureSeparator + targetElement.dataset.recordUid;
        }

        this.importRecord([objectId, (this.querySelector(Selectors.createNewRecordBySelectorSelector) as HTMLInputElement)?.value], targetElement.dataset.recordUid ?? null);
      }
    }).delegateTo(this, Selectors.createNewRecordButtonSelector);
  }

  private registerCreateRecordBySelector(): void {
    new RegularEvent('change', (e: Event, targetElement: HTMLElement): void => {
      e.preventDefault();
      e.stopImmediatePropagation();

      const selectTarget = <HTMLSelectElement>targetElement;
      const recordUid = selectTarget.options[selectTarget.selectedIndex].getAttribute('value');

      this.importRecord([this.objectGroup, recordUid]);
    }).delegateTo(this, Selectors.createNewRecordBySelectorSelector);
  }

  private registerCreateRecordByPresetSelector(): void {
    new RegularEvent('change', (e: Event): void => {
      e.preventDefault();
      e.stopImmediatePropagation();

      const selector = this.querySelector(Selectors.createNewRecordByPresetSelector) as HTMLSelectElement;
      const presetValue = selector?.value;
      if (presetValue === '') {
        return;
      }

      selector.value = '';
      this.importRecord([this.objectGroup, '', presetValue]);
    }).delegateTo(this, Selectors.createNewRecordByPresetSelector);
  }

  /**
   * @param {MessageEvent} e
   */
  private readonly handlePostMessage = (e: MessageEvent): void => {
    if (!MessageUtility.verifyOrigin(e.origin)) {
      throw 'Denied message sent by ' + e.origin;
    }

    if (e.data.actionName === 'typo3:foreignRelation:insert') {
      if (typeof e.data.objectGroup === 'undefined') {
        throw 'No object group defined for message';
      }

      if (e.data.objectGroup !== this.objectGroup) {
        // Received message isn't provisioned for current InlineControlContainer instance
        return;
      }

      if (this.isUniqueElementUsed(parseInt(e.data.uid, 10), e.data.table)) {
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

    if (e.data.actionName === 'typo3:foreignRelation:delete') {
      if (e.data.objectGroup !== this.objectGroup) {
        // Received message isn't provisioned for current FilesContainer instance
        return;
      }

      const forceDirectRemoval = e.data.directRemoval || false;
      const objectId = [e.data.objectGroup, e.data.uid].join('-');
      this.deleteRecord(objectId, forceDirectRemoval);
    }
  };

  /**
   * @param {string} uid
   * @param {string} markup
   * @param {string} afterUid
   * @param {string} selectedValue
   */
  private createRecord(uid: string, markup: string, afterUid: string = null, selectedValue: string = null): void {
    let objectId = this.objectGroup;
    if (afterUid !== null) {
      objectId += Separators.structureSeparator + afterUid;
    }

    if (afterUid !== null) {
      this.getRecordContainer(objectId).insertAdjacentHTML('afterend', markup);
      this.memorizeAddRecord(uid, afterUid, selectedValue);
    } else {
      this.recordsContainer.insertAdjacentHTML('beforeend', markup);
      this.memorizeAddRecord(uid, null, selectedValue);
    }
  }

  /**
   * @param {Array} params
   * @param {string} afterUid
   */
  private async importRecord(params: Array<any>, afterUid?: string): Promise<void> {
    return this.ajaxDispatcher.send(
      this.ajaxDispatcher.newRequest(this.ajaxDispatcher.getEndpoint(this.endpoints.create)),
      params,
    ).then(async (response: InlineResponseInterface): Promise<void> => {
      if (this.isBelowMax()) {
        this.createRecord(
          response.compilerInput.uid,
          response.data,
          typeof afterUid !== 'undefined' ? afterUid : null,
          typeof response.compilerInput.childChildUid !== 'undefined' ? response.compilerInput.childChildUid : null,
        );
      }
    });
  }

  private registerEnableDisableButton(): void {
    new RegularEvent('click', (e: Event, target: HTMLElement): void => {
      e.preventDefault();
      e.stopImmediatePropagation();

      const objectId = (<HTMLDivElement>target.closest('[data-object-id]')).dataset.objectId;
      const recordContainer = this.getRecordContainer(objectId);
      const hiddenFieldName = selector`data${recordContainer.dataset.fieldName}[${target.dataset.hiddenField}]`;
      const hiddenValueCheckBox = <HTMLInputElement>this.querySelector('[data-formengine-input-name="' + hiddenFieldName + '"');
      const hiddenValueInput = <HTMLInputElement>this.querySelector('[name="' + hiddenFieldName + '"');

      if (hiddenValueCheckBox !== null && hiddenValueInput !== null) {
        hiddenValueCheckBox.checked = !hiddenValueCheckBox.checked;
        const active = hiddenValueCheckBox.checked !== (hiddenValueCheckBox.dataset.invertStateDisplay === 'true');
        hiddenValueInput.value = active ? '1' : '0';
        FormEngine.markFieldAsChanged(hiddenValueCheckBox);
      }

      const hiddenClass = 'panel-hidden';
      const isHidden = recordContainer.classList.contains(hiddenClass);
      let toggleIcon: string;

      if (isHidden) {
        toggleIcon = 'actions-edit-hide';
        recordContainer.classList.remove(hiddenClass);
      } else {
        toggleIcon = 'actions-edit-unhide';
        recordContainer.classList.add(hiddenClass);
      }

      Icons.getIcon(toggleIcon, Icons.sizes.small).then((markup: string): void => {
        target.replaceChild(document.createRange().createContextualFragment(markup), target.querySelector('.t3js-icon'));
      });
    }).delegateTo(this, Selectors.enableDisableRecordButtonSelector);
  }

  private registerInfoButton(): void {
    new RegularEvent('click', function(this: HTMLElement, e: Event): void {
      e.preventDefault();
      e.stopImmediatePropagation();

      InfoWindow.showItem(this.dataset.infoTable, this.dataset.infoUid);
    }).delegateTo(this, Selectors.infoWindowButton);
  }

  private registerDeleteButton(): void {
    new RegularEvent('click', (e: Event, targetElement: HTMLElement): void => {
      e.preventDefault();
      e.stopImmediatePropagation();

      const title = backendAltDocLabels.get('label.confirm.delete_record.title');
      const content = backendAltDocLabels.get('label.confirm.delete_record.content', [targetElement.dataset.recordInfo]);
      const modal = Modal.confirm(title, content, Severity.warning, [
        {
          text: backendAltDocLabels.get('buttons.confirm.delete_record.no'),
          active: true,
          btnClass: 'btn-default',
          name: 'no',
        },
        {
          text: backendAltDocLabels.get('buttons.confirm.delete_record.yes'),
          btnClass: 'btn-warning',
          name: 'yes',
        },
      ]);
      modal.addEventListener('button.clicked', (modalEvent: Event): void => {
        if ((<HTMLAnchorElement>modalEvent.target).name === 'yes') {
          const objectId = (<HTMLDivElement>targetElement.closest('[data-object-id]')).dataset.objectId;
          this.deleteRecord(objectId);
        }

        modal.hideModal();
      });
    }).delegateTo(this, Selectors.deleteRecordButtonSelector);
  }

  /**
   * @param {Event} e
   */
  private registerSynchronizeLocalize(): void {
    new RegularEvent('click', (e: Event, targetElement: HTMLElement): void => {
      e.preventDefault();
      e.stopImmediatePropagation();

      if (this.endpoints.synchronizelocalize === null) {
        console.error(`Synchronize/localize is not supported for type "${this.type}"`);
        return;
      }

      this.ajaxDispatcher.send(
        this.ajaxDispatcher.newRequest(this.ajaxDispatcher.getEndpoint(this.endpoints.synchronizelocalize)),
        [this.objectGroup, targetElement.dataset.type],
      ).then(async (response: InlineResponseInterface): Promise<void> => {
        this.recordsContainer.insertAdjacentHTML('beforeend', response.data);

        const objectIdPrefix = this.objectGroup + Separators.structureSeparator;
        for (const itemUid of response.compilerInput.delete) {
          this.deleteRecord(objectIdPrefix + itemUid, true);
        }

        for (const item of Object.values(response.compilerInput.localize)) {
          if (typeof item.remove !== 'undefined') {
            const removableRecordContainer = this.getRecordContainer(objectIdPrefix + item.remove);
            removableRecordContainer.parentElement.removeChild(removableRecordContainer);
          }

          this.memorizeAddRecord(item.uid, null, item.selectedValue);
        }
      });
    }).delegateTo(this, Selectors.synchronizeLocalizeRecordButtonSelector);
  }

  private registerUniqueSelectFieldChanged(): void {
    new RegularEvent('change', (e: Event, targetElement: HTMLElement): void => {
      e.preventDefault();
      e.stopImmediatePropagation();

      const recordContainer = (<HTMLDivElement>targetElement.closest('[data-object-id]'));
      if (recordContainer !== null) {
        const objectId = recordContainer.dataset.objectId;
        const objectUid = recordContainer.dataset.objectUid;
        this.handleChangedField(<HTMLSelectElement>targetElement, objectId);

        const formField = this.getFormFieldForElements();
        if (formField === null) {
          return;
        }
        this.updateUnique(<HTMLSelectElement>targetElement, formField, objectUid);
      }
    }).delegateTo(this, Selectors.uniqueValueSelectors);
  }

  private registerRevertUniquenessAction(): void {
    new RegularEvent('click', (e: Event, targetElement: HTMLElement): void => {
      e.preventDefault();
      e.stopImmediatePropagation();

      this.revertUnique(targetElement.dataset.uid);
    }).delegateTo(this, Selectors.revertUniqueness);
  }

  /**
   * Loads record details via AJAX for a not-yet-loaded record, then expands it.
   */
  private loadRecordDetails(objectId: string): void {
    const recordFieldsContainer = this.getCollapseContent(objectId);
    const recordContainer = this.getRecordContainer(objectId);
    const isLoading = typeof this.requestQueue[objectId] !== 'undefined';

    const progress = this.getProgress(objectId);

    if (!isLoading) {
      const ajaxRequest = this.ajaxDispatcher.newRequest(this.ajaxDispatcher.getEndpoint(this.endpoints.details));
      const request = this.ajaxDispatcher.send(ajaxRequest, [objectId]);

      request.then(async (response: InlineResponseInterface): Promise<void> => {
        delete this.requestQueue[objectId];
        delete this.progressQueue[objectId];

        recordContainer.classList.remove(States.notLoaded);
        recordFieldsContainer.innerHTML = response.data;

        progress.done();

        // Now that content is loaded, trigger expand via Bootstrap Collapse
        if (this.expandSingle) {
          this.collapseAllRecords(recordContainer.dataset.objectUid);
        }
        Collapse.getOrCreateInstance(recordFieldsContainer).show();

        FormEngine.reinitialize();
        FormEngineValidation.initializeInputFields();
        FormEngineValidation.validate(this);

        if (this.hasObjectGroupDefinedUniqueConstraints()) {
          const recordContainer = this.getRecordContainer(objectId);
          this.removeUsed(recordContainer);
        }
      });

      this.requestQueue[objectId] = ajaxRequest;
      progress.start();
    } else {
      // Abort loading if collapsed again
      this.requestQueue[objectId].abort();
      delete this.requestQueue[objectId];
      delete this.progressQueue[objectId];
      progress.done();
    }
  }

  /**
   * Persists the expand/collapse state for a record, either locally (for new records)
   * or via AJAX to the backend user's UC.
   */
  private persistExpandCollapseState(objectId: string, isExpanded: boolean): void {
    if (this.endpoints.expandcollapse === null) {
      return;
    }

    if (this.isNewRecord(objectId)) {
      this.updateExpandedCollapsedStateLocally(objectId, isExpanded);
      return;
    }

    const recordElement = this.getRecordContainer(objectId);
    const expand = isExpanded ? recordElement.dataset.objectUid : '';
    const collapse = isExpanded ? '' : recordElement.dataset.objectUid;

    this.ajaxDispatcher.send(
      this.ajaxDispatcher.newRequest(this.ajaxDispatcher.getEndpoint(this.endpoints.expandcollapse)),
      [objectId, expand, collapse]
    );
  }

  /**
   * @param {string} newUid
   * @param {string} afterUid
   * @param {string} selectedValue
   */
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
    FormEngine.markFieldAsChanged(formField);
    document.dispatchEvent(new Event('change'));

    this.redrawSortingButtons(this.objectGroup, records);
    this.setUnique(newUid, selectedValue);

    if (!this.isBelowMax()) {
      this.toggleContainerControls(false);
    }

    FormEngine.reinitialize();
    FormEngineValidation.initializeInputFields();
    FormEngineValidation.validate(this);
  }

  /**
   * @param {String} objectUid
   * @return Array<string>
   */
  private memorizeRemoveRecord(objectUid: string): Array<string> {
    const formField = this.getFormFieldForElements();
    if (formField === null) {
      return [];
    }

    const records = Utility.trimExplode(',', (<HTMLInputElement>formField).value);
    const indexOfRemoveUid = records.indexOf(objectUid);
    if (indexOfRemoveUid > -1) {
      records.splice(indexOfRemoveUid, 1);

      (<HTMLInputElement>formField).value = records.join(',');
      FormEngine.markFieldAsChanged(formField);
      document.dispatchEvent(new Event('change'));

      this.redrawSortingButtons(this.objectGroup, records);
    }

    return records;
  }

  /**
   * @param {string} objectId
   * @param {SortDirections} direction
   */
  private changeSortingByButton(objectId: string, direction: SortDirections): void {
    const currentRecordContainer = this.getRecordContainer(objectId);
    const recordUid = currentRecordContainer.dataset.objectUid;
    const recordListContainer = this.recordsContainer;
    const records = Array.from(recordListContainer.children).map((child: HTMLElement) => child.dataset.objectUid);
    const position = records.indexOf(recordUid);
    let isChanged = false;

    if (direction === SortDirections.UP && position > 0) {
      records[position] = records[position - 1];
      records[position - 1] = recordUid;
      isChanged = true;
    } else if (direction === SortDirections.DOWN && position < records.length - 1) {
      records[position] = records[position + 1];
      records[position + 1] = recordUid;
      isChanged = true;
    }

    if (isChanged) {
      const objectIdPrefix = this.objectGroup + Separators.structureSeparator;
      const adjustment = direction === SortDirections.UP ? 1 : 0;
      currentRecordContainer.parentElement.insertBefore(
        this.getRecordContainer(objectIdPrefix + records[position - adjustment]),
        this.getRecordContainer(objectIdPrefix + records[position + 1 - adjustment]),
      );

      this.updateSorting();
    }
  }

  private updateSorting(): void {
    const formField = this.getFormFieldForElements();
    if (formField === null) {
      return;
    }

    const recordListContainer = this.recordsContainer;
    const records = Array.from(recordListContainer.querySelectorAll(selector`[data-object-parent-group="${this.objectGroup}"][data-placeholder-record="0"]`))
      .map((child: HTMLElement) => child.dataset.objectUid);

    (<HTMLInputElement>formField).value = records.join(',');
    FormEngine.markFieldAsChanged(formField);
    document.dispatchEvent(new Event('change'));

    this.redrawSortingButtons(this.objectGroup, records);
  }

  /**
   * @param {String} objectId
   * @param {Boolean} forceDirectRemoval
   */
  private deleteRecord(objectId: string, forceDirectRemoval: boolean = false): void {
    const recordContainer = this.getRecordContainer(objectId);
    const objectUid = recordContainer.dataset.objectUid;

    recordContainer.classList.add('t3js-inline-record-deleted');

    if (!this.isNewRecord(objectId) && !forceDirectRemoval) {
      const deleteCommandInput = this.querySelector(selector`[name="cmd${recordContainer.dataset.fieldName}[delete]"]`);
      deleteCommandInput.removeAttribute('disabled');

      // Move input field to inline container so we can remove the record container
      recordContainer.parentElement.insertAdjacentElement('afterbegin', deleteCommandInput);
    }

    new RegularEvent('transitionend', (): void => {
      recordContainer.remove();
      FormEngineValidation.validate(this);
    }).bindTo(recordContainer);

    this.revertUnique(objectUid);
    this.memorizeRemoveRecord(objectUid);
    recordContainer.classList.add('form-irre-object--deleted');

    if (this.isBelowMax()) {
      this.toggleContainerControls(true);
    }
  }

  /**
   * @param {boolean} visible
   */
  private toggleContainerControls(visible: boolean): void {
    const controlContainer = this.querySelectorAll(
      ':scope > ' + Selectors.controlContainer
    );
    controlContainer.forEach((container: HTMLElement): void => {
      const controlContainerButtons = container.querySelectorAll<HTMLButtonElement | HTMLAnchorElement>('button, a');
      controlContainerButtons.forEach((button: HTMLButtonElement | HTMLAnchorElement): void => {
        button.hidden = !visible;
      });
    });
  }

  private getProgress(objectId: string): ProgressBarElement {
    let progress: ProgressBarElement;

    if (typeof this.progressQueue[objectId] !== 'undefined') {
      progress = this.progressQueue[objectId];
    } else {
      progress = document.createElement('typo3-backend-progress-bar');
      const panel = this.getRecordContainer(objectId);
      panel.insertBefore(progress, panel.firstChild);
      this.progressQueue[objectId] = progress;
    }

    return progress;
  }

  /**
   * Collapses all records except the one with the given UID.
   * State persistence is handled by the hidden.bs.collapse event handler.
   */
  private collapseAllRecords(excludeUid: string): void {
    const formField = this.getFormFieldForElements();

    if (formField !== null) {
      const records = Utility.trimExplode(',', (<HTMLInputElement>formField).value);
      for (const recordUid of records) {
        if (recordUid === excludeUid) {
          continue;
        }

        const recordObjectId = this.objectGroup + Separators.structureSeparator + recordUid;
        const collapseContent = this.getCollapseContent(recordObjectId);
        if (collapseContent?.classList.contains('show')) {
          this.collapseElement(recordObjectId);
        }
      }
    }
  }

  private getFormFieldForElements(): HTMLInputElement | null {
    return this.querySelector<HTMLInputElement>(selector`[name="${this.formField}"]`);
  }

  /**
   * Redraws rhe sorting buttons of each record
   *
   * @param {string} objectId
   * @param {Array<string>} records
   */
  private redrawSortingButtons(objectId: string, records: Array<string> = []): void {
    if (records.length === 0) {
      const formField = this.getFormFieldForElements();
      if (formField !== null) {
        records = Utility.trimExplode(',', (<HTMLInputElement>formField).value);
      }
    }

    if (records.length === 0) {
      return;
    }

    records.forEach((recordUid: string, index: number): void => {
      const recordContainer = this.getRecordContainer(objectId + Separators.structureSeparator + recordUid);
      if (recordContainer === null) {
        return;
      }
      const sortUp = recordContainer.querySelector(selector`[data-action="sort"][data-direction="${SortDirections.UP}"]`);

      if (sortUp !== null) {
        let iconIdentifier = 'actions-move-up';
        if (index === 0) {
          sortUp.classList.add('disabled');
          iconIdentifier = 'empty-empty';
        } else {
          sortUp.classList.remove('disabled');
        }
        Icons.getIcon(iconIdentifier, Icons.sizes.small).then((markup: string): void => {
          sortUp.replaceChild(document.createRange().createContextualFragment(markup), sortUp.querySelector('.t3js-icon'));
        });
      }

      const sortDown = recordContainer.querySelector(selector`[data-action="sort"][data-direction="${SortDirections.DOWN}"]`);
      if (sortDown !== null) {
        let iconIdentifier = 'actions-move-down';
        if (index === records.length - 1) {
          sortDown.classList.add('disabled');
          iconIdentifier = 'empty-empty';
        } else {
          sortDown.classList.remove('disabled');
        }
        Icons.getIcon(iconIdentifier, Icons.sizes.small).then((markup: string): void => {
          sortDown.replaceChild(document.createRange().createContextualFragment(markup), sortDown.querySelector('.t3js-icon'));
        });
      }
    });
  }

  /**
   * @return {boolean}
   */
  private isBelowMax(): boolean {
    const formField = this.getFormFieldForElements();
    if (formField === null) {
      return true;
    }

    const records = Utility.trimExplode(',', (<HTMLInputElement>formField).value);
    if (this.max > 0 && records.length >= this.max) {
      return false;
    }

    if (this.hasObjectGroupDefinedUniqueConstraints()) {
      const unique = TYPO3.settings.FormEngineInline.unique[this.objectGroup];
      if (unique.used.length >= unique.max && unique.max >= 0) {
        return false;
      }
    }

    return true;
  }

  /**
   * @param {number} uid
   * @param {string} table
   */
  private isUniqueElementUsed(uid: number, table: string): boolean {
    if (!this.hasObjectGroupDefinedUniqueConstraints()) {
      return false;
    }

    const unique: UniqueDefinition = TYPO3.settings.FormEngineInline.unique[this.objectGroup];
    const values = InlineControlContainer.getValuesFromHashMap(unique.used);

    if (unique.type === 'select' && values.indexOf(uid) !== -1) {
      return true;
    }

    if (unique.type === 'groupdb') {
      for (let i = values.length - 1; i >= 0; i--) {
        // if the pair table:uid is already used:
        if (values[i].table === table && values[i].uid === uid) {
          return true;
        }
      }
    }

    return false;
  }

  /**
   * @param {HTMLDivElement} recordContainer
   */
  private removeUsed(recordContainer: HTMLDivElement): void {
    if (!this.hasObjectGroupDefinedUniqueConstraints()) {
      return;
    }

    const unique: UniqueDefinition = TYPO3.settings.FormEngineInline.unique[this.objectGroup];
    if (unique.type !== 'select') {
      return;
    }

    const uniqueValueField = <HTMLSelectElement>recordContainer.querySelector(
      '[name="data[' + unique.table + '][' + recordContainer.dataset.objectUid + '][' + unique.field + ']"]',
    );
    const values = InlineControlContainer.getValuesFromHashMap(unique.used);

    if (uniqueValueField !== null) {
      const selectedValue = uniqueValueField.options[uniqueValueField.selectedIndex].value;
      for (const value of values) {
        if (value !== selectedValue) {
          InlineControlContainer.removeSelectOptionByValue(uniqueValueField, value);
        }
      }
    }
  }

  /**
   * @param {string} recordUid
   * @param {string} selectedValue
   */
  private setUnique(recordUid: string, selectedValue: string): void {
    if (!this.hasObjectGroupDefinedUniqueConstraints()) {
      return;
    }
    const selectorElement = <HTMLSelectElement>this.querySelector(
      selector`[id="${this.objectGroup}_selector"]`,
    );
    const unique: UniqueDefinition = TYPO3.settings.FormEngineInline.unique[this.objectGroup];
    if (unique.type === 'select') {
      if (!(unique.selector && unique.max === -1)) {
        const formField = this.getFormFieldForElements();
        const recordObjectId = this.objectGroup + Separators.structureSeparator + recordUid;
        const recordContainer = this.getRecordContainer(recordObjectId);
        let uniqueValueField = <HTMLSelectElement>recordContainer.querySelector(
          '[name="data[' + unique.table + '][' + recordUid + '][' + unique.field + ']"]',
        );
        const values = InlineControlContainer.getValuesFromHashMap(unique.used);
        if (selectorElement !== null) {
          // remove all items from the new select-item which are already used in other children
          if (uniqueValueField !== null) {
            for (const value of values) {
              InlineControlContainer.removeSelectOptionByValue(uniqueValueField, value);
            }
            // set the selected item automatically to the first of the remaining items if no selector is used
            if (!unique.selector) {
              selectedValue = uniqueValueField.options[0].value;
              uniqueValueField.options[0].selected = true;
              this.updateUnique(uniqueValueField, formField, recordUid);
              this.handleChangedField(uniqueValueField, this.objectGroup + '[' + recordUid + ']');
            }
          }
          for (const value of values) {
            InlineControlContainer.removeSelectOptionByValue(uniqueValueField, value);
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
        if (formField !== null && InlineControlContainer.selectOptionValueExists(selectorElement, selectedValue)) {
          const records = Utility.trimExplode(',', (<HTMLInputElement>formField).value);
          for (const record of records) {
            uniqueValueField = <HTMLSelectElement>this.querySelector(
              '[name="data[' + unique.table + '][' + record + '][' + unique.field + ']"]',
            );
            if (uniqueValueField !== null && record !== recordUid) {
              InlineControlContainer.removeSelectOptionByValue(uniqueValueField, selectedValue);
            }
          }
        }
      }
    } else if (unique.type === 'groupdb') {
      // add the new record to the used items:
      unique.used[recordUid] = {
        table: unique.elTable,
        uid: selectedValue,
      };
    }

    // remove used items from a selector-box
    if (unique.selector === 'select' && InlineControlContainer.selectOptionValueExists(selectorElement, selectedValue)) {
      InlineControlContainer.removeSelectOptionByValue(selectorElement, selectedValue);
      unique.used[recordUid] = {
        table: unique.elTable,
        uid: selectedValue,
      };
    }
  }

  /**
   * @param {HTMLSelectElement} srcElement
   * @param {HTMLInputElement} formField
   * @param {string} recordUid
   */
  private updateUnique(srcElement: HTMLSelectElement, formField: HTMLInputElement, recordUid: string): void {
    if (!this.hasObjectGroupDefinedUniqueConstraints()) {
      return;
    }
    const unique = TYPO3.settings.FormEngineInline.unique[this.objectGroup];
    const oldValue = unique.used[recordUid];

    if (unique.selector === 'select') {
      const selectorElement = <HTMLSelectElement>this.querySelector(
        selector`[id="${this.objectGroup}_selector"]`,
      );
      InlineControlContainer.removeSelectOptionByValue(selectorElement, srcElement.value);
      if (typeof oldValue !== 'undefined') {
        InlineControlContainer.reAddSelectOption(selectorElement, oldValue, unique);
      }
    }

    if (unique.selector && unique.max === -1) {
      return;
    }

    if (!unique || formField === null) {
      return;
    }

    const records = Utility.trimExplode(',', formField.value);
    let uniqueValueField;
    for (const record of records) {
      uniqueValueField = <HTMLSelectElement>this.querySelector(
        '[name="data[' + unique.table + '][' + record + '][' + unique.field + ']"]',
      );
      if (uniqueValueField !== null && uniqueValueField !== srcElement) {
        InlineControlContainer.removeSelectOptionByValue(uniqueValueField, srcElement.value);
        if (typeof oldValue !== 'undefined') {
          InlineControlContainer.reAddSelectOption(uniqueValueField, oldValue, unique);
        }
      }
    }
    unique.used[recordUid] = srcElement.value;
  }

  /**
   * @param {string} recordUid
   */
  private revertUnique(recordUid: string): void {
    if (!this.hasObjectGroupDefinedUniqueConstraints()) {
      return;
    }

    const unique = TYPO3.settings.FormEngineInline.unique[this.objectGroup];
    const recordObjectId = this.objectGroup + Separators.structureSeparator + recordUid;
    const recordContainer = this.getRecordContainer(recordObjectId);

    const uniqueValueField = <HTMLSelectElement>recordContainer.querySelector(
      '[name="data[' + unique.table + '][' + recordContainer.dataset.objectUid + '][' + unique.field + ']"]',
    );
    if (unique.type === 'select') {
      let uniqueValue;
      if (uniqueValueField !== null) {
        uniqueValue = uniqueValueField.value;
      } else if (recordContainer.dataset.tableUniqueOriginalValue !== '') {
        uniqueValue = recordContainer.dataset.tableUniqueOriginalValue;
      } else {
        return;
      }

      if (unique.selector === 'select') {
        if (!isNaN(parseInt(uniqueValue, 10))) {
          const selectorElement = <HTMLSelectElement>this.querySelector(
            selector`[id="${this.objectGroup}_selector"]`,
          );
          InlineControlContainer.reAddSelectOption(selectorElement, uniqueValue, unique);
        }
      }

      if (unique.selector && unique.max === -1) {
        return;
      }

      const formField = this.getFormFieldForElements();
      if (formField === null) {
        return;
      }

      const records = Utility.trimExplode(',', formField.value);
      let recordObj;
      // walk through all inline records on that level and get the select field
      for (let i = 0; i < records.length; i++) {
        recordObj = <HTMLSelectElement>this.querySelector(
          '[name="data[' + unique.table + '][' + records[i] + '][' + unique.field + ']"]',
        );
        if (recordObj !== null) {
          InlineControlContainer.reAddSelectOption(recordObj, uniqueValue, unique);
        }
      }

      delete unique.used[recordUid];
    } else if (unique.type === 'groupdb') {
      delete unique.used[recordUid];
    }
  }

  /**
   * @return {boolean}
   */
  private hasObjectGroupDefinedUniqueConstraints(): boolean {
    return typeof TYPO3.settings.FormEngineInline.unique !== 'undefined'
      && typeof TYPO3.settings.FormEngineInline.unique[this.objectGroup] !== 'undefined';
  }

  /**
   * @param {HTMLInputElement | HTMLSelectElement} formField
   * @param {string} objectId
   */
  private handleChangedField(formField: HTMLInputElement | HTMLSelectElement, objectId: string): void {
    let value;
    if (formField instanceof HTMLSelectElement) {
      value = formField.options[formField.selectedIndex].text;
    } else {
      value = formField.value;
    }
    this.querySelector(selector`[id="${objectId}_label"]`).textContent = value.length ? value : this.noTitleString;
  }

}

window.customElements.define('typo3-formengine-container-inline', InlineControlContainer);

declare global {
  interface HTMLElementTagNameMap {
    'typo3-formengine-container-inline': InlineControlContainer;
  }
}
