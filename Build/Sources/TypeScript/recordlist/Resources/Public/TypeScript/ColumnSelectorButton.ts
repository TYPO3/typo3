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

import {html, TemplateResult, LitElement} from 'lit';
import {customElement, property} from 'lit/decorators';
import {SeverityEnum} from 'TYPO3/CMS/Backend/Enum/Severity';
import Severity = require('TYPO3/CMS/Backend/Severity');
import Modal = require('TYPO3/CMS/Backend/Modal');
import {lll} from 'TYPO3/CMS/Core/lit-helper';
import AjaxRequest from 'TYPO3/CMS/Core/Ajax/AjaxRequest';
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import Notification = require('TYPO3/CMS/Backend/Notification');

enum Selectors {
  columnSelectors = '.t3js-record-column-selector',
  columnSelectorActionsSelector = '.t3js-record-column-selector-actions'
}

enum SelectorActions {
  toggle = 'select-toggle',
  all = 'select-all',
  none = 'select-none'
}

/**
 * Module: TYPO3/CMS/Recordlist/ColumnSelectorButton
 *
 * @example
 * <typo3-recordlist-column-selector-button
 *    url="/url/to/column/selector/form"
 *    target="/url/to/go/after/column/selection"
 *    title="Show columns"
 *    ok="Update"
 *    close="Cancel"
 *    close="Error"
 * >
 *   <button>Show columns/button>
 * </typo3-recordlist-column-selector-button>
 */
@customElement('typo3-recordlist-column-selector-button')
class ColumnSelectorButton extends LitElement {
  @property({type: String}) url: string;
  @property({type: String}) target: string;
  @property({type: String}) title: string = 'Show columns';
  @property({type: String}) ok: string = lll('button.ok') || 'Update';
  @property({type: String}) close: string = lll('button.close') || 'Close';
  @property({type: String}) error: string = 'Could not update columns';

  private static toggleSelectors(
    columnSelectors: NodeListOf<HTMLInputElement>,
    selectAll: HTMLButtonElement,
    selectNone: HTMLButtonElement
  ) {
    selectAll.classList.add('disabled')
    for (let i=0; i < columnSelectors.length; i++) {
      if (!columnSelectors[i].disabled && !columnSelectors[i].checked) {
        selectAll.classList.remove('disabled')
        break;
      }
    }
    selectNone.classList.add('disabled')
    for (let i=0; i < columnSelectors.length; i++) {
      if (!columnSelectors[i].disabled && columnSelectors[i].checked) {
        selectNone.classList.remove('disabled')
        break;
      }
    }
  }

  public constructor() {
    super();
    this.addEventListener('click', (e: Event): void => {
      e.preventDefault();
      this.showColumnSelectorModal();
    });
  }

  protected render(): TemplateResult {
    return html`<slot></slot>`;
  }

  private showColumnSelectorModal(): void {
    if (!this.url || !this.target) {
      // Don't render modal in case no url or target is given
      return;
    }

    Modal.advanced({
      content: this.url,
      title: this.title,
      severity: SeverityEnum.notice,
      size: Modal.sizes.medium,
      type: Modal.types.ajax,
      buttons: [
        {
          text: this.close,
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: (): void => Modal.dismiss(),
        },
        {
          text: this.ok,
          btnClass: 'btn-' + Severity.getCssClass(SeverityEnum.info),
          name: 'update',
          trigger: (): void => this.proccessSelection(Modal.currentModal[0])
        }
      ],
      ajaxCallback: (): void => this.handleModalContentLoaded(Modal.currentModal[0])
    });
  }

  private proccessSelection(currentModal: HTMLElement): void {
    const form: HTMLFormElement = currentModal.querySelector('form') as HTMLFormElement;
    if (form === null) {
      this.abortSelection();
      return;
    }
    (new AjaxRequest(TYPO3.settings.ajaxUrls.record_show_columns))
      .post('', {body: new FormData(form)})
      .then(async (response: AjaxResponse): Promise<any> => {
        const data = await response.resolve();
        if (data.success === true) {
          // @todo This does not jump to the anchor (#t3-table-some_table) after the reload!!!
          this.ownerDocument.location.href = this.target
          this.ownerDocument.location.reload(true);
        } else {
          Notification.error(data.message || 'No update was performed');
        }
        Modal.dismiss();
      })
      .catch(() => {
        this.abortSelection();
      })
  }

  private handleModalContentLoaded(currentModal: HTMLElement): void {
    const form: HTMLFormElement = currentModal.querySelector('form') as HTMLFormElement;
    if (form === null) {
      // Early return if modal content does not include a form
      return;
    }
    // Prevent the form from being submitted as the form data will be send via an ajax request
    form.addEventListener('submit', (e: Event): void => {e.preventDefault()});

    const columnSelectors: NodeListOf<HTMLInputElement> = currentModal.querySelectorAll(Selectors.columnSelectors);
    const columnSelectorActions: HTMLDivElement = currentModal.querySelector(Selectors.columnSelectorActionsSelector);
    const selectAll: HTMLButtonElement = columnSelectorActions.querySelector('button[data-action="' + SelectorActions.all + '"]');
    const selectNone: HTMLButtonElement = columnSelectorActions.querySelector('button[data-action="' + SelectorActions.none + '"]');

    if (selectAll === null || selectNone === null || !columnSelectors.length) {
      // Return in case required elements do not exist in the modal content
      return;
    }

    // Initialize select-all / select-none buttons
    ColumnSelectorButton.toggleSelectors(columnSelectors, selectAll, selectNone);

    // Add event listener for each column selector to toggle the selector actions after change
    columnSelectors.forEach((column: HTMLInputElement) => {
      column.addEventListener('change', (): void => {
        ColumnSelectorButton.toggleSelectors(columnSelectors, selectAll, selectNone);
      });
    });

    // Add event listener for selector actions
    columnSelectorActions.addEventListener('click', (e: Event): void => {
      e.preventDefault();

      const target: HTMLElement = e.target as HTMLElement;
      if (target.nodeName !== 'BUTTON' || !target.dataset.action) {
        // Return if we don't deal with a valid action (Either no button or no action defined)
        return;
      }

      // Perform requested action
      switch (target.dataset.action) {
        case SelectorActions.toggle:
          columnSelectors.forEach((column: HTMLInputElement) => {
            if (!column.disabled) {
              column.checked = !column.checked;
            }
          });
          break;
        case SelectorActions.all:
          columnSelectors.forEach((column: HTMLInputElement) => {
            if (!column.disabled) {
              column.checked = true;
            }
          });
          break;
        case SelectorActions.none:
          columnSelectors.forEach((column: HTMLInputElement) => {
            if (!column.disabled) {
              column.checked = false;
            }
          });
          break;
        default:
          // Unknown action
          Notification.warning('Unknown selector action');
      }

      // After performing the action always toggle selectors
      ColumnSelectorButton.toggleSelectors(columnSelectors, selectAll, selectNone);
    });
  }

  private abortSelection(): void {
    Notification.error(this.error);
    Modal.dismiss();
  }
}
