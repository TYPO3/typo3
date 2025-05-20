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

import { BroadcastMessage } from '@typo3/backend/broadcast-message';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import DocumentService from '@typo3/core/document-service';
import { SeverityEnum } from './enum/severity';
import ResponseInterface from './ajax-data-handler/response-interface';
import BroadcastService from '@typo3/backend/broadcast-service';
import Icons from './icons';
import Modal from './modal';
import Notification from './notification';
import RegularEvent from '@typo3/core/event/regular-event';
import { sudoModeInterceptor } from '@typo3/backend/security/sudo-mode-interceptor';

enum Identifiers {
  hide = 'button[data-datahandler-action="visibility"]',
  delete = '.t3js-record-delete',
  icon = '.t3js-icon',
}

interface AfterProcessEventDict {
  component: string;
  action: string;
  table: string;
  uid: number;
}

/**
 * Module: @typo3/backend/ajax-data-handler
 * Javascript functions to work with AJAX and interacting with Datahandler
 * through \TYPO3\CMS\Backend\Controller\SimpleDataHandlerController->processAjaxRequest (record_process route)
 */
class AjaxDataHandler {
  constructor() {
    DocumentService.ready().then((): void => {
      this.initialize();
    });
  }

  /**
   * Refresh the page tree
   */
  private static refreshPageTree(): void {
    top.document.dispatchEvent(new CustomEvent('typo3:pagetree:refresh'));
  }

  /**
   * AJAX call to record_process route (SimpleDataHandlerController->processAjaxRequest)
   * returns a jQuery Promise to work with
   *
   * @param {string | object} params
   * @returns {Promise<ResponseInterface>}
   */
  private static call(params: string | object): Promise<ResponseInterface> {
    return (new AjaxRequest(TYPO3.settings.ajaxUrls.record_process))
      .addMiddleware(sudoModeInterceptor)
      .withQueryArguments(params)
      .get()
      .then(async (response: AjaxResponse): Promise<ResponseInterface> => {
        return await response.resolve();
      });
  }

  /**
   * Generic function to call from the outside the script and validate directly showing errors
   *
   * @param {string | object} parameters
   * @param {AfterProcessEventDict} eventDict Dictionary used as event detail. This is private API yet.
   * @returns {Promise<ResponseInterface>}
   */
  public process(parameters: string | object, eventDict?: AfterProcessEventDict): Promise<ResponseInterface> {
    const promise = AjaxDataHandler.call(parameters);
    return promise.then((result: ResponseInterface): ResponseInterface => {
      if (result.hasErrors) {
        this.handleErrors(result);
      }

      if (eventDict) {
        const payload = { ...eventDict, hasErrors: result.hasErrors };
        const message = new BroadcastMessage(
          'datahandler',
          'process',
          payload
        );
        BroadcastService.post(message);

        const event = new CustomEvent('typo3:datahandler:process',{
          detail: {
            payload: payload
          }
        });
        document.dispatchEvent(event);
      }

      return result;
    });
  }

  // @todo: Many extensions rely on this behavior but it's misplaced in AjaxDataHandler. Move into recordlist.ts and deprecate in v11.
  private initialize(): void {
    // HIDE/UNHIDE: click events for all action icons to hide/unhide
    new RegularEvent('click', (e: Event, element: HTMLButtonElement): void => {
      e.preventDefault();
      this.handleVisibilityToggle(element);
    }).delegateTo(document, Identifiers.hide);

    // DELETE: click events for all action icons to delete
    new RegularEvent('click', (evt: Event, anchorElement: HTMLElement): void => {
      evt.preventDefault();

      const modal = Modal.confirm(anchorElement.dataset.title, anchorElement.dataset.message, SeverityEnum.warning, [
        {
          text: anchorElement.dataset.buttonCloseText || TYPO3.lang['button.cancel'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
        },
        {
          text: anchorElement.dataset.buttonOkText || TYPO3.lang['button.delete'] || 'Delete',
          btnClass: 'btn-warning',
          name: 'delete',
        },
      ]);
      modal.addEventListener('button.clicked', (e: Event): void => {
        if ((e.target as HTMLInputElement).getAttribute('name') === 'cancel') {
          modal.hideModal();
        } else if ((e.target as HTMLInputElement).getAttribute('name') === 'delete') {
          modal.hideModal();
          this.deleteRecord(anchorElement);
        }
      });
    }).delegateTo(document, Identifiers.delete);
  }

  private handleVisibilityToggle(element: HTMLButtonElement): void
  {
    const rowElement = element.closest('tr[data-uid]');

    // Show spinner
    const iconElement = element.querySelector(Identifiers.icon);
    this._showSpinnerIcon(iconElement);

    const isVisible = element.dataset.datahandlerStatus === 'visible';
    // Get Settings from element
    const settings = {
      table: element.dataset.datahandlerTable,
      uid: element.dataset.datahandlerUid,
      field: element.dataset.datahandlerField,
      visible: isVisible,
      overlayIcon: isVisible
        ? element.dataset.datahandlerRecordHiddenOverlayIcon ?? 'overlay-hidden'
        : element.dataset.datahandlerRecordVisibleOverlayIcon ?? null
    };

    const params = {
      data: {
        [settings.table]: {
          [settings.uid]: {
            [settings.field]: settings.visible
              ? element.dataset.datahandlerHiddenValue
              : element.dataset.datahandlerVisibleValue
          }
        }
      }
    };

    // Submit Data
    this.process(params).then((result: ResponseInterface): void => {
      if (!result.hasErrors) {
        // Inverse current state
        settings.visible = !(settings.visible);
        element.setAttribute('data-datahandler-status', settings.visible ? 'visible' : 'hidden');

        const elementLabel = settings.visible
          ? element.dataset.datahandlerVisibleLabel
          : element.dataset.datahandlerHiddenLabel;
        element.setAttribute('title', elementLabel);

        const elementIconIdentifier = settings.visible
          ? element.dataset.datahandlerVisibleIcon
          : element.dataset.datahandlerHiddenIcon;
        const iconElement = element.querySelector(Identifiers.icon);
        Icons.getIcon(elementIconIdentifier, Icons.sizes.small).then((icon: string): void => {
          iconElement.replaceWith(document.createRange().createContextualFragment(icon));
        });

        // Set overlay for the record icon
        const recordIcon = rowElement.querySelector('.col-icon ' + Identifiers.icon);
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
          AjaxDataHandler.refreshPageTree();
        }
      }
    });
  }

  /**
   * Delete record by given element (icon in table)
   * don't call it directly!
   */
  private deleteRecord(anchorElement: HTMLElement): void {
    const params = anchorElement.dataset.params;
    let iconElement = anchorElement.querySelector(Identifiers.icon);

    // add a spinner
    this._showSpinnerIcon(iconElement);

    const tableElement = anchorElement.closest('table[data-table]') as HTMLTableElement;
    const table = tableElement.dataset.table;
    const rowElement = anchorElement.closest('tr[data-uid]') as HTMLTableRowElement;
    const uid = parseInt(rowElement.dataset.uid, 10);

    // make the AJAX call to toggle the visibility
    const eventData = { component: 'datahandler', action: 'delete', table, uid };
    this.process(params, eventData).then((result: ResponseInterface): void => {
      // revert to the old class
      Icons.getIcon('actions-edit-delete', Icons.sizes.small).then((icon: string): void => {
        iconElement = anchorElement.querySelector(Identifiers.icon);
        iconElement.replaceWith(document.createRange().createContextualFragment(icon));
      });
      if (!result.hasErrors) {
        const panel = anchorElement.closest('.recordlist');
        const panelHeading = panel.querySelector('.recordlist-heading-title');
        const translatedRowElements = tableElement.querySelectorAll('[data-l10nparent="' + uid + '"]');
        translatedRowElements.forEach((translatedRowElement: HTMLTableRowElement): void => {
          new RegularEvent('transitionend', (): void => {
            translatedRowElement.remove();
          }).bindTo(translatedRowElement);
          translatedRowElement.classList.add('record-deleted');
        });

        new RegularEvent('transitionend', (): void => {
          rowElement.remove();

          if (tableElement.querySelectorAll('tbody tr').length === 0) {
            panel.remove();
          }
        }).bindTo(rowElement);
        rowElement.classList.add('record-deleted');

        if (anchorElement.dataset.l10parent === '0' || anchorElement.dataset.l10parent === '') {
          const count = parseInt(panelHeading.querySelector('.t3js-table-total-items').textContent, 10);
          panelHeading.querySelector('.t3js-table-total-items').textContent = (count - 1).toString();
        }

        if (table === 'pages') {
          AjaxDataHandler.refreshPageTree();
        }
      }
    });
  }

  /**
   * Handle the errors from result object
   *
   * @param {Object} result
   */
  private handleErrors(result: ResponseInterface): void {
    for (const message of result.messages) {
      Notification.error(message.title, message.message);
    }
  }

  /**
   * Replace the given icon with a spinner icon
   */
  private _showSpinnerIcon(iconElement: Element): void {
    Icons.getIcon('spinner-circle', Icons.sizes.small).then((icon: string): void => {
      iconElement.replaceWith(document.createRange().createContextualFragment(icon));
    });
  }
}

export default new AjaxDataHandler();
