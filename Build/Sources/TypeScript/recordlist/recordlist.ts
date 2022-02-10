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

import $ from 'jquery';
import Icons from '@typo3/backend/icons';
import PersistentStorage from '@typo3/backend/storage/persistent';
import RegularEvent from '@typo3/core/event/regular-event';
import Tooltip from '@typo3/backend/tooltip';
import DocumentService from '@typo3/core/document-service';
import {ActionConfiguration, ActionEventDetails} from '@typo3/backend/multi-record-selection-action';
import Modal from '@typo3/backend/modal';
import {SeverityEnum} from '@typo3/backend/enum/severity';
import Severity from '@typo3/backend/severity';

interface IconIdentifier {
  collapse: string;
  expand: string;
  editMultiple: string;
}
interface RecordlistIdentifier {
  entity: string;
  toggle: string;
  localize: string;
  searchboxToolbar: string;
  searchboxToggle: string;
  searchField: string;
  icons: IconIdentifier;
}
interface DataHandlerEventPayload {
  action: string;
  component: string;
  table: string;
  uid: number;
}
interface EditRecordsConfiguration extends ActionConfiguration {
  tableName: string;
  returnUrl: string;
}
interface DeleteRecordsConfiguration extends ActionConfiguration {
  ok: string;
  title: string;
  content: string;
}

/**
 * Module: @typo3/recordlist/recordlist
 * Usability improvements for the record list
 * @exports @typo3/recordlist/recordlist
 */
class Recordlist {
  identifier: RecordlistIdentifier = {
    entity: '.t3js-entity',
    toggle: '.t3js-toggle-recordlist',
    localize: '.t3js-action-localize',
    searchboxToolbar: '#db_list-searchbox-toolbar',
    searchboxToggle: '.t3js-toggle-search-toolbox',
    searchField: '#search_field',
    icons: {
      collapse: 'actions-view-list-collapse',
      expand: 'actions-view-list-expand',
      editMultiple: '.t3js-record-edit-multiple',
    },
  };

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

  constructor() {
    $(document).on('click', this.identifier.toggle, this.toggleClick);
    $(document).on('click', this.identifier.icons.editMultiple, this.onEditMultiple);
    $(document).on('click', this.identifier.localize, this.disableButton);
    $(document).on('click', this.identifier.searchboxToggle, this.toggleSearchbox);
    DocumentService.ready().then((): void => {
      Tooltip.initialize('.table-fit a[title]');
      this.registerPaginationEvents();
    });
    new RegularEvent('typo3:datahandler:process', this.handleDataHandlerResult.bind(this)).bindTo(document);

    // multi record selection events
    new RegularEvent('multiRecordSelection:action:edit', this.onEditMultiple).bindTo(document);
    new RegularEvent('multiRecordSelection:action:delete', this.deleteMultiple).bindTo(document);
    new RegularEvent('multiRecordSelection:action:copyMarked', (event: CustomEvent): void => {
      Recordlist.submitClipboardFormWithCommand('copyMarked', event.target as HTMLButtonElement)
    }).bindTo(document);
    new RegularEvent('multiRecordSelection:action:removeMarked', (event: CustomEvent): void => {
      Recordlist.submitClipboardFormWithCommand('removeMarked', event.target as HTMLButtonElement)
    }).bindTo(document);
  }

  public toggleClick = (e: JQueryEventObject): void => {
    e.preventDefault();

    const $me = $(e.currentTarget);
    const table = $me.data('table');
    const $target = $($me.data('bs-target'));
    const isExpanded = $target.data('state') === 'expanded';
    const $collapseIcon = $me.find('.collapseIcon');
    const toggleIcon = isExpanded ? this.identifier.icons.expand : this.identifier.icons.collapse;

    Icons.getIcon(toggleIcon, Icons.sizes.small).then((icon: string): void => {
      $collapseIcon.html(icon);
    });

    // Store collapse state in UC
    let storedModuleDataList = {};

    if (PersistentStorage.isset('moduleData.web_list.collapsedTables')) {
      storedModuleDataList = PersistentStorage.get('moduleData.web_list.collapsedTables');
    }

    const collapseConfig: any = {};
    collapseConfig[table] = isExpanded ? 1 : 0;

    $.extend(storedModuleDataList, collapseConfig);
    PersistentStorage.set('moduleData.web_list.collapsedTables', storedModuleDataList).done((): void => {
      $target.data('state', isExpanded ? 'collapsed' : 'expanded');
    });
  }

  /**
   * Handles editing multiple records.
   */
  public onEditMultiple = (event: Event): void => {
    event.preventDefault();
    let tableName: string = '';
    let returnUrl: string = '';
    let columnsOnly: string = '';
    let entityIdentifiers: Array<string> = [];

    if (event.type === 'multiRecordSelection:action:edit') {
      // In case the request is triggerd by the multi record selection event, handling
      // is slightly different since the event data already contain the selected records.
      const eventDetails: ActionEventDetails = (event as CustomEvent).detail as ActionEventDetails;
      const configuration: EditRecordsConfiguration = eventDetails.configuration;
      returnUrl = configuration.returnUrl || '';
      tableName = configuration.tableName || '';
      if (tableName === '') {
        return;
      }
      // Evaluate all checked records and if valid, add their uid to the list
      eventDetails.checkboxes.forEach((checkbox: HTMLInputElement): void => {
        const checkboxContainer: HTMLElement = checkbox.closest('tr');
        if (checkboxContainer !== null && checkboxContainer.dataset[configuration.idField]) {
          entityIdentifiers.push(checkboxContainer.dataset[configuration.idField]);
        }
      });
    } else {
      // Edit record request was triggered via t3js-* class.
      const target: HTMLElement = event.currentTarget as HTMLElement;
      const tableContainer: HTMLElement = target.closest('[data-table]');
      if (tableContainer === null) {
        return;
      }
      tableName = tableContainer.dataset.table || '';
      if (tableName === '') {
        return;
      }
      returnUrl = target.dataset.returnUrl || '';
      columnsOnly = target.dataset.columnsOnly || '';
      // Check if there are selected records, which would limit the records to edit
      const selection: NodeListOf<HTMLElement> = tableContainer.querySelectorAll(
        this.identifier.entity + '[data-uid][data-table="' + tableName + '"] td.col-selector input[type="checkbox"]:checked'
      );
      if (selection.length) {
        // If there are selected records, only those are added to the list
        selection.forEach((entity: HTMLInputElement): void => {
          entityIdentifiers.push((entity.closest(this.identifier.entity + '[data-uid][data-table="' + tableName + '"]') as HTMLElement).dataset.uid);
        })
      } else {
        // Get all records for the current table and add their uid to the list
        const entities: NodeListOf<HTMLElement> = tableContainer.querySelectorAll(this.identifier.entity + '[data-uid][data-table="' + tableName + '"]');
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

    if (columnsOnly !== '') {
      editUrl += '&columnsOnly=' + columnsOnly;
    }

    window.location.href = editUrl;
  }

  private disableButton = (event: JQueryEventObject): void => {
    const $me = $(event.currentTarget);

    $me.prop('disable', true).addClass('disabled');
  }

  private toggleSearchbox = (): void => {
    const toolbar: JQuery = $(this.identifier.searchboxToolbar);
    toolbar.toggle();
    if (toolbar.is(':visible')) {
      $(this.identifier.searchField).focus();
    }
  };

  private handleDataHandlerResult(e: CustomEvent): void {
    const payload = e.detail.payload;
    if (payload.hasErrors) {
      return;
    }

    if (payload.component === 'datahandler') {
      // In this case the delete action was triggered by AjaxDataHandler itself, which currently has its own handling.
      // Visual handling is about to get decoupled from data handling itself, thus the logic is duplicated for now.
      return;
    }

    if (payload.action === 'delete') {
      this.deleteRow(payload);
    }
  };

  private deleteRow = (payload: DataHandlerEventPayload): void => {
    const $tableElement = $(`table[data-table="${payload.table}"]`);
    const $rowElement = $tableElement.find(`tr[data-uid="${payload.uid}"]`);
    const $panel = $tableElement.closest('.panel');
    const $panelHeading = $panel.find('.panel-heading');
    const $translatedRowElements = $tableElement.find(`[data-l10nparent="${payload.uid}"]`);

    const $rowElements = $().add($rowElement).add($translatedRowElements);
    $rowElements.fadeTo('slow', 0.4, (): void => {
      $rowElements.slideUp('slow', (): void => {
        $rowElements.remove();
        if ($tableElement.find('tbody tr').length === 0) {
          $panel.slideUp('slow');
        }
      });
    });
    if ($rowElement.data('l10nparent') === '0' || $rowElement.data('l10nparent') === '') {
      const count = Number($panelHeading.find('.t3js-table-total-items').html());
      $panelHeading.find('.t3js-table-total-items').text(count - 1);
    }

    if (payload.table === 'pages') {
      top.document.dispatchEvent(new CustomEvent('typo3:pagetree:refresh'));
    }
  }

  private deleteMultiple (event: CustomEvent): void {
    event.preventDefault();
    const eventDetails: ActionEventDetails = event.detail as ActionEventDetails;
    const configuration: DeleteRecordsConfiguration = eventDetails.configuration;
    Modal.advanced({
      title: configuration.title || 'Delete',
      content: configuration.content || 'Are you sure you want to delete those records?',
      severity: SeverityEnum.warning,
      buttons: [
        {
          text: TYPO3.lang['button.close'] || 'Close',
          active: true,
          btnClass: 'btn-default',
          trigger: (): JQuery => Modal.currentModal.trigger('modal-dismiss')
        },
        {
          text: configuration.ok || TYPO3.lang['button.ok'] || 'OK',
          btnClass: 'btn-' + Severity.getCssClass(SeverityEnum.warning),
          trigger: (): void => {
            Modal.currentModal.trigger('modal-dismiss');
            Recordlist.submitClipboardFormWithCommand('delete', event.target as HTMLButtonElement)
          }
        }
      ]
    });
  }

  private registerPaginationEvents = (): void => {
    document.querySelectorAll('.t3js-recordlist-paging').forEach((trigger: HTMLInputElement) => {
      trigger.addEventListener('keyup', (e: KeyboardEvent) => {
        e.preventDefault();
        let value = parseInt(trigger.value, 10);
        if (value < parseInt(trigger.min, 10)) {
          value = parseInt(trigger.min, 10);
        }
        if (value > parseInt(trigger.max, 10)) {
          value = parseInt(trigger.max, 10);
        }
        if (e.key === 'Enter' && value !== parseInt(trigger.dataset.currentpage, 10)) {
          window.location.href = trigger.dataset.currenturl + value.toString();
        }
      });
    });
  }
}

export default new Recordlist();
