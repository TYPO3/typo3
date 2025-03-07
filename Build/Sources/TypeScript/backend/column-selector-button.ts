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

import { html, css, type TemplateResult, LitElement } from 'lit';
import { customElement, property } from 'lit/decorators';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import { default as Modal, type ModalElement } from '@typo3/backend/modal';
import { lll } from '@typo3/core/lit-helper';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import Notification from '@typo3/backend/notification';

enum Selectors {
  columnsSelector = '.t3js-column-selector',
  columnsContainerSelector = '.t3js-column-selector-container',
  columnsFilterSelector = 'input[name="columns-filter"]',
  columnsSelectorActionsSelector = '.t3js-column-selector-actions'
}

enum SelectorActions {
  toggle = 'select-toggle',
  all = 'select-all',
  none = 'select-none'
}

/**
 * Module: @typo3/backend/column-selector-button
 *
 * @example
 * <typo3-backend-column-selector-button
 *    class="btn btn-default"
 *    data-url="/url/to/column/selector/form"
 *    data-target="/url/to/go/after/column/selection"
 *    data-title="Show columns"
 *    data-button-ok="Update"
 *    data-button-close="Cancel"
 *    data-error-message="Error"
 * >
 *   Show columns
 * </typo3-backend-column-selector-button>
 */
@customElement('typo3-backend-column-selector-button')
export class ColumnSelectorButton extends LitElement {
  static override styles = [css`:host { cursor: pointer; appearance: button; }`];

  @property({ type: String, attribute: 'data-url' }) modalUrl: string;
  @property({ type: String, attribute: 'data-target' }) modalTarget: string;
  @property({ type: String, attribute: 'data-title' }) modalTitle: string = 'Show columns';
  @property({ type: String, attribute: 'data-button-ok' }) buttonOk: string = lll('button.ok') || 'Update';
  @property({ type: String, attribute: 'data-button-close' }) buttonClose: string = lll('button.close') || 'Close';
  @property({ type: String, attribute: 'data-error-message' }) errorMessage: string = 'Could not update columns';

  public constructor() {
    super();
    this.addEventListener('click', (e: Event): void => {
      e.preventDefault();
      this.showColumnSelectorModal();
    });
    this.addEventListener('keydown', (e: KeyboardEvent): void => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        this.showColumnSelectorModal();
      }
    });
  }

  /**
   * Toggle selector actions state (enabled or disabled) depending
   * on the columns state (checked, unchecked, displayed or hidden)
   *
   * @param columns The columns
   * @param selectAll The "select all" action button
   * @param selectNone The "select none" action button
   * @param initialize Whether this is the initialize call - don't check hidden
   *                   state as all columns are displayed on initialization
   * @private
   */
  private static toggleSelectorActions(
    columns: NodeListOf<HTMLInputElement>,
    selectAll: HTMLButtonElement,
    selectNone: HTMLButtonElement,
    initialize: boolean = false
  ) {
    selectAll.classList.add('disabled');
    for (let i = 0; i < columns.length; i++) {
      if (!columns[i].disabled
        && !columns[i].checked
        && (initialize || !ColumnSelectorButton.isColumnHidden(columns[i]))
      ) {
        selectAll.classList.remove('disabled');
        break;
      }
    }
    selectNone.classList.add('disabled');
    for (let i = 0; i < columns.length; i++) {
      if (!columns[i].disabled
        && columns[i].checked
        && (initialize || !ColumnSelectorButton.isColumnHidden(columns[i]))
      ) {
        selectNone.classList.remove('disabled');
        break;
      }
    }
  }

  /**
   * Check if the given column is hidden by looking at it's container element
   *
   * @param column The column to check for
   * @private
   */
  private static isColumnHidden(column: HTMLInputElement): boolean {
    return column.closest(Selectors.columnsContainerSelector)?.classList.contains('hidden');
  }

  /**
   * Check each column if it matches the current search term.
   * If not, hide its outer container to not break the grid.
   *
   * @param columnsFilter The columns filter
   * @param columns The columns to check
   * @private
   */
  private static filterColumns(columnsFilter: HTMLInputElement, columns: NodeListOf<HTMLInputElement>): void {
    columns.forEach((column: HTMLInputElement) => {
      const columnContainer: HTMLDivElement = column.closest(Selectors.columnsContainerSelector);
      if (!column.disabled && columnContainer !== null) {
        const filterValue: string = columnContainer.querySelector('.form-check-label')?.textContent;
        if (filterValue && filterValue.length) {
          columnContainer.classList.toggle(
            'hidden',
            columnsFilter.value !== '' && !RegExp(columnsFilter.value, 'i').test(
              filterValue.trim().replace(/\[\]/g, '').replace(/\s+/g, ' ')
            )
          );
        }
      }
    });
  }

  public override connectedCallback(): void {
    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'button');
    }
    if (!this.hasAttribute('tabindex')) {
      this.setAttribute('tabindex', '0');
    }
  }

  protected override render(): TemplateResult {
    return html`<slot></slot>`;
  }

  private showColumnSelectorModal(): void {
    if (!this.modalUrl || !this.modalTarget) {
      // Don't render modal in case no url or target is given
      return;
    }

    const modal = Modal.advanced({
      content: this.modalUrl,
      title: this.modalTitle,
      severity: SeverityEnum.notice,
      size: Modal.sizes.medium,
      type: Modal.types.ajax,
      buttons: [
        {
          text: this.buttonClose,
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: (e: Event, modal: ModalElement): void => modal.hideModal()
        },
        {
          text: this.buttonOk,
          btnClass: 'btn-primary',
          name: 'update',
          trigger: (e: Event, modal: ModalElement): void => this.processSelection(modal)
        }
      ],
      ajaxCallback: (): void => this.handleModalContentLoaded(modal)
    });
  }

  private processSelection(currentModal: HTMLElement): void {
    const form: HTMLFormElement = currentModal.querySelector('form') as HTMLFormElement;
    if (form === null) {
      this.abortSelection();
      return;
    }
    (new AjaxRequest(TYPO3.settings.ajaxUrls.show_columns))
      .post(new FormData(form))
      .then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          // @todo This does not jump to the anchor (#t3-table-some_table) after the reload!!!
          this.ownerDocument.location.href = this.modalTarget;
          this.ownerDocument.location.reload();
        } else {
          Notification.error(data.message || 'No update was performed');
        }
        Modal.dismiss();
      })
      .catch(() => {
        this.abortSelection();
      });
  }

  private handleModalContentLoaded(currentModal: HTMLElement): void {
    const form: HTMLFormElement = currentModal.querySelector('form') as HTMLFormElement;
    if (form === null) {
      // Early return if modal content does not include a form
      return;
    }
    // Prevent the form from being submitted as the form data will be send via an ajax request
    form.addEventListener('submit', (e: Event): void => { e.preventDefault(); });

    const columns: NodeListOf<HTMLInputElement> = currentModal.querySelectorAll(Selectors.columnsSelector);
    const columnsFilter: HTMLInputElement = currentModal.querySelector(Selectors.columnsFilterSelector);
    const columnsSelectorActions: HTMLDivElement = currentModal.querySelector(Selectors.columnsSelectorActionsSelector);
    const selectAll: HTMLButtonElement = columnsSelectorActions.querySelector('button[data-action="' + SelectorActions.all + '"]');
    const selectNone: HTMLButtonElement = columnsSelectorActions.querySelector('button[data-action="' + SelectorActions.none + '"]');

    if (!columns.length || columnsFilter === null || selectAll === null || selectNone === null) {
      // Return in case required elements do not exist in the modal content
      return;
    }

    // First initialize select-all / select-none buttons
    ColumnSelectorButton.toggleSelectorActions(columns, selectAll, selectNone, true);

    // Add event listener for each column to toggle the selector actions after change
    columns.forEach((column: HTMLInputElement) => {
      column.addEventListener('change', (): void => {
        ColumnSelectorButton.toggleSelectorActions(columns, selectAll, selectNone);
      });
    });

    // Add event listener for keydown event for the columns filter, so we
    // can catch the "Escape" key, which would otherwise close the modal.
    columnsFilter.addEventListener('keydown', (e: KeyboardEvent): void => {
      const target = e.target as HTMLInputElement;
      if (e.code === 'Escape') {
        e.stopImmediatePropagation();
        target.value = '';
      }
    });

    // Add event listener for keydown event for the columns filter, allowing the "live filtering"
    columnsFilter.addEventListener('keyup', (e: KeyboardEvent): void => {
      ColumnSelectorButton.filterColumns(e.target as HTMLInputElement, columns);
      ColumnSelectorButton.toggleSelectorActions(columns, selectAll, selectNone);
    });

    // Catch browser specific "search" event, triggered on clicking the "clear" button
    columnsFilter.addEventListener('search', (e: Event): void => {
      ColumnSelectorButton.filterColumns(e.target as HTMLInputElement, columns);
      ColumnSelectorButton.toggleSelectorActions(columns, selectAll, selectNone);
    });

    // Add event listener for all columns select actions. querySelectorAll will return
    // at least two actions (selectAll and selectNone) which we checked above already
    columnsSelectorActions.querySelectorAll('button[data-action]').forEach((action: HTMLButtonElement) => {
      action.addEventListener('click', (e: Event): void => {
        e.preventDefault();

        const target: HTMLButtonElement = e.currentTarget as HTMLButtonElement;
        if (!target.dataset.action) {
          // Return if we don't deal with a valid action (No action defined)
          return;
        }

        // Perform requested action
        switch (target.dataset.action) {
          case SelectorActions.toggle:
            columns.forEach((column: HTMLInputElement) => {
              if (!column.disabled && !ColumnSelectorButton.isColumnHidden(column)) {
                column.checked = !column.checked;
              }
            });
            break;
          case SelectorActions.all:
            columns.forEach((column: HTMLInputElement) => {
              if (!column.disabled && !ColumnSelectorButton.isColumnHidden(column)) {
                column.checked = true;
              }
            });
            break;
          case SelectorActions.none:
            columns.forEach((column: HTMLInputElement) => {
              if (!column.disabled && !ColumnSelectorButton.isColumnHidden(column)) {
                column.checked = false;
              }
            });
            break;
          default:
            // Unknown action
            Notification.warning('Unknown selector action');
        }

        // After performing the action always toggle selector actions
        ColumnSelectorButton.toggleSelectorActions(columns, selectAll, selectNone);
      });
    });
  }

  private abortSelection(): void {
    Notification.error(this.errorMessage);
    Modal.dismiss();
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-column-selector-button': ColumnSelectorButton;
  }
}
