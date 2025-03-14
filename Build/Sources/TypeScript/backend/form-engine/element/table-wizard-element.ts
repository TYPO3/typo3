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

import { html, LitElement, type TemplateResult } from 'lit';
import { customElement, property } from 'lit/decorators';
import { lll } from '@typo3/core/lit-helper';
import '@typo3/backend/element/icon-element';
import Modal from '@typo3/backend/modal';

/**
 * Module: @typo3/backend/form-engine/element/table-wizard-element
 *
 * @example
 * <typo3-formengine-table-wizard table="[["quot;a"quot;,"quot;b"quot;],["quot;c"quot;,"quot;d"quot;]]">
 * </typo3-formengine-table-wizard>
 *
 * This is based on W3C custom elements ("web components") specification, see
 * https://developer.mozilla.org/en-US/docs/Web/Web_Components/Using_custom_elements
 */
@customElement('typo3-formengine-table-wizard')
export class TableWizardElement extends LitElement {
  @property({ type: String }) type: string = 'textarea';
  @property({ type: String }) selectorData: string = '';
  @property({ type: String }) delimiter: string = '|';
  @property({ type: String }) enclosure: string = '';
  @property({ type: Number, attribute: 'append-rows' }) appendRows: number = 1;

  private table: string[][] = [];

  private get firstRow(): string[] {
    return this.table[0] || [];
  }

  public override connectedCallback(): void {
    super.connectedCallback();

    this.selectorData = this.getAttribute('selector');
    this.delimiter = this.getAttribute('delimiter');
    this.enclosure = this.getAttribute('enclosure') || '';
    this.readTableFromTextarea();
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult {
    return this.renderTemplate();
  }

  private provideMinimalTable(): void {
    if (this.table.length === 0 || this.firstRow.length === 0) {
      // create a table with one row and one column
      this.table = [
        ['']
      ];
    }
  }

  private readTableFromTextarea(): void {
    // Note: We do not wait for `DocumentService.ready()` here, as the <textarea> is placed as a previous sibling before this element in DOM
    const textarea: HTMLTextAreaElement = document.querySelector(this.selectorData);
    const table: string[][] = [];

    textarea.value.split('\n').forEach((row: string) => {
      if (row !== '') {
        if(this.enclosure) {
          row = row.replace(new RegExp(this.enclosure, 'g'), '');
        }

        const cols = row.split(this.delimiter);
        table.push(cols);
      }
    });

    this.table = table;
  }

  private writeTableSyntaxToTextarea(): void {
    const textarea: HTMLTextAreaElement = document.querySelector(this.selectorData);
    let text = '';
    this.table.forEach((row) => {
      const count = row.length;
      text += row.reduce((result, word, index) => {
        // Do not add delimiter at the end of each row
        const delimiter = (count - 1) === index ? '' : this.delimiter;
        // Substitute newlines by `<br>`
        word = word.replace(/\r?\n/g, '<br>');
        // Build enclosed representation of column value
        return result + this.enclosure + word + this.enclosure + delimiter;
      }, '') + '\n';
    });
    textarea.value = text;
    textarea.dispatchEvent(new CustomEvent('change', { bubbles: true }));
  }

  private modifyTable(evt: Event, rowIndex: number, colIndex: number): void {
    const target = evt.target as HTMLInputElement | HTMLTextAreaElement;
    this.table[rowIndex][colIndex] = target.value;
    this.writeTableSyntaxToTextarea();
    this.requestUpdate();
  }

  private toggleType(): void {
    this.type = this.type === 'input' ? 'textarea' : 'input';
  }

  private moveColumn(col: number, target: number): void {
    this.table = this.table.map((row: string[]): string[] => {
      const temp = row.splice(col, 1);
      row.splice(target, 0, ...temp);
      return row;
    });
    this.writeTableSyntaxToTextarea();
    this.requestUpdate();
  }

  private appendColumn(evt: Event, col: number): void {
    this.table = this.table.map((row: string[]): string[] => {
      row.splice(col + 1, 0, '');
      return row;
    });
    this.writeTableSyntaxToTextarea();
    this.requestUpdate();
  }

  private removeColumn(evt: Event, col: number): void {
    this.table = this.table.map((row: string[]): string[] => {
      row.splice(col, 1);
      return row;
    });
    this.writeTableSyntaxToTextarea();
    this.requestUpdate();
  }

  private moveRow(evt: Event, row: number, target: number): void {
    const temp = this.table.splice(row, 1);
    this.table.splice(target, 0, ...temp);
    this.writeTableSyntaxToTextarea();
    this.requestUpdate();
  }

  private appendRow(evt: Event, row: number): void {
    const columns = this.firstRow.concat().fill('');
    const rows = (new Array(this.appendRows)).fill(columns);
    this.table.splice(row + 1, 0, ...rows);
    this.writeTableSyntaxToTextarea();
    this.requestUpdate();
  }

  private removeRow(evt: Event, row: number): void {
    this.table.splice(row, 1);
    this.writeTableSyntaxToTextarea();
    this.requestUpdate();
  }

  private renderTemplate(): TemplateResult {
    this.provideMinimalTable();
    const colIndexes = Object.keys(this.firstRow).map((item: string) => parseInt(item, 10));
    const lastColIndex = colIndexes[colIndexes.length - 1];
    const lastRowIndex = this.table.length - 1;

    return html`
      <div class="table-fit table-fit-inline-block">
        <table class="table table-center">
          <thead>
            <th>${this.renderTypeButton()}</th>
            ${colIndexes.map((colIndex: number) => html`
            <th>${this.renderColButtons(colIndex, lastColIndex)}</th>
            `)}
          </thead>
          <tbody>
            ${this.table.map((row: string[], rowIndex: number) => html`
            <tr>
              <td>${this.renderRowButtons(rowIndex, lastRowIndex)}</td>
              ${row.map((value: string, colIndex: number) => html`
              <td>${this.renderDataElement(value, rowIndex, colIndex)}</td>
              `)}
            </tr>
            `)}
          </tbody>
        </table>
      </div>
    `;
  }

  private renderDataElement(value: string, rowIndex: number, colIndex: number): TemplateResult {
    const modifyTable = (evt: Event) => this.modifyTable(evt, rowIndex, colIndex);
    switch (this.type) {
      case 'input':
        return html`
          <input class="form-control" type="text" data-row="${rowIndex}" data-col="${colIndex}"
            @change="${modifyTable}" .value="${value.replace(/\n/g, '<br>')}">
        `;
      case 'textarea':
      default:
        return html`
          <textarea class="form-control" rows="6" data-row="${rowIndex}" data-col="${colIndex}"
            @change="${modifyTable}" .value="${value.replace(/<br[ ]*\/?>/g, '\n')}"></textarea>
        `;
    }
  }

  private renderTypeButton(): TemplateResult {
    return html`
      <span class="btn-group">
        <button class="btn btn-default" type="button" title="${lll('table_smallFields')}"
                @click="${() => this.toggleType()}">
          <typo3-backend-icon identifier="${this.type === 'input' ? 'actions-chevron-expand' : 'actions-chevron-contract'}" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${lll('table_setCount')}"
                @click="${(evt: Event) => this.showTableConfigurationModal(evt)}">
          <typo3-backend-icon identifier="actions-plus" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${lll('table_showCode')}"
                @click="${() => this.showTableSyntax()}">
          <typo3-backend-icon identifier="actions-code" size="small"></typo3-backend-icon>
        </button>
      </span>
    `;
  }

  private renderColButtons(col: number, last: number): TemplateResult {
    const leftButton = {
      title: col === 0 ? lll('table_end') : lll('table_left'),
      class: col === 0 ? 'bar-right' : 'left',
      target: col === 0 ? last : col - 1,
    };
    const rightButton = {
      title: col === last ? lll('table_start') : lll('table_right'),
      class: col === last ? 'bar-left' : 'right',
      target: col === last ? 0 : col + 1,
    };
    return html`
      <span class="btn-group">
        <button class="btn btn-default" type="button" title="${leftButton.title}"
                @click="${() => this.moveColumn(col, leftButton.target)}">
          <typo3-backend-icon identifier="actions-chevron-${leftButton.class}" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${rightButton.title}"
                @click="${() => this.moveColumn(col, rightButton.target)}">
          <typo3-backend-icon identifier="actions-chevron-${rightButton.class}" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${lll('table_removeColumn')}"
                @click="${(evt: Event) => this.removeColumn(evt, col)}">
          <typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${lll('table_addColumn')}"
                @click="${(evt: Event) => this.appendColumn(evt, col)}">
          <typo3-backend-icon identifier="actions-plus" size="small"></typo3-backend-icon>
        </button>
      </span>
    `;
  }

  private renderRowButtons(row: number, last: number): TemplateResult {
    const topButton = {
      title: row === 0 ? lll('table_bottom') : lll('table_up'),
      class: row === 0 ? 'bar-down' : 'up',
      target: row === 0 ? last : row - 1,
    };
    const bottomButton = {
      title: row === last ? lll('table_top') : lll('table_down'),
      class: row === last ? 'bar-up' : 'down',
      target: row === last ? 0 : row + 1,
    };
    return html`
      <span class="btn-group${this.type === 'input' ? '' : '-vertical'}">
        <button class="btn btn-default" type="button" title="${topButton.title}"
                @click="${(evt: Event) => this.moveRow(evt, row, topButton.target)}">
          <typo3-backend-icon identifier="actions-chevron-${topButton.class}" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${bottomButton.title}"
                @click="${(evt: Event) => this.moveRow(evt, row, bottomButton.target)}">
          <typo3-backend-icon identifier="actions-chevron-${bottomButton.class}" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${lll('table_removeRow')}"
                @click="${(evt: Event) => this.removeRow(evt, row)}">
          <typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon>
        </button>
        <button class="btn btn-default" type="button" title="${lll('table_addRow')}"
                @click="${(evt: Event) => this.appendRow(evt, row)}">
          <typo3-backend-icon identifier="actions-plus" size="small"></typo3-backend-icon>
        </button>
      </span>
    `;
  }

  private showTableConfigurationModal(evt: Event): void {
    const lastColIndex: number = this.firstRow.length;
    const lastRowIndex: number = this.table.length;
    const initRowValue: number = lastRowIndex || 1;
    const initTableValue: number = lastColIndex || 1;

    const modal = Modal.advanced({
      content: html`
        <div class="form-group">
          <label for="t3js-expand-rows" class="form-label">${lll('table_rowCount')}</label>
          <input id="t3js-expand-rows" class="form-control" type="number" min="1" required value="${initRowValue}">
        </div>
        <div class="form-group">
          <label for="t3js-expand-cols" class="form-label">${lll('table_colCount')}</label>
          <input id="t3js-expand-cols" class="form-control" type="number" min="1" required value="${initTableValue}">
        </div>
      `,
      title: lll('table_setCountHeadline'),
      size: Modal.sizes.small,
      buttons: [
        {
          text: lll('labels.cancel') || 'Cancel',
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: (): void => modal.hideModal(),
        },
        {
          text: lll('table_buttonUpdate') || 'Update',
          active: true,
          btnClass: 'btn-primary',
          name: 'apply',
          trigger: (): void => {
            const rows: HTMLInputElement = modal.querySelector('#t3js-expand-rows');
            const cols: HTMLInputElement = modal.querySelector('#t3js-expand-cols');

            if (rows === null || cols === null) {
              return;
            }

            if (rows.checkValidity() && cols.checkValidity()) {
              const modifyRows: number = Number(rows.value) - lastRowIndex;
              const modifyCols: number = Number(cols.value) - lastColIndex;
              this.setColAndRowCount(evt, modifyCols, modifyRows);
              modal.hideModal();
            } else {
              rows.reportValidity();
              cols.reportValidity();
            }
          }
        }
      ]
    });
  }

  private showTableSyntax(): void {
    const textarea: HTMLTextAreaElement = document.querySelector(this.selectorData);
    const modal = Modal.advanced({
      content: html`
        <div class="form-group">
          <label for="table-wizard-textarea-raw" class="form-label">${lll('table_showCodeLabel')}</label>
          <textarea id="table-wizard-textarea-raw" rows="8" class="form-control">${textarea.value}</textarea>
        </div>
      `,
      title: lll('table_showCodeHeadline'),
      size: Modal.sizes.small,
      buttons: [
        {
          text: lll('labels.cancel') || 'Cancel',
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: (): void => modal.hideModal(),
        },
        {
          text: lll('table_buttonUpdate') || 'Update',
          active: true,
          btnClass: 'btn-primary',
          name: 'apply',
          trigger: (): void => {
            // Apply table changes
            textarea.value = modal.querySelector('textarea').value;
            textarea.dispatchEvent(new CustomEvent('change', { bubbles: true }));
            this.readTableFromTextarea();
            this.requestUpdate();

            modal.hideModal();
          }
        }
      ]
    });
  }

  /**
   * Allow user to set a specific row/col count
   *
   * @param evt
   * @param colCount
   * @param rowCount
   * @private
   */
  private setColAndRowCount(evt: Event, colCount: number, rowCount: number) {
    const lastRowIndex: number = this.table.length;

    if (rowCount > 0) {
      for (let count = 0; count < rowCount; count++) {
        this.appendRow(evt, lastRowIndex);
      }
    } else {
      for (let count = 0; count < Math.abs(rowCount); count++) {
        this.removeRow(evt, this.table.length - 1);
      }
    }

    if (colCount > 0) {
      for (let count = 0; count < colCount; count++) {
        this.appendColumn(evt, colCount);
      }
    } else {
      for (let count = 0; count < Math.abs(colCount); count++) {
        this.removeColumn(evt, this.firstRow.length - 1);
      }
    }
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-formengine-table-wizard': TableWizardElement;
  }
}
