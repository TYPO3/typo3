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

import {SeverityEnum} from './enum/severity';
import 'bootstrap';
import $ from 'jquery';
import Modal from './modal';
import SecurityUtility from '@typo3/core/security-utility';

/**
 * GridEditorConfigurationInterface
 */
interface GridEditorConfigurationInterface {
  nameLabel: string;
  columnLabel: string;
}

/**
 * CellInterface
 */
interface CellInterface {
  spanned: number;
  rowspan: number;
  colspan: number;
  column: number;
  name: string;
  colpos: string;
}

/**
 * Module: @typo3/backend/grid-editor
 * @exports @typo3/backend/grid-editor
 */
export class GridEditor {

  protected colCount: number = 1;
  protected rowCount: number = 1;
  protected readOnly: boolean = false;
  protected field: JQuery;
  protected data: any[];
  protected nameLabel: string = 'name';
  protected columnLabel: string = 'column label';
  protected targetElement: JQuery;
  protected defaultCell: object = {spanned: 0, rowspan: 1, colspan: 1, name: '', colpos: '', column: undefined};
  protected selectorEditor: string = '.t3js-grideditor';
  protected selectorAddColumn: string = '.t3js-grideditor-addcolumn';
  protected selectorRemoveColumn: string = '.t3js-grideditor-removecolumn';
  protected selectorAddRowTop: string = '.t3js-grideditor-addrow-top';
  protected selectorRemoveRowTop: string = '.t3js-grideditor-removerow-top';
  protected selectorAddRowBottom: string = '.t3js-grideditor-addrow-bottom';
  protected selectorRemoveRowBottom: string = '.t3js-grideditor-removerow-bottom';
  protected selectorLinkEditor: string = '.t3js-grideditor-link-editor';
  protected selectorLinkExpandRight: string = '.t3js-grideditor-link-expand-right';
  protected selectorLinkShrinkLeft: string = '.t3js-grideditor-link-shrink-left';
  protected selectorLinkExpandDown: string = '.t3js-grideditor-link-expand-down';
  protected selectorLinkShrinkUp: string = '.t3js-grideditor-link-shrink-up';
  protected selectorConfigPreview: string = '.t3js-grideditor-preview-config';
  protected selectorPreviewArea: string = '.t3js-tsconfig-preview-area';
  protected selectorCodeMirror: string = '.t3js-grideditor-preview-config .CodeMirror';

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

  /**
   *
   * @param {GridEditorConfigurationInterface} config
   */
  constructor(config: GridEditorConfigurationInterface = null) {
    const $element = $(this.selectorEditor);
    this.colCount = $element.data('colcount');
    this.rowCount = $element.data('rowcount');
    this.readOnly = $element.data('readonly');
    this.field = $('input[name="' + $element.data('field') + '"]');
    this.data = $element.data('data');
    this.nameLabel = config !== null ? config.nameLabel : 'Name';
    this.columnLabel = config !== null ? config.columnLabel : 'Column';
    this.targetElement = $(this.selectorEditor);

    this.initializeEvents();
    this.addVisibilityObserver($element.get(0));
    this.drawTable();
    this.writeConfig(this.export2LayoutRecord());
  }

  /**
   *
   */
  protected initializeEvents(): void {
    if (this.readOnly) {
      // Do not initialize events in case this is a readonly field
      return;
    }
    $(document).on('click', this.selectorAddColumn, this.addColumnHandler);
    $(document).on('click', this.selectorRemoveColumn, this.removeColumnHandler);
    $(document).on('click', this.selectorAddRowTop, this.addRowTopHandler);
    $(document).on('click', this.selectorAddRowBottom, this.addRowBottomHandler);
    $(document).on('click', this.selectorRemoveRowTop, this.removeRowTopHandler);
    $(document).on('click', this.selectorRemoveRowBottom, this.removeRowBottomHandler);
    $(document).on('click', this.selectorLinkEditor, this.linkEditorHandler);
    $(document).on('click', this.selectorLinkExpandRight, this.linkExpandRightHandler);
    $(document).on('click', this.selectorLinkShrinkLeft, this.linkShrinkLeftHandler);
    $(document).on('click', this.selectorLinkExpandDown, this.linkExpandDownHandler);
    $(document).on('click', this.selectorLinkShrinkUp, this.linkShrinkUpHandler);
  }

  /**
   *
   * @param {Event} e
   */
  protected modalButtonClickHandler = (e: Event) => {
    const button: any = e.target;
    if (button.name === 'cancel') {
      Modal.currentModal.trigger('modal-dismiss');
    } else if (button.name === 'ok') {
      this.setName(
        Modal.currentModal.find('.t3js-grideditor-field-name').val(),
        Modal.currentModal.data('col'),
        Modal.currentModal.data('row'),
      );
      this.setColumn(
        Modal.currentModal.find('.t3js-grideditor-field-colpos').val(),
        Modal.currentModal.data('col'),
        Modal.currentModal.data('row'),
      );
      this.drawTable();
      this.writeConfig(this.export2LayoutRecord());
      Modal.currentModal.trigger('modal-dismiss');
    }
  }

  /**
   *
   * @param {Event} e
   */
  protected addColumnHandler = (e: Event) => {
    e.preventDefault();
    this.addColumn();
    this.drawTable();
    this.writeConfig(this.export2LayoutRecord());
  }

  /**
   *
   * @param {Event} e
   */
  protected removeColumnHandler = (e: Event) => {
    e.preventDefault();
    this.removeColumn();
    this.drawTable();
    this.writeConfig(this.export2LayoutRecord());
  }

  /**
   *
   * @param {Event} e
   */
  protected addRowTopHandler = (e: Event) => {
    e.preventDefault();
    this.addRowTop();
    this.drawTable();
    this.writeConfig(this.export2LayoutRecord());
  }

  /**
   *
   * @param {Event} e
   */
  protected addRowBottomHandler = (e: Event) => {
    e.preventDefault();
    this.addRowBottom();
    this.drawTable();
    this.writeConfig(this.export2LayoutRecord());
  }

  /**
   *
   * @param {Event} e
   */
  protected removeRowTopHandler = (e: Event) => {
    e.preventDefault();
    this.removeRowTop();
    this.drawTable();
    this.writeConfig(this.export2LayoutRecord());
  }

  /**
   *
   * @param {Event} e
   */
  protected removeRowBottomHandler = (e: Event) => {
    e.preventDefault();
    this.removeRowBottom();
    this.drawTable();
    this.writeConfig(this.export2LayoutRecord());
  }

  /**
   *
   * @param {Event} e
   */
  protected linkEditorHandler = (e: Event) => {
    e.preventDefault();
    const $element = $(e.target);
    this.showOptions($element.data('col'), $element.data('row'));
  }

  /**
   *
   * @param {Event} e
   */
  protected linkExpandRightHandler = (e: Event) => {
    e.preventDefault();
    const $element = $(e.target);
    this.addColspan($element.data('col'), $element.data('row'));
    this.drawTable();
    this.writeConfig(this.export2LayoutRecord());
  }

  /**
   *
   * @param {Event} e
   */
  protected linkShrinkLeftHandler = (e: Event) => {
    e.preventDefault();
    const $element = $(e.target);
    this.removeColspan($element.data('col'), $element.data('row'));
    this.drawTable();
    this.writeConfig(this.export2LayoutRecord());
  }

  /**
   *
   * @param {Event} e
   */
  protected linkExpandDownHandler = (e: Event) => {
    e.preventDefault();
    const $element = $(e.target);
    this.addRowspan($element.data('col'), $element.data('row'));
    this.drawTable();
    this.writeConfig(this.export2LayoutRecord());
  }

  /**
   *
   * @param {Event} e
   */
  protected linkShrinkUpHandler = (e: Event) => {
    e.preventDefault();
    const $element = $(e.target);
    this.removeRowspan($element.data('col'), $element.data('row'));
    this.drawTable();
    this.writeConfig(this.export2LayoutRecord());
  }

  /**
   * Create a new cell from defaultCell
   * @returns {Object}
   */
  protected getNewCell(): any {
    return $.extend({}, this.defaultCell);
  }

  /**
   * write data back to hidden field
   *
   * @param data
   */
  protected writeConfig(data: any): void {
    this.field.val(data);
    const configLines = data.split('\n');
    let config = '';
    for (const line of configLines) {
      if (line) {
        config += '\t\t\t' + line + '\n';
      }
    }

    let content = 'mod.web_layout.BackendLayouts {\n' +
      '  exampleKey {\n' +
      '    title = Example\n' +
      '    icon = EXT:example_extension/Resources/Public/Images/BackendLayouts/default.gif\n' +
      '    config {\n' +
      config.replace(new RegExp('\t', 'g'), '  ') +
      '    }\n' +
      '  }\n' +
      '}\n';

    $(this.selectorConfigPreview).find(this.selectorPreviewArea).empty().append(
      content
    );

    // Update CodeMirror content if instantiated
    const codemirror: any = document.querySelector(this.selectorCodeMirror);
    if (codemirror) {
      codemirror.CodeMirror.setValue(content)
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
   * Draws the grid as table into a given container.
   * It also adds all needed links and bindings to the cells to make it editable.
   */
  protected drawTable(): void {
    const $colgroup = $('<colgroup>');
    for (let col = 0; col < this.colCount; col++) {
      const percent = 100 / this.colCount;
      $colgroup.append($('<col>').css({
        width: parseInt(percent.toString(), 10) + '%',
      }));
    }
    const $table = $('<table id="base" class="table editor">');
    $table.append($colgroup);

    for (let row = 0; row < this.rowCount; row++) {
      const rowData = this.data[row];
      if (rowData.length === 0) {
        continue;
      }

      const $row = $('<tr>');

      for (let col = 0; col < this.colCount; col++) {
        const cell = this.data[row][col];
        if (cell.spanned === 1) {
          continue;
        }
        const percentRow = 100 / this.rowCount;
        const percentCol = 100 / this.colCount;
        const $cell = $('<td>').css({
          height: parseInt(percentRow.toString(), 10) * cell.rowspan + '%',
          width: parseInt(percentCol.toString(), 10) * cell.colspan + '%',
        });

        if (!this.readOnly) {
          // Add cell container and actions in case this isn't a readonly field
          const $container = $('<div class="cell_container">');
          $cell.append($container);
          const $anchor = $('<a href="#" data-col="' + col + '" data-row="' + row + '">');

          $container.append(
            $anchor
              .clone()
              .attr('class', 't3js-grideditor-link-editor link link_editor')
              .attr('title', TYPO3.lang.grid_editCell),
          );
          if (this.cellCanSpanRight(col, row)) {
            $container.append(
              $anchor
                .clone()
                .attr('class', 't3js-grideditor-link-expand-right link link_expand_right')
                .attr('title', TYPO3.lang.grid_mergeCell),
            );
          }
          if (this.cellCanShrinkLeft(col, row)) {
            $container.append(
              $anchor
                .clone()
                .attr('class', 't3js-grideditor-link-shrink-left link link_shrink_left')
                .attr('title', TYPO3.lang.grid_splitCell),
            );
          }
          if (this.cellCanSpanDown(col, row)) {
            $container.append(
              $anchor
                .clone()
                .attr('class', 't3js-grideditor-link-expand-down link link_expand_down')
                .attr('title', TYPO3.lang.grid_mergeCell),
            );
          }
          if (this.cellCanShrinkUp(col, row)) {
            $container.append(
              $anchor
                .clone()
                .attr('class', 't3js-grideditor-link-shrink-up link link_shrink_up')
                .attr('title', TYPO3.lang.grid_splitCell),
            );
          }
        }

        $cell.append(
          $('<div class="cell_data">')
            .html(
              TYPO3.lang.grid_name + ': '
              + (cell.name ? GridEditor.stripMarkup(cell.name) : TYPO3.lang.grid_notSet)
              + '<br />'
              + TYPO3.lang.grid_column + ': '
              + (typeof cell.column === 'undefined' || isNaN(cell.column)
                ? TYPO3.lang.grid_notSet
                : parseInt(cell.column, 10)
              ),
            ),
        );
        if (cell.colspan > 1) {
          $cell.attr('colspan', cell.colspan);
        }
        if (cell.rowspan > 1) {
          $cell.attr('rowspan', cell.rowspan);
        }
        $row.append($cell);
      }
      $table.append($row);
    }
    $(this.targetElement).empty().append($table);
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

    const $markup = $('<div>');
    const $formGroup = $('<div class="form-group">');
    const $label = $('<label>');
    const $input = $('<input>');

    $markup.append([
      $formGroup
        .clone()
        .append([
          $label
            .clone()
            .text(TYPO3.lang.grid_nameHelp)
          ,
          $input
            .clone()
            .attr('type', 'text')
            .attr('class', 't3js-grideditor-field-name form-control')
            .attr('name', 'name')
            .val(GridEditor.stripMarkup(cell.name) || ''),
        ]),
      $formGroup
        .clone()
        .append([
          $label
            .clone()
            .text(TYPO3.lang.grid_columnHelp)
          ,
          $input
            .clone()
            .attr('type', 'text')
            .attr('class', 't3js-grideditor-field-colpos form-control')
            .attr('name', 'column')
            .val(colPos),
        ]),
    ]);

    const $modal = Modal.show(TYPO3.lang.grid_windowTitle, $markup, SeverityEnum.notice, [
      {
        active: true,
        btnClass: 'btn-default',
        name: 'cancel',
        text: $(this).data('button-close-text') || TYPO3.lang['button.cancel'] || 'Cancel',
      },
      {
        btnClass: 'btn-primary',
        name: 'ok',
        text: $(this).data('button-ok-text') || TYPO3.lang['button.ok'] || 'OK',
      },
    ]);
    $modal.data('col', col);
    $modal.data('row', row);
    $modal.on('button.clicked', this.modalButtonClickHandler);
    return true;
  }

  /**
   * Returns a cell element from the grid.
   *
   * @param {number} col
   * @param {number} row
   */
  protected getCell(col: number, row: number): any {
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
    new IntersectionObserver((entries: IntersectionObserverEntry[], observer: IntersectionObserver) => {
      entries.forEach(entry => {
        const codemirror: any = document.querySelector(this.selectorCodeMirror);
        if (entry.intersectionRatio > 0 && codemirror) {
          codemirror.CodeMirror.refresh();
        }
      });
    }).observe(gridEditor);
  }
}
