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

import { SeverityEnum } from './enum/severity';
import 'bootstrap';
import { default as Modal, ModalElement } from '@typo3/backend/modal';
import SecurityUtility from '@typo3/core/security-utility';
import { customElement, property } from 'lit/decorators';
import { html, LitElement, nothing, TemplateResult } from 'lit';
import { classMap } from 'lit/directives/class-map';
import { StyleInfo, styleMap } from 'lit/directives/style-map';
import { ref, Ref, createRef } from 'lit/directives/ref';
import { CodeMirrorElement } from '@typo3/backend/code-editor/element/code-mirror-element';

type Cell = { spanned: number, rowspan: number, colspan: number, name: string, colpos: string, column: number, identifier: string, slideMode: SlideModes }

enum SlideModes {
  none = '',
  slide = 'slide',
  collect = 'collect',
  collectReverse = 'collectReverse',
}

/**
 * Module: @typo3/backend/grid-editor
 * @exports @typo3/backend/grid-editor
 */
@customElement('typo3-backend-grid-editor')
export class GridEditor extends LitElement {
  @property({ type: Number }) protected colCount: number = 1;
  @property({ type: Number }) protected rowCount: number = 1;
  @property({ type: Boolean }) protected readOnly: boolean = false;
  @property({ type: String }) protected fieldName: string = '';
  @property({ type: Array }) protected data: any[] = [];
  @property({ type: Object }) protected codeMirrorConfig: Record<string, string> = {};

  protected field: HTMLInputElement;
  protected previewAreaRef: Ref<HTMLTextAreaElement> = createRef();
  protected codeMirrorRef: Ref<CodeMirrorElement> = createRef();
  protected defaultCell: Cell = { spanned: 0, rowspan: 1, colspan: 1, name: '', colpos: '', column: undefined, identifier: '', slideMode: SlideModes.none };

  /**
   * Remove all markup
   *
   * @param {String} input
   * @returns {string}
   */
  public static stripMarkup(input: string): string {
    const securityUtility = new SecurityUtility();
    return securityUtility.stripHtml(input);
  }

  public override connectedCallback(): void {
    this.field = document.querySelector('input[name="' + this.fieldName + '"]');

    this.addVisibilityObserver(this);
    super.connectedCallback();
  }

  protected override firstUpdated(): void {
    this.writeConfig(this.export2LayoutRecord());
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    // @todo Switch to Shadow DOM once Bootstrap CSS style can be applied correctly
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <div class=${classMap({ 'grideditor': true, 'grideditor-readonly': this.readOnly })}>
        ${!this.readOnly ? this.renderControls('top', false) : nothing}
        <div class="grideditor-editor">
          <div class="t3js-grideditor">
            ${this.renderEditorGrid()}
          </div>
        </div>
        ${!this.readOnly ? this.renderControls('right', true) : nothing}
        ${!this.readOnly ? this.renderControls('bottom', false) : nothing}
        <div class="grideditor-preview">
          ${this.renderPreview()}
        </div>
      </div>
    `;
  }

  protected renderControls(position: string, vertical: boolean): TemplateResult {
    const addHandlerMapping: Record<string, (e: Event) => void> = {
      top: this.addRowTopHandler,
      right: this.addColumnHandler,
      bottom: this.addRowBottomHandler
    }

    const addLocaleMapping: Record<string, string> = {
      top: TYPO3.lang.grid_addRow,
      right: TYPO3.lang.grid_addColumn,
      bottom: TYPO3.lang.grid_addRow
    }

    const removeHandlerMapping: Record<string, (e: Event) => void> = {
      top: this.removeRowTopHandler,
      right: this.removeColumnHandler,
      bottom: this.removeRowBottomHandler
    }

    const removeLocaleMapping: Record<string, string> = {
      top: TYPO3.lang.grid_removeRow,
      right: TYPO3.lang.grid_removeColumn,
      bottom: TYPO3.lang.grid_removeRow
    }

    return html`
      <div class="grideditor-control grideditor-control-${position}">
        <div class=${classMap({ 'btn-group': !vertical, 'btn-group-vertical': vertical })}>
          <button @click=${addHandlerMapping[position]} class="btn btn-default btn-sm" title=${addLocaleMapping[position]}>
            <typo3-backend-icon identifier="actions-plus" size="small"></typo3-backend-icon>
          </button>
          <button @click=${removeHandlerMapping[position]} class="btn btn-default btn-sm" title=${removeLocaleMapping[position]}>
            <typo3-backend-icon identifier="actions-minus" size="small"></typo3-backend-icon>
          </button>
        </div>
      </div>
    `;
  }

  protected renderEditorGrid(): TemplateResult {
    const cells: TemplateResult[] = [];

    for (let row = 0; row < this.rowCount; row++) {
      const rowData = this.data[row];
      if (rowData.length === 0) {
        continue;
      }

      for (let col = 0; col < this.colCount; col++) {
        const cell = this.data[row][col];
        if (cell.spanned === 1) {
          continue;
        }

        cells.push(this.renderGridCell(row, col, cell));
      }
    }

    return html`
      <div class="grideditor-editor-grid">
        ${cells}
      </div>
    `;
  }

  protected renderGridCell(row: number, col: number, cell: Cell): TemplateResult {
    const styleMapping: StyleInfo = {
      '--grideditor-cell-col': col + 1,
      '--grideditor-cell-colspan': cell.colspan,
      '--grideditor-cell-row': row + 1,
      '--grideditor-cell-rowspan': cell.rowspan
    }

    return html`
      <div class="grideditor-cell" style=${styleMap(styleMapping)}>
        <div class="grideditor-cell-actions">
        ${!this.readOnly ?
    html`
            <button
              @click=${this.linkEditorHandler}
              class="t3js-grideditor-link-editor grideditor-action grideditor-action-edit"
              data-row=${row}
              data-col=${col}
              title=${TYPO3.lang.grid_editCell}>
              <typo3-backend-icon identifier="actions-open" size="small"></typo3-backend-icon>
            </button>
            ${this.cellCanSpanRight(col, row) ?
    html`
                <button
                  @click=${this.linkExpandRightHandler}
                  class="t3js-grideditor-link-expand-right grideditor-action grideditor-action-expand-right"
                  data-row=${row}
                  data-col=${col}
                  title=${TYPO3.lang.grid_editCell}>
                  <typo3-backend-icon identifier="actions-caret-right" size="small"></typo3-backend-icon>
                </button>
              `
    : nothing}
            ${this.cellCanShrinkLeft(col, row) ?
    html`
                <button
                  @click=${this.linkShrinkLeftHandler}
                  class="t3js-grideditor-link-shrink-left grideditor-action grideditor-action-shrink-left"
                  data-row=${row}
                  data-col=${col}
                  title=${TYPO3.lang.grid_editCell}>
                  <typo3-backend-icon identifier="actions-caret-left" size="small"></typo3-backend-icon>
                </button>
              `
    : nothing}
            ${this.cellCanSpanDown(col, row) ?
    html`
                <button
                  @click=${this.linkExpandDownHandler}
                  class="t3js-grideditor-link-expand-down grideditor-action grideditor-action-expand-down"
                  data-row=${row}
                  data-col=${col}
                  title=${TYPO3.lang.grid_editCell}>
                  <typo3-backend-icon identifier="actions-caret-down" size="small"></typo3-backend-icon>
                </button>
              `
    : nothing}
            ${this.cellCanShrinkUp(col, row) ?
    html`
                <button
                  @click=${this.linkShrinkUpHandler}
                  class="t3js-grideditor-link-shrink-up grideditor-action grideditor-action-shrink-up"
                  data-row=${row}
                  data-col=${col}
                  title=${TYPO3.lang.grid_editCell}>
                  <typo3-backend-icon identifier="actions-caret-up" size="small"></typo3-backend-icon>
                </button>
              `
    : nothing}
          `
    : nothing}
        </div>

        <div class="grideditor-cell-info">
          <strong>${TYPO3.lang.grid_name}:</strong>
          ${cell.name ? GridEditor.stripMarkup(cell.name) : TYPO3.lang.grid_notSet}
          <br/>
          <strong>${TYPO3.lang.grid_column}:</strong>
          ${typeof cell.column === 'undefined' || isNaN(cell.column) ? TYPO3.lang.grid_notSet : cell.column}
          ${cell.identifier?.length ? html`<br/><strong>${TYPO3.lang.grid_identifier}:</strong> ${cell.identifier}` : ''}
          ${(cell.slideMode?.toString() || '') !== '' ? html`<br/><strong>${TYPO3.lang.grid_slideMode}:</strong> ${cell.slideMode.toString()}` : ''}
        </div>
      </div>
    `;
  }

  protected renderPreview(): TemplateResult {
    if (Object.keys(this.codeMirrorConfig).length === 0) {
      return html`
        <label>${TYPO3.lang['buttons.pageTsConfig']}</label>
        <div class="t3js-grideditor-preview-config grideditor-preview">
            <textarea class="t3js-tsconfig-preview-area form-control" rows="25" readonly ${ref(this.previewAreaRef)}></textarea>
        </div>
      `;
    }

    return html`
      <typo3-t3editor-codemirror
        class="t3js-grideditor-preview-config grideditor-preview"
        label=${this.codeMirrorConfig.label}
        panel=${this.codeMirrorConfig.panel}
        mode=${this.codeMirrorConfig.mode}
        nolazyload=true
        readonly=true
        ${ref(this.codeMirrorRef)}>
        <textarea class="t3js-tsconfig-preview-area form-control" ${ref(this.previewAreaRef)}></textarea>
      </typo3-t3editor-codemirror>
    `;
  }

  /**
   *
   * @param {Event} e
   */
  protected modalButtonClickHandler = (e: Event) => {
    const button = e.target as HTMLButtonElement;
    const modal: ModalElement = e.currentTarget as ModalElement;
    if (button.name === 'cancel') {
      modal.hideModal();
    } else if (button.name === 'ok') {
      this.setName(
        (modal.querySelector('.t3js-grideditor-field-name') as HTMLInputElement).value,
        modal.userData.col,
        modal.userData.row,
      );
      this.setColumn(
        parseInt((modal.querySelector('.t3js-grideditor-field-colpos') as HTMLInputElement).value, 10),
        modal.userData.col,
        modal.userData.row,
      );
      this.setIdentifier(
        (modal.querySelector('.t3js-grideditor-field-identifier') as HTMLInputElement).value,
        modal.userData.col,
        modal.userData.row,
      );
      this.setSlideMode(
        (modal.querySelector('.t3js-grideditor-field-slide-mode') as HTMLSelectElement).value,
        modal.userData.col,
        modal.userData.row,
      );
      this.requestUpdate();
      this.writeConfig(this.export2LayoutRecord());
      modal.hideModal();
    }
  };

  /**
   *
   * @param {Event} e
   */
  protected addColumnHandler = (e: Event) => {
    e.preventDefault();
    this.addColumn();
    this.requestUpdate();
    this.writeConfig(this.export2LayoutRecord());
  };

  /**
   *
   * @param {Event} e
   */
  protected removeColumnHandler = (e: Event) => {
    e.preventDefault();
    this.removeColumn();
    this.requestUpdate();
    this.writeConfig(this.export2LayoutRecord());
  };

  /**
   *
   * @param {Event} e
   */
  protected addRowTopHandler = (e: Event) => {
    e.preventDefault();
    this.addRowTop();
    this.requestUpdate();
    this.writeConfig(this.export2LayoutRecord());
  };

  /**
   *
   * @param {Event} e
   */
  protected addRowBottomHandler = (e: Event) => {
    e.preventDefault();
    this.addRowBottom();
    this.requestUpdate();
    this.writeConfig(this.export2LayoutRecord());
  };

  /**
   *
   * @param {Event} e
   */
  protected removeRowTopHandler = (e: Event) => {
    e.preventDefault();
    this.removeRowTop();
    this.requestUpdate();
    this.writeConfig(this.export2LayoutRecord());
  };

  /**
   *
   * @param {Event} e
   */
  protected removeRowBottomHandler = (e: Event) => {
    e.preventDefault();
    this.removeRowBottom();
    this.requestUpdate();
    this.writeConfig(this.export2LayoutRecord());
  };

  /**
   *
   * @param {Event} e
   */
  protected linkEditorHandler = (e: Event) => {
    e.preventDefault();
    const element = e.currentTarget as HTMLElement;
    this.showOptions(Number(element.dataset.col), Number(element.dataset.row));
  };

  /**
   *
   * @param {Event} e
   */
  protected linkExpandRightHandler = (e: Event) => {
    e.preventDefault();
    const element = e.currentTarget as HTMLElement;
    this.addColspan(Number(element.dataset.col), Number(element.dataset.row));
    this.requestUpdate();
    this.writeConfig(this.export2LayoutRecord());
  };

  /**
   *
   * @param {Event} e
   */
  protected linkShrinkLeftHandler = (e: Event) => {
    e.preventDefault();
    const element = e.currentTarget as HTMLElement;
    this.removeColspan(Number(element.dataset.col), Number(element.dataset.row));
    this.requestUpdate();
    this.writeConfig(this.export2LayoutRecord());
  };

  /**
   *
   * @param {Event} e
   */
  protected linkExpandDownHandler = (e: Event) => {
    e.preventDefault();
    const element = e.currentTarget as HTMLElement;
    this.addRowspan(Number(element.dataset.col), Number(element.dataset.row));
    this.requestUpdate();
    this.writeConfig(this.export2LayoutRecord());
  };

  /**
   *
   * @param {Event} e
   */
  protected linkShrinkUpHandler = (e: Event) => {
    e.preventDefault();
    const element = e.currentTarget as HTMLElement;
    this.removeRowspan(Number(element.dataset.col), Number(element.dataset.row));
    this.requestUpdate();
    this.writeConfig(this.export2LayoutRecord());
  };

  /**
   * Create a new cell from defaultCell
   * @returns {Object}
   */
  protected getNewCell(): Cell {
    return structuredClone(this.defaultCell);
  }

  /**
   * write data back to hidden field
   *
   * @param data
   */
  protected writeConfig(data: string): void {
    this.field.value = data;
    const configLines = data.split('\n');
    let config = '';
    for (const line of configLines) {
      if (line) {
        config += '\t\t\t' + line + '\n';
      }
    }

    const content = 'mod.web_layout.BackendLayouts {\n' +
      '  exampleKey {\n' +
      '    title = Example\n' +
      '    icon = content-container-columns-2\n' +
      '    config {\n' +
      config.replace(new RegExp('\\t', 'g'), '  ') +
      '    }\n' +
      '  }\n' +
      '}\n';

    const previewArea: HTMLTextAreaElement | undefined = this.previewAreaRef.value;
    // Update previewArea value if instantiated
    if (previewArea instanceof HTMLTextAreaElement) {
      previewArea.value = content;
    }

    // Update CodeMirror content if instantiated
    const codemirror: CodeMirrorElement | undefined = this.codeMirrorRef.value;
    if (codemirror instanceof CodeMirrorElement) {
      codemirror.setContent(content);
    }
  }

  /**
   * Add a new row at the top
   */
  protected addRowTop(): void {
    const newRow = [];
    for (let i = 0; i < this.colCount; i++) {
      const newCell = this.getNewCell();
      newCell.name = i + 'x' + this.data.length;
      newRow[i] = newCell;
    }
    this.data.unshift(newRow);
    this.rowCount++;
  }

  /**
   * Add a new row at the bottom
   */
  protected addRowBottom(): void {
    const newRow = [];
    for (let i = 0; i < this.colCount; i++) {
      const newCell = this.getNewCell();
      newCell.name = i + 'x' + this.data.length;
      newRow[i] = newCell;
    }
    this.data.push(newRow);
    this.rowCount++;
  }

  /**
   * Removes the first row of the grid and adjusts all cells that might be effected
   * by that change. (Removing colspans)
   */
  protected removeRowTop(): boolean {
    if (this.rowCount <= 1) {
      return false;
    }
    const newData = [];
    for (let rowIndex = 1; rowIndex < this.rowCount; rowIndex++) {
      newData.push(this.data[rowIndex]);
    }

    // fix rowspan in former last row
    for (let colIndex = 0; colIndex < this.colCount; colIndex++) {
      if (this.data[0][colIndex].spanned === 1) {
        this.findUpperCellWidthRowspanAndDecreaseByOne(colIndex, 0);
      }
    }

    this.data = newData;
    this.rowCount--;
    return true;
  }

  /**
   * Removes the last row of the grid and adjusts all cells that might be effected
   * by that change. (Removing colspans)
   */
  protected removeRowBottom(): boolean {
    if (this.rowCount <= 1) {
      return false;
    }
    const newData = [];
    for (let rowIndex = 0; rowIndex < this.rowCount - 1; rowIndex++) {
      newData.push(this.data[rowIndex]);
    }

    // fix rowspan in former last row
    for (let colIndex = 0; colIndex < this.colCount; colIndex++) {
      if (this.data[this.rowCount - 1][colIndex].spanned === 1) {
        this.findUpperCellWidthRowspanAndDecreaseByOne(colIndex, this.rowCount - 1);
      }
    }

    this.data = newData;
    this.rowCount--;
    return true;
  }

  /**
   * Takes a cell and looks above it if there are any cells that have colspans that
   * spans into the given cell. This is used when a row was removed from the grid
   * to make sure that no cell with wrong colspans exists in the grid.
   *
   * @param {number} col
   * @param {number} row integer
   */
  protected findUpperCellWidthRowspanAndDecreaseByOne(col: number, row: number): boolean {
    const upperCell = this.getCell(col, row - 1);
    if (!upperCell) {
      return false;
    }

    if (upperCell.spanned === 1) {
      this.findUpperCellWidthRowspanAndDecreaseByOne(col, row - 1);
    } else {
      if (upperCell.rowspan > 1) {
        this.removeRowspan(col, row - 1);
      }
    }
    return true;
  }

  /**
   * Removes the outermost right column from the grid.
   */
  protected removeColumn(): boolean {
    if (this.colCount <= 1) {
      return false;
    }
    const newData = [];

    for (let rowIndex = 0; rowIndex < this.rowCount; rowIndex++) {
      const newRow = [];
      for (let colIndex = 0; colIndex < this.colCount - 1; colIndex++) {
        newRow.push(this.data[rowIndex][colIndex]);
      }
      if (this.data[rowIndex][this.colCount - 1].spanned === 1) {
        this.findLeftCellWidthColspanAndDecreaseByOne(this.colCount - 1, rowIndex);
      }
      newData.push(newRow);
    }

    this.data = newData;
    this.colCount--;
    return true;
  }

  /**
   * Checks if there are any cells on the left side of a given cell with a
   * rowspan that spans over the given cell.
   *
   * @param {number} col
   * @param {number} row
   */
  protected findLeftCellWidthColspanAndDecreaseByOne(col: number, row: number): boolean {
    const leftCell = this.getCell(col - 1, row);
    if (!leftCell) {
      return false;
    }

    if (leftCell.spanned === 1) {
      this.findLeftCellWidthColspanAndDecreaseByOne(col - 1, row);
    } else {
      if (leftCell.colspan > 1) {
        this.removeColspan(col - 1, row);
      }
    }
    return true;
  }

  /**
   * Adds a column at the right side of the grid.
   */
  protected addColumn(): void {
    for (let rowIndex = 0; rowIndex < this.rowCount; rowIndex++) {
      const newCell = this.getNewCell();
      newCell.name = this.colCount + 'x' + rowIndex;
      this.data[rowIndex].push(newCell);
    }
    this.colCount++;
  }

  /**
   * Sets the name of a certain grid element.
   *
   * @param {String} newName
   * @param {number} col
   * @param {number} row
   *
   * @returns {Boolean}
   */
  protected setName(newName: string, col: number, row: number): boolean {
    const cell = this.getCell(col, row);
    if (!cell) {
      return false;
    }
    cell.name = GridEditor.stripMarkup(newName);
    return true;
  }

  /**
   * Sets the column field for a certain grid element. This is NOT the column of the
   * element itself.
   *
   * @param {number} newColumn
   * @param {number} col
   * @param {number} row
   *
   * @returns {Boolean}
   */
  protected setColumn(newColumn: number, col: number, row: number): boolean {
    const cell = this.getCell(col, row);
    if (!cell) {
      return false;
    }
    cell.column = parseInt(newColumn.toString(), 10);
    return true;
  }

  protected setIdentifier(newIdentifier: string, col: number, row: number): boolean {
    const cell = this.getCell(col, row);
    if (!cell) {
      return false;
    }
    cell.identifier = GridEditor.stripMarkup(newIdentifier);
    return true;
  }

  protected setSlideMode(newSlideMode: string, col: number, row: number): boolean {
    const cell = this.getCell(col, row);
    if (!cell) {
      return false;
    }
    cell.slideMode = SlideModes[newSlideMode as keyof typeof SlideModes];
    return true;
  }

  /**
   * Creates an Modal with two input fields and shows it. On save, the data
   * is written into the grid element.
   *
   * @param {number} col
   * @param {number} row
   *
   * @returns {Boolean}
   */
  protected showOptions(col: number, row: number): boolean {
    const cell = this.getCell(col, row);
    if (!cell) {
      return false;
    }
    let colPos;
    if (cell.column === 0) {
      colPos = 0;
    } else if (cell.column) {
      colPos = parseInt(cell.column.toString(), 10);
    } else {
      colPos = '';
    }

    const markup = document.createElement('div');
    const formGroup = document.createElement('div');
    formGroup.classList.add('form-group');
    const label = document.createElement('label');
    const input = document.createElement('input');

    const nameFormGroup = formGroup.cloneNode(true) as HTMLElement;
    const nameLabel = label.cloneNode(true) as HTMLElement;
    nameLabel.innerText = TYPO3.lang.grid_nameHelp;
    const nameInput = input.cloneNode(true) as HTMLInputElement;
    nameInput.type = 'text';
    nameInput.classList.add('t3js-grideditor-field-name', 'form-control');
    nameInput.name = 'name';
    nameInput.value = GridEditor.stripMarkup(cell.name) || '';

    nameFormGroup.append(nameLabel, nameInput);

    const columnFormGroup = formGroup.cloneNode(true) as HTMLElement;
    const columnLabel = label.cloneNode(true) as HTMLElement;
    columnLabel.innerText = TYPO3.lang.grid_columnHelp;
    const columnInput = input.cloneNode(true) as HTMLInputElement;
    columnInput.type = 'text';
    columnInput.classList.add('t3js-grideditor-field-colpos', 'form-control');
    columnInput.name = 'column';
    columnInput.value = colPos.toString();

    columnFormGroup.append(columnLabel, columnInput);

    const identifierFormGroup = formGroup.cloneNode(true) as HTMLElement;
    const identifierLabel = label.cloneNode(true) as HTMLElement;
    identifierLabel.innerText = TYPO3.lang.grid_identifierHelp;
    const identifierInput = input.cloneNode(true) as HTMLInputElement;
    nameInput.type = 'text';
    identifierInput.classList.add('t3js-grideditor-field-identifier', 'form-control');
    identifierInput.name = 'identifier';
    identifierInput.value = typeof(cell.identifier) === 'string' ? GridEditor.stripMarkup(cell.identifier) : '';

    identifierFormGroup.append(identifierLabel, identifierInput);

    const slideModeFormGroup = formGroup.cloneNode(true) as HTMLElement;
    const slideModeLabel = label.cloneNode(true) as HTMLElement;
    slideModeLabel.innerText = TYPO3.lang.grid_slideModeHelp;
    const slideModeSelect = document.createElement('select');
    slideModeSelect.classList.add('t3js-grideditor-field-slide-mode', 'form-select', 'form-control-adapt');
    slideModeSelect.name = 'slideMode';
    slideModeSelect.value = GridEditor.stripMarkup(cell.slideMode?.toString()) || '';

    (Object.keys(SlideModes) as Array<keyof typeof SlideModes>).map((key) => {
      const text = key !== 'none' ? key : '';
      const value = SlideModes[key as keyof typeof SlideModes];
      const option = document.createElement('option');
      option.value = value;
      option.text = text;
      option.selected = value === cell.slideMode?.toString();
      slideModeSelect.appendChild(option);
    });

    slideModeFormGroup.append(slideModeLabel, slideModeSelect);

    markup.append(nameFormGroup, columnFormGroup, identifierFormGroup, slideModeFormGroup);

    const modal = Modal.show(TYPO3.lang.grid_windowTitle, markup, SeverityEnum.notice, [
      {
        active: true,
        btnClass: 'btn-default',
        name: 'cancel',
        text: TYPO3.lang['button.cancel'] || 'Cancel',
      },
      {
        btnClass: 'btn-primary',
        name: 'ok',
        text: TYPO3.lang['button.ok'] || 'OK',
      },
    ]);
    modal.userData.col = col;
    modal.userData.row = row;
    modal.addEventListener('button.clicked', this.modalButtonClickHandler);
    return true;
  }

  /**
   * Returns a cell element from the grid.
   *
   * @param {number} col
   * @param {number} row
   */
  protected getCell(col: number, row: number): Cell|false|null {
    if (col > this.colCount - 1) {
      return false;
    }
    if (row > this.rowCount - 1) {
      return false;
    }
    if (this.data.length > row - 1 && this.data[row].length > col - 1) {
      return this.data[row][col];
    }
    return null;
  }

  /**
   * Checks whether a cell can span to the right or not. A cell can span to the right
   * if it is not in the last column and if there is no cell beside it that is
   * already overspanned by some other cell.
   *
   * @param {number} col
   * @param {number} row
   * @returns {Boolean}
   */
  protected cellCanSpanRight(col: number, row: number): boolean {
    if (col === this.colCount - 1) {
      return false;
    }

    const cell = this.getCell(col, row);
    if (!cell) {
      return false;
    }

    let checkCell;
    if (cell.rowspan > 1) {
      for (let rowIndex = row; rowIndex < row + cell.rowspan; rowIndex++) {
        checkCell = this.getCell(col + cell.colspan, rowIndex);
        if (!checkCell || checkCell.spanned === 1 || checkCell.colspan > 1 || checkCell.rowspan > 1) {
          return false;
        }
      }
    } else {
      checkCell = this.getCell(col + cell.colspan, row);
      if (!checkCell || cell.spanned === 1 || checkCell.spanned === 1 || checkCell.colspan > 1
        || checkCell.rowspan > 1) {
        return false;
      }
    }

    return true;
  }

  /**
   * Checks whether a cell can span down or not.
   *
   * @param {number} col
   * @param {number} row
   * @returns {Boolean}
   */
  protected cellCanSpanDown(col: number, row: number): boolean {
    if (row === this.rowCount - 1) {
      return false;
    }

    const cell = this.getCell(col, row);
    if (!cell) {
      return false;
    }

    let checkCell;
    if (cell.colspan > 1) {
      // we have to check all cells on the right side for the complete colspan
      for (let colIndex = col; colIndex < col + cell.colspan; colIndex++) {
        checkCell = this.getCell(colIndex, row + cell.rowspan);
        if (!checkCell || checkCell.spanned === 1 || checkCell.colspan > 1 || checkCell.rowspan > 1) {
          return false;
        }
      }
    } else {
      checkCell = this.getCell(col, row + cell.rowspan);
      if (!checkCell || cell.spanned === 1 || checkCell.spanned === 1 || checkCell.colspan > 1
        || checkCell.rowspan > 1) {
        return false;
      }
    }

    return true;
  }

  /**
   * Checks if a cell can shrink to the left. It can shrink if the colspan of the
   * cell is bigger than 1.
   *
   * @param {number} col
   * @param {number} row
   * @returns {Boolean}
   */
  protected cellCanShrinkLeft(col: number, row: number): boolean {
    return (this.data[row][col].colspan > 1);
  }

  /**
   * Returns if a cell can shrink up. This is the case if a cell has at least
   * a rowspan of 2.
   *
   * @param {number} col
   * @param {number} row
   * @returns {Boolean}
   */
  protected cellCanShrinkUp(col: number, row: number): boolean {
    return (this.data[row][col].rowspan > 1);
  }

  /**
   * Adds a colspan to a grid element.
   *
   * @param {number} col
   * @param {number} row
   * @returns {Boolean}
   */
  protected addColspan(col: number, row: number): boolean {
    const cell = this.getCell(col, row);
    if (!cell || !this.cellCanSpanRight(col, row)) {
      return false;
    }

    for (let rowIndex = row; rowIndex < row + cell.rowspan; rowIndex++) {
      this.data[rowIndex][col + cell.colspan].spanned = 1;
    }
    cell.colspan += 1;
    return true;
  }

  /**
   * Adds a rowspan to grid element.
   *
   * @param {number} col
   * @param {number} row
   * @returns {Boolean}
   */
  protected addRowspan(col: number, row: number): boolean {
    const cell = this.getCell(col, row);
    if (!cell || !this.cellCanSpanDown(col, row)) {
      return false;
    }

    for (let colIndex = col; colIndex < col + cell.colspan; colIndex++) {
      this.data[row + cell.rowspan][colIndex].spanned = 1;
    }
    cell.rowspan += 1;
    return true;
  }

  /**
   * Removes a colspan from a grid element.
   *
   * @param {number} col
   * @param {number} row
   * @returns {Boolean}
   */
  protected removeColspan(col: number, row: number): boolean {
    const cell = this.getCell(col, row);
    if (!cell || !this.cellCanShrinkLeft(col, row)) {
      return false;
    }

    cell.colspan -= 1;

    for (let rowIndex = row; rowIndex < row + cell.rowspan; rowIndex++) {
      this.data[rowIndex][col + cell.colspan].spanned = 0;
    }
    return true;
  }

  /**
   * Removes a rowspan from a grid element.
   *
   * @param {number} col
   * @param {number} row
   * @returns {Boolean}
   */
  protected removeRowspan(col: number, row: number): boolean {
    const cell = this.getCell(col, row);
    if (!cell || !this.cellCanShrinkUp(col, row)) {
      return false;
    }

    cell.rowspan -= 1;
    for (let colIndex = col; colIndex < col + cell.colspan; colIndex++) {
      this.data[row + cell.rowspan][colIndex].spanned = 0;
    }
    return true;
  }

  /**
   * Exports the current grid to a TypoScript notation that can be read by the
   * page module and is human readable.
   *
   * @returns {String}
   */
  protected export2LayoutRecord(): string {
    let result = 'backend_layout {\n\tcolCount = ' + this.colCount + '\n\trowCount = ' + this.rowCount + '\n\trows {\n';
    for (let row = 0; row < this.rowCount; row++) {
      result += '\t\t' + (row + 1) + ' {\n';
      result += '\t\t\tcolumns {\n';
      let colIndex = 0;
      for (let col = 0; col < this.colCount; col++) {
        const cell = this.getCell(col, row);
        if (cell) {
          if (!cell.spanned) {
            const cellName: string = GridEditor.stripMarkup(cell.name) || '';
            colIndex++;
            result += '\t\t\t\t' + (colIndex) + ' {\n';
            result += '\t\t\t\t\tname = ' + ((!cellName) ? col + 'x' + row : cellName) + '\n';
            if (cell.colspan > 1) {
              result += '\t\t\t\t\tcolspan = ' + cell.colspan + '\n';
            }
            if (cell.rowspan > 1) {
              result += '\t\t\t\t\trowspan = ' + cell.rowspan + '\n';
            }
            if (typeof(cell.column) === 'number') {
              result += '\t\t\t\t\tcolPos = ' + cell.column + '\n';
            }
            if (typeof(cell.identifier) === 'string' && cell.identifier.length) {
              result += '\t\t\t\t\tidentifier = ' + cell.identifier + '\n';
            }
            if (cell.slideMode !== undefined && cell.slideMode !== SlideModes.none) {
              result += '\t\t\t\t\tslideMode = ' + cell.slideMode.toString() + '\n';
            }
            result += '\t\t\t\t}\n';
          }
        }

      }
      result += '\t\t\t}\n';
      result += '\t\t}\n';
    }

    result += '\t}\n}\n';
    return result;
  }

  /**
   * Observe the editors' visibility, since codeMirror needs to be refreshed as soon as it becomes
   * visible in the viewport. Otherwise, if this element is not in the first visible FormEngine tab,
   * it will not display any value, unless the grid gets manually updated.
   */
  protected addVisibilityObserver(gridEditor: HTMLElement) {
    if (gridEditor.offsetParent !== null) {
      // In case the editor is already visible, we don't have to add the observer
      return;
    }
    new IntersectionObserver((entries: IntersectionObserverEntry[]) => {
      entries.forEach(entry => {
        const codemirror: CodeMirrorElement | undefined = this.codeMirrorRef.value;
        // Update CodeMirror if instantiated
        if (entry.intersectionRatio > 0 && codemirror instanceof CodeMirrorElement) {
          codemirror.requestUpdate();
        }
      });
    }).observe(gridEditor);
  }
}
