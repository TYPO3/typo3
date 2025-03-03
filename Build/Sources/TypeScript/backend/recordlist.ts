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

import Icons from '@typo3/backend/icons';
import PersistentStorage from '@typo3/backend/storage/persistent';
import RegularEvent from '@typo3/core/event/regular-event';
import DocumentService from '@typo3/core/document-service';
import { ActionConfiguration, ActionEventDetails } from '@typo3/backend/multi-record-selection-action';
import { MultiRecordSelectionSelectors } from '@typo3/backend/multi-record-selection';
import { selector } from '@typo3/core/literals';
import ResponseInterface from '@typo3/backend/ajax-data-handler/response-interface';
import AjaxDataHandler from '@typo3/backend/ajax-data-handler';

interface IconIdentifier {
  collapse: string;
  expand: string;
}
interface RecordlistIdentifier {
  entity: string;
  toggle: string;
  localize: string;
  hide: string;
  delete: string;
  editMultiple: string;
  icons: IconIdentifier;
}
interface EditRecordsConfiguration extends ActionConfiguration {
  tableName: string;
  columnsOnly: Array<string>;
  returnUrl: string;
}

/**
 * Module: @typo3/backend/recordlist
 * Usability improvements for the record list
 * @exports @typo3/backend/recordlist
 */
class Recordlist {
  identifier: RecordlistIdentifier = {
    entity: '.t3js-entity',
    toggle: '.t3js-toggle-recordlist',
    localize: '.t3js-action-localize',
    hide: 'button[data-datahandler-action="visibility"]',
    delete: '.t3js-record-delete',
    editMultiple: '.t3js-record-edit-multiple',
    icons: {
      collapse: 'actions-view-list-collapse',
      expand: 'actions-view-list-expand'
    },
  };

  constructor() {
    new RegularEvent('click', this.toggleClick).delegateTo(document, this.identifier.toggle);
    new RegularEvent('click', this.onEditMultiple).delegateTo(document, this.identifier.editMultiple);
    new RegularEvent('click', this.disableButton).delegateTo(document, this.identifier.localize);
    new RegularEvent('click', this.toggleVisibility).delegateTo(document, this.identifier.hide);
    DocumentService.ready().then((): void => {
      this.registerPaginationEvents();
    });

    // multi record selection events
    new RegularEvent('multiRecordSelection:action:edit', this.onEditMultiple).bindTo(document);
    new RegularEvent('multiRecordSelection:action:copyMarked', (event: CustomEvent): void => {
      Recordlist.submitClipboardFormWithCommand('copyMarked', event.target as HTMLButtonElement);
    }).bindTo(document);
    new RegularEvent('multiRecordSelection:action:removeMarked', (event: CustomEvent): void => {
      Recordlist.submitClipboardFormWithCommand('removeMarked', event.target as HTMLButtonElement);
    }).bindTo(document);
  }

  private static submitClipboardFormWithCommand(cmd: string, target: HTMLButtonElement) {
    const clipboardForm = <HTMLFormElement>target.closest('form');
    if (!clipboardForm) {
      return;
    }
    const commandField = <HTMLInputElement>clipboardForm.querySelector('input[name="cmd"]');
    if (!commandField) {
      return;
    }
    commandField.value = cmd;
    clipboardForm.submit();
  }

  private static getReturnUrl(returnUrl: string): string {
    if (returnUrl === '') {
      returnUrl = top.list_frame.document.location.pathname + top.list_frame.document.location.search;
    }
    return encodeURIComponent(returnUrl);
  }

  public toggleClick = (e: MouseEvent, targetEl: HTMLElement): void => {
    e.preventDefault();

    const table = targetEl.dataset.table;
    const target = document.querySelector(targetEl.dataset.bsTarget) as HTMLElement;
    const isExpanded = target.dataset.state === 'expanded';
    const collapseIcon = targetEl.querySelector('.t3js-icon');
    const toggleIcon = isExpanded ? this.identifier.icons.expand : this.identifier.icons.collapse;

    Icons.getIcon(toggleIcon, Icons.sizes.small).then((icon: string): void => {
      collapseIcon.replaceWith(document.createRange().createContextualFragment(icon));
    });

    // Store collapse state in UC
    let storedModuleDataList = {};

    if (PersistentStorage.isset('moduleData.web_list.collapsedTables')) {
      storedModuleDataList = PersistentStorage.get('moduleData.web_list.collapsedTables');
    }

    const collapseConfig: Record<string, number> = {};
    collapseConfig[table] = isExpanded ? 1 : 0;

    storedModuleDataList = Object.assign(storedModuleDataList, collapseConfig);
    PersistentStorage.set('moduleData.web_list.collapsedTables', storedModuleDataList).then((): void => {
      target.dataset.state = isExpanded ? 'collapsed' : 'expanded';
    });
  };

  /**
   * Handles editing multiple records.
   */
  public onEditMultiple = (event: Event, target: HTMLElement): void => {
    event.preventDefault();
    let tableName: string = '';
    let returnUrl: string = '';
    let columnsOnly: Array<string> = [];
    const entityIdentifiers: Array<string> = [];

    if (event.type === 'multiRecordSelection:action:edit') {
      // In case the request is triggerd by the multi record selection event, handling
      // is slightly different since the event data already contain the selected records.
      const eventDetails: ActionEventDetails = (event as CustomEvent).detail as ActionEventDetails;
      const configuration: EditRecordsConfiguration = eventDetails.configuration;
      returnUrl = configuration.returnUrl || '';
      columnsOnly = configuration.columnsOnly || [];
      tableName = configuration.tableName || '';
      if (tableName === '') {
        return;
      }
      // Evaluate all checked records and if valid, add their uid to the list
      eventDetails.checkboxes.forEach((checkbox: HTMLInputElement): void => {
        const checkboxContainer: HTMLElement = checkbox.closest(MultiRecordSelectionSelectors.elementSelector);
        if (checkboxContainer !== null && checkboxContainer.dataset[configuration.idField]) {
          entityIdentifiers.push(checkboxContainer.dataset[configuration.idField]);
        }
      });
    } else {
      // Edit record request was triggered via t3js-* class on target.
      const tableContainer: HTMLElement = target.closest('[data-table]');
      if (tableContainer === null) {
        return;
      }
      tableName = tableContainer.dataset.table || '';
      if (tableName === '') {
        return;
      }
      returnUrl = target.dataset.returnUrl || '';
      columnsOnly = JSON.parse(target.dataset.columnsOnly || '{}');
      // Check if there are selected records, which would limit the records to edit
      const selection: NodeListOf<HTMLElement> = tableContainer.querySelectorAll(
        this.identifier.entity + '[data-uid][data-table="' + tableName + '"] td.col-checkbox input[type="checkbox"]:checked'
      );
      if (selection.length) {
        // If there are selected records, only those are added to the list
        selection.forEach((entity: HTMLInputElement): void => {
          entityIdentifiers.push((entity.closest(this.identifier.entity + selector`[data-uid][data-table="${tableName}"]`) as HTMLElement).dataset.uid);
        });
      } else {
        // Get all records for the current table and add their uid to the list
        const entities: NodeListOf<HTMLElement> = tableContainer.querySelectorAll(this.identifier.entity + selector`[data-uid][data-table="${tableName}"]`);
        if (!entities.length) {
          return;
        }
        entities.forEach((entity: HTMLElement): void => {
          entityIdentifiers.push(entity.dataset.uid);
        });
      }
    }

    if (!entityIdentifiers.length) {
      // Return in case no records to edit were found
      return;
    }

    let editUrl: string = top.TYPO3.settings.FormEngine.moduleUrl
      + '&edit[' + tableName + '][' + entityIdentifiers.join(',') + ']=edit'
      + '&returnUrl=' + Recordlist.getReturnUrl(returnUrl);

    if (columnsOnly.length > 0) {
      editUrl += columnsOnly.map((column: string, i: number): string => '&columnsOnly[' + tableName + '][' + i + ']=' + column).join('');
    }

    window.location.href = editUrl;
  };

  private readonly disableButton = (event: Event, target: HTMLElement): void => {
    target.setAttribute('disabled', 'disabled');
    target.classList.add('disabled');
  };

  private readonly toggleVisibility = (event: Event, target: HTMLButtonElement): void => {
    const rowElement = target.closest('tr[data-uid]');

    // Show spinner
    const buttonIconElement = target.querySelector('.t3js-icon');
    Icons.getIcon('spinner-circle', Icons.sizes.small).then((icon: string): void => {
      buttonIconElement.replaceWith(document.createRange().createContextualFragment(icon));
    });

    const isVisible = target.dataset.datahandlerStatus === 'visible';
    // Get Settings from element
    const settings = {
      table: target.dataset.datahandlerTable,
      uid: target.dataset.datahandlerUid,
      field: target.dataset.datahandlerField,
      visible: isVisible,
      overlayIcon: isVisible
        ? target.dataset.datahandlerRecordHiddenOverlayIcon ?? 'overlay-hidden'
        : target.dataset.datahandlerRecordVisibleOverlayIcon ?? null
    };

    const params = {
      data: {
        [settings.table]: {
          [settings.uid]: {
            [settings.field]: settings.visible
              ? target.dataset.datahandlerHiddenValue
              : target.dataset.datahandlerVisibleValue
          }
        }
      }
    };

    // Submit Data
    AjaxDataHandler.process(params).then((result: ResponseInterface): void => {
      if (!result.hasErrors) {
        // Inverse current state
        settings.visible = !(settings.visible);
        target.setAttribute('data-datahandler-status', settings.visible ? 'visible' : 'hidden');

        const elementLabel = settings.visible
          ? target.dataset.datahandlerVisibleLabel
          : target.dataset.datahandlerHiddenLabel;
        target.setAttribute('title', elementLabel);

        const elementIconIdentifier = settings.visible
          ? target.dataset.datahandlerVisibleIcon
          : target.dataset.datahandlerHiddenIcon;
        const iconElement = target.querySelector('.t3js-icon');
        Icons.getIcon(elementIconIdentifier, Icons.sizes.small).then((icon: string): void => {
          iconElement.replaceWith(document.createRange().createContextualFragment(icon));
        });

        // Set overlay for the record icon
        const recordIcon = rowElement.querySelector('.col-icon .t3js-icon');
        recordIcon.querySelector('.icon-overlay')?.remove();
        Icons.getIcon('miscellaneous-placeholder', Icons.sizes.small, settings.overlayIcon).then((icon: string): void => {
          const iconFragment = document.createRange().createContextualFragment(icon);
          recordIcon.append(iconFragment.querySelector('.icon-overlay'));
        });

        // Animate row
        const animationEvent = new RegularEvent('animationend', (): void => {
          rowElement.classList.remove('record-pulse');
          animationEvent.release();
        });
        animationEvent.bindTo(rowElement);
        rowElement.classList.add('record-pulse');

        // Refresh Pagetree
        if (settings.table === 'pages') {
          top.document.dispatchEvent(new CustomEvent('typo3:pagetree:refresh'));
        }
      }
    });
  };

  private readonly registerPaginationEvents = (): void => {
    document.querySelectorAll('.t3js-recordlist-paging').forEach((trigger: HTMLInputElement) => {
      trigger.addEventListener('keyup', (e: KeyboardEvent) => {
        e.preventDefault();
        let value = Number(trigger.value);
        const min = Number(trigger.min);
        const max = Number(trigger.max);
        if (min && value < min) {
          value = min;
        }
        if (max && value > max) {
          value = max;
        }
        trigger.value = value.toString(10);
        if (e.key === 'Enter' && value !== Number(trigger.dataset.currentpage)) {
          const form = trigger.closest('form[name^="list-table-form-"]') as HTMLFormElement;
          const submitUrl = new URL(form.action, window.origin);
          submitUrl.searchParams.set('pointer', value.toString());
          window.location.href = submitUrl.toString();
        }
      });
    });
  };
}

export default new Recordlist();
