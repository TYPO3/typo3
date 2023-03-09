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
import { MessageUtility } from '../../utility/message-utility';
import { AjaxDispatcher } from './../inline-relation/ajax-dispatcher';
import { InlineResponseInterface } from './../inline-relation/inline-response-interface';
import NProgress from 'nprogress';
import Sortable from 'sortablejs';
import FormEngine from '@typo3/backend/form-engine';
import FormEngineValidation from '@typo3/backend/form-engine-validation';
import Icons from '../../icons';
import InfoWindow from '../../info-window';
import Modal, { ModalElement } from '../../modal';
import RegularEvent from '@typo3/core/event/regular-event';
import Severity from '../../severity';
import Utility from '../../utility';

enum Selectors {
  toggleSelector = '[data-bs-toggle="formengine-file"]',
  controlSectionSelector = '.t3js-formengine-file-header-control',
  deleteRecordButtonSelector = '.t3js-editform-delete-file-reference',
  enableDisableRecordButtonSelector = '.t3js-toggle-visibility-button',
  infoWindowButton = '[data-action="infowindow"]',
  synchronizeLocalizeRecordButtonSelector = '.t3js-synchronizelocalize-button',
  controlContainer = '.t3js-file-controls',
}

enum States {
  new = 'isNewFileReference',
  visible = 'panel-visible',
  collapsed = 'panel-collapsed',
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
  [key: string]: any;
}

interface Appearance {
  expandSingle?: boolean;
  useSortable?: boolean;
}

/**
 * Module: @typo3/backend/form-engine/container/files-control-container
 *
 * Functionality for the files control container
 *
 * @example
 * <typo3-formengine-container-files identifier="some-id">
 *   ...
 * </typo3-formengine-container-files>
 *
 * This is based on W3C custom elements ("web components") specification, see
 * https://developer.mozilla.org/en-US/docs/Web/Web_Components/Using_custom_elements
 */
class FilesControlContainer extends HTMLElement {
  private container: HTMLElement = null;
  private ajaxDispatcher: AjaxDispatcher = null;
  private appearance: Appearance = null;
  private requestQueue: RequestQueue = {};
  private progessQueue: ProgressQueue = {};
  private noTitleString: string = (TYPO3.lang ? TYPO3.lang['FormEngine.noRecordTitle'] : '[No title]');

  private static getFileReferenceContainer(objectId: string): HTMLDivElement {
    return <HTMLDivElement>document.querySelector('[data-object-id="' + objectId + '"]');
  }

  private static getCollapseButton(objectId: string): HTMLButtonElement {
    return <HTMLButtonElement>document.querySelector('[aria-controls="' + objectId + '_fields"]');
  }

  private static toggleElement(objectId: string): void {
    const fileReferenceContainer = FilesControlContainer.getFileReferenceContainer(objectId);
    if (fileReferenceContainer.classList.contains(States.collapsed)) {
      FilesControlContainer.expandElement(fileReferenceContainer, objectId);
    } else {
      FilesControlContainer.collapseElement(fileReferenceContainer, objectId);
    }
  }

  private static collapseElement(recordContainer: HTMLDivElement, objectId: string): void {
    const collapseButton = FilesControlContainer.getCollapseButton(objectId);
    recordContainer.classList.remove(States.visible);
    recordContainer.classList.add(States.collapsed);
    collapseButton.setAttribute('aria-expanded', 'false');
  }

  private static expandElement(recordContainer: HTMLDivElement, objectId: string): void {
    const collapseButton = FilesControlContainer.getCollapseButton(objectId);
    recordContainer.classList.remove(States.collapsed);
    recordContainer.classList.add(States.visible);
    collapseButton.setAttribute('aria-expanded', 'true');
  }

  private static isNewRecord(objectId: string): boolean {
    const fileReferenceContainer = FilesControlContainer.getFileReferenceContainer(objectId);
    return fileReferenceContainer.classList.contains(States.new);
  }

  private static updateExpandedCollapsedStateLocally(objectId: string, value: boolean): void {
    const fileReferenceContainer = FilesControlContainer.getFileReferenceContainer(objectId);

    const ucFormObj = document.getElementsByName(
      'uc[inlineView]'
      + '[' + fileReferenceContainer.dataset.topmostParentTable + ']'
      + '[' + fileReferenceContainer.dataset.topmostParentUid + ']'
      + fileReferenceContainer.dataset.fieldName
    );

    if (ucFormObj.length) {
      (<HTMLInputElement>ucFormObj[0]).value = value ? '1' : '0';
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
    this.registerInfoButton();
    this.registerSort();
    this.registerEnableDisableButton();
    this.registerDeleteButton();
    this.registerSynchronizeLocalize();
    this.registerToggle();

    new RegularEvent('message', this.handlePostMessage).bindTo(window);

    if (this.getAppearance().useSortable) {
      const recordListContainer = <HTMLDivElement>document.getElementById(this.container.getAttribute('id') + '_records');
      // tslint:disable-next-line:no-unused-expression
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
    const me = this;
    new RegularEvent('click', function(this: HTMLElement, e: Event) {
      e.preventDefault();
      e.stopImmediatePropagation();

      me.loadRecordDetails(this.closest(Selectors.toggleSelector).parentElement.dataset.objectId);
    }).delegateTo(this.container, `${Selectors.toggleSelector} .form-irre-header-cell:not(${Selectors.controlSectionSelector}`);
  }

  private registerSort(): void {
    const me = this;
    new RegularEvent('click', function(this: HTMLElement, e: Event) {
      e.preventDefault();
      e.stopImmediatePropagation();

      me.changeSortingByButton(
        (<HTMLDivElement>this.closest('[data-object-id]')).dataset.objectId,
        <SortDirections>this.dataset.direction,
      );
    }).delegateTo(this.container, Selectors.controlSectionSelector + ' [data-action="sort"]');
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
        // Received message isn't provisioned for current FilesContainer instance
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
  };

  private createRecord(uid: string, markup: string, afterUid: string = null): void {
    let objectId = this.container.dataset.objectGroup;
    if (afterUid !== null) {
      objectId += Separators.structureSeparator + afterUid;
    }

    if (afterUid !== null) {
      FilesControlContainer.getFileReferenceContainer(objectId).insertAdjacentHTML('afterend', markup);
      this.memorizeAddRecord(uid, afterUid);
    } else {
      document.getElementById(this.container.getAttribute('id') + '_records').insertAdjacentHTML('beforeend', markup);
      this.memorizeAddRecord(uid, null);
    }
  }

  private async importRecord(params: Array<any>, afterUid?: string): Promise<void> {
    return this.ajaxDispatcher.send(
      this.ajaxDispatcher.newRequest(this.ajaxDispatcher.getEndpoint('file_reference_create')),
      params,
    ).then(async (response: InlineResponseInterface): Promise<void> => {
      if (this.isBelowMax()) {
        this.createRecord(
          response.compilerInput.uid,
          response.data,
          typeof afterUid !== 'undefined' ? afterUid : null
        );
      }
    });
  }

  private registerEnableDisableButton(): void {
    new RegularEvent('click', (e: Event, target: HTMLElement): void => {
      e.preventDefault();
      e.stopImmediatePropagation();

      const objectId = (<HTMLDivElement>target.closest('[data-object-id]')).dataset.objectId;
      const recordContainer = FilesControlContainer.getFileReferenceContainer(objectId);
      const hiddenFieldName = 'data' + recordContainer.dataset.fieldName + '[' + target.dataset.hiddenField + ']';
      const hiddenValueCheckBox = <HTMLInputElement>document.querySelector('[data-formengine-input-name="' + hiddenFieldName + '"');
      const hiddenValueInput = <HTMLInputElement>document.querySelector('[name="' + hiddenFieldName + '"');

      if (hiddenValueCheckBox !== null && hiddenValueInput !== null) {
        hiddenValueCheckBox.checked = !hiddenValueCheckBox.checked;
        hiddenValueInput.value = hiddenValueCheckBox.checked ? '1' : '0';
        FormEngineValidation.markFieldAsChanged(hiddenValueCheckBox);
      }

      const hiddenClass = 't3-form-field-container-inline-hidden';
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
    }).delegateTo(this.container, Selectors.enableDisableRecordButtonSelector);
  }

  private registerInfoButton(): void {
    new RegularEvent('click', function(this: HTMLElement, e: Event) {
      e.preventDefault();
      e.stopImmediatePropagation();

      InfoWindow.showItem(this.dataset.infoTable, this.dataset.infoUid);
    }).delegateTo(this.container, Selectors.infoWindowButton);
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
          trigger: (e: Event, modal: ModalElement) => modal.hideModal(),
        },
        {
          text: TYPO3.lang['buttons.confirm.delete_record.yes'] || 'Yes, delete this record',
          btnClass: 'btn-warning',
          name: 'yes',
          trigger: (e: Event, modal: ModalElement): void => {
            me.deleteRecord((<HTMLDivElement>this.closest('[data-object-id]')).dataset.objectId);
            modal.hideModal();
          }
        },
      ]);
    }).delegateTo(this.container, Selectors.deleteRecordButtonSelector);
  }

  private registerSynchronizeLocalize(): void {
    const me = this;
    new RegularEvent('click', function(this: HTMLElement, e: Event) {
      e.preventDefault();
      e.stopImmediatePropagation();

      me.ajaxDispatcher.send(
        me.ajaxDispatcher.newRequest(me.ajaxDispatcher.getEndpoint('file_reference_synchronizelocalize')),
        [me.container.dataset.objectGroup, this.dataset.type],
      ).then(async (response: InlineResponseInterface): Promise<void> => {
        document.getElementById(me.container.getAttribute('id') + '_records').insertAdjacentHTML('beforeend', response.data);

        const objectIdPrefix = me.container.dataset.objectGroup + Separators.structureSeparator;
        for (const itemUid of response.compilerInput.delete) {
          me.deleteRecord(objectIdPrefix + itemUid, true);
        }

        for (const item of Object.values(response.compilerInput.localize)) {
          if (typeof item.remove !== 'undefined') {
            const removableRecordContainer = FilesControlContainer.getFileReferenceContainer(objectIdPrefix + item.remove);
            removableRecordContainer.parentElement.removeChild(removableRecordContainer);
          }

          me.memorizeAddRecord(item.uid, null);
        }
      });
    }).delegateTo(this.container, Selectors.synchronizeLocalizeRecordButtonSelector);
  }

  private loadRecordDetails(objectId: string): void {
    const recordFieldsContainer = document.getElementById(objectId + '_fields');
    const recordContainer = FilesControlContainer.getFileReferenceContainer(objectId);
    const isLoading = typeof this.requestQueue[objectId] !== 'undefined';
    const isLoaded = recordFieldsContainer !== null && !recordContainer.classList.contains(States.notLoaded);

    if (!isLoaded) {
      const progress = this.getProgress(objectId, recordContainer.dataset.objectIdHash);

      if (!isLoading) {
        const ajaxRequest = this.ajaxDispatcher.newRequest(this.ajaxDispatcher.getEndpoint('file_reference_details'));
        const request = this.ajaxDispatcher.send(ajaxRequest, [objectId]);

        request.then(async (response: InlineResponseInterface): Promise<void> => {
          delete this.requestQueue[objectId];
          delete this.progessQueue[objectId];

          recordContainer.classList.remove(States.notLoaded);
          recordFieldsContainer.innerHTML = response.data;
          this.collapseExpandRecord(objectId);

          progress.done();

          FormEngine.reinitialize();
          FormEngineValidation.initializeInputFields();
          FormEngineValidation.validate(this.container);
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

    this.collapseExpandRecord(objectId);
  }

  private collapseExpandRecord(objectId: string): void {
    const recordElement = FilesControlContainer.getFileReferenceContainer(objectId);
    const expandSingle = this.getAppearance().expandSingle === true;
    const isCollapsed: boolean = recordElement.classList.contains(States.collapsed);
    let collapse: Array<string> = [];
    const expand: Array<string> = [];

    if (expandSingle && isCollapsed) {
      collapse = this.collapseAllRecords(recordElement.dataset.objectUid);
    }

    FilesControlContainer.toggleElement(objectId);

    if (FilesControlContainer.isNewRecord(objectId)) {
      FilesControlContainer.updateExpandedCollapsedStateLocally(objectId, isCollapsed);
    } else if (isCollapsed) {
      expand.push(recordElement.dataset.objectUid);
    } else if (!isCollapsed) {
      collapse.push(recordElement.dataset.objectUid);
    }

    this.ajaxDispatcher.send(
      this.ajaxDispatcher.newRequest(this.ajaxDispatcher.getEndpoint('file_reference_expandcollapse')),
      [objectId, expand.join(','), collapse.join(',')]
    );
  }

  private memorizeAddRecord(newUid: string, afterUid: string = null): void {
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

    this.redrawSortingButtons(this.container.dataset.objectGroup, records);

    if (!this.isBelowMax()) {
      this.toggleContainerControls(false);
    }

    FormEngine.reinitialize();
    FormEngineValidation.initializeInputFields();
    FormEngineValidation.validate(this.container);
  }

  private memorizeRemoveRecord(objectUid: string): Array<string> {
    const formField = this.getFormFieldForElements();
    if (formField === null) {
      return [];
    }

    const records = Utility.trimExplode(',', (<HTMLInputElement>formField).value);
    const indexOfRemoveUid = records.indexOf(objectUid);
    if (indexOfRemoveUid > -1) {
      delete records[indexOfRemoveUid];

      (<HTMLInputElement>formField).value = records.join(',');
      (<HTMLInputElement>formField).classList.add('has-change');
      document.dispatchEvent(new Event('change'));

      this.redrawSortingButtons(this.container.dataset.objectGroup, records);
    }

    return records;
  }

  private changeSortingByButton(objectId: string, direction: SortDirections): void {
    const fileReferenceContainer = FilesControlContainer.getFileReferenceContainer(objectId);
    const objectUid = fileReferenceContainer.dataset.objectUid;
    const recordListContainer = <HTMLDivElement>document.getElementById(this.container.getAttribute('id') + '_records');
    const records = Array.from(recordListContainer.children).map((child: HTMLElement) => child.dataset.objectUid);
    const position = records.indexOf(objectUid);
    let isChanged = false;

    if (direction === SortDirections.UP && position > 0) {
      records[position] = records[position - 1];
      records[position - 1] = objectUid;
      isChanged = true;
    } else if (direction === SortDirections.DOWN && position < records.length - 1) {
      records[position] = records[position + 1];
      records[position + 1] = objectUid;
      isChanged = true;
    }

    if (isChanged) {
      const objectIdPrefix = this.container.dataset.objectGroup + Separators.structureSeparator;
      const adjustment = direction === SortDirections.UP ? 1 : 0;
      fileReferenceContainer.parentElement.insertBefore(
        FilesControlContainer.getFileReferenceContainer(objectIdPrefix + records[position - adjustment]),
        FilesControlContainer.getFileReferenceContainer(objectIdPrefix + records[position + 1 - adjustment]),
      );

      this.updateSorting();
    }
  }

  private updateSorting(): void {
    const formField = this.getFormFieldForElements();
    if (formField === null) {
      return;
    }

    const recordListContainer = <HTMLDivElement>document.getElementById(this.container.getAttribute('id') + '_records');
    const records = Array.from(recordListContainer.querySelectorAll('[data-object-parent-group="' + this.container.dataset.objectGroup + '"][data-placeholder-record="0"]'))
      .map((child: HTMLElement) => child.dataset.objectUid);

    (<HTMLInputElement>formField).value = records.join(',');
    (<HTMLInputElement>formField).classList.add('has-change');
    document.dispatchEvent(new Event('formengine:files:sorting-changed'));
    document.dispatchEvent(new Event('change'));

    this.redrawSortingButtons(this.container.dataset.objectGroup, records);
  }

  private deleteRecord(objectId: string, forceDirectRemoval: boolean = false): void {
    const recordContainer = FilesControlContainer.getFileReferenceContainer(objectId);
    const objectUid = recordContainer.dataset.objectUid;

    recordContainer.classList.add('t3js-file-reference-deleted');

    if (!FilesControlContainer.isNewRecord(objectId) && !forceDirectRemoval) {
      const deleteCommandInput = this.container.querySelector('[name="cmd' + recordContainer.dataset.fieldName + '[delete]"]');
      deleteCommandInput.removeAttribute('disabled');

      // Move input field to inline container so we can remove the record container
      recordContainer.parentElement.insertAdjacentElement('afterbegin', deleteCommandInput);
    }

    new RegularEvent('transitionend', (): void => {
      recordContainer.parentElement.removeChild(recordContainer);
      FormEngineValidation.validate(this.container);
    }).bindTo(recordContainer);

    this.memorizeRemoveRecord(objectUid);
    recordContainer.classList.add('form-irre-object--deleted');

    if (this.isBelowMax()) {
      this.toggleContainerControls(true);
    }
  }

  private toggleContainerControls(visible: boolean): void {
    const controlContainer = this.container.querySelector(Selectors.controlContainer);
    if (controlContainer === null) {
      return;
    }
    const controlContainerButtons = controlContainer.querySelectorAll('button, a');
    controlContainerButtons.forEach((button: HTMLElement): void => {
      button.style.display = visible ? null : 'none';
    });
  }

  private getProgress(objectId: string, objectIdHash: string): any {
    const headerIdentifier = '#' + objectIdHash + '_header';
    let progress: any;

    if (typeof this.progessQueue[objectId] !== 'undefined') {
      progress = this.progessQueue[objectId];
    } else {
      progress = NProgress;
      progress.configure({ parent: headerIdentifier, showSpinner: false });
      this.progessQueue[objectId] = progress;
    }

    return progress;
  }

  private collapseAllRecords(excludeUid: string): Array<string> {
    const formField = this.getFormFieldForElements();
    const collapse: Array<string> = [];

    if (formField !== null) {
      const records = Utility.trimExplode(',', (<HTMLInputElement>formField).value);
      for (const recordUid of records) {
        if (recordUid === excludeUid) {
          continue;
        }

        const recordObjectId = this.container.dataset.objectGroup + Separators.structureSeparator + recordUid;
        const recordContainer = FilesControlContainer.getFileReferenceContainer(recordObjectId);
        if (recordContainer.classList.contains(States.visible)) {
          FilesControlContainer.collapseElement(recordContainer, recordObjectId);

          if (FilesControlContainer.isNewRecord(recordObjectId)) {
            FilesControlContainer.updateExpandedCollapsedStateLocally(recordObjectId, false);
          } else {
            collapse.push(recordUid);
          }
        }
      }
    }

    return collapse;
  }

  private getFormFieldForElements(): HTMLInputElement | null {
    const formFields = document.getElementsByName(this.container.dataset.formField);
    if (formFields.length > 0) {
      return <HTMLInputElement>formFields[0];
    }

    return null;
  }

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
      const recordContainer = FilesControlContainer.getFileReferenceContainer(objectId + Separators.structureSeparator + recordUid);
      const headerIdentifier = recordContainer.dataset.objectIdHash + '_header';
      const headerElement = document.getElementById(headerIdentifier);
      const sortUp = headerElement.querySelector('[data-action="sort"][data-direction="' + SortDirections.UP + '"]');

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

      const sortDown = headerElement.querySelector('[data-action="sort"][data-direction="' + SortDirections.DOWN + '"]');
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

  private isBelowMax(): boolean {
    const formField = this.getFormFieldForElements();
    if (formField === null) {
      return true;
    }

    if (typeof TYPO3.settings.FormEngineInline.config[this.container.dataset.objectGroup] !== 'undefined') {
      const records = Utility.trimExplode(',', (<HTMLInputElement>formField).value);
      if (records.length >= TYPO3.settings.FormEngineInline.config[this.container.dataset.objectGroup].max) {
        return false;
      }
    }

    return true;
  }

  private getAppearance(): Appearance {
    if (this.appearance === null) {
      this.appearance = {};

      if (typeof this.container.dataset.appearance === 'string') {
        try {
          this.appearance = JSON.parse(this.container.dataset.appearance);
        } catch (e) {
          console.error(e);
        }
      }
    }

    return this.appearance;
  }
}

window.customElements.define('typo3-formengine-container-files', FilesControlContainer);
