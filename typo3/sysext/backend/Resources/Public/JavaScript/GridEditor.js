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
var __values = (this && this.__values) || function (o) {
    var m = typeof Symbol === "function" && o[Symbol.iterator], i = 0;
    if (m) return m.call(o);
    return {
        next: function () {
            if (o && i >= o.length) o = void 0;
            return { value: o && o[i++], done: !o };
        }
    };
};
define(["require", "exports", "jquery", "TYPO3/CMS/Backend/Modal", "TYPO3/CMS/Backend/Severity", "bootstrap"], function (require, exports, $, Modal, Severity) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    /**
     * Module: TYPO3/CMS/Backend/GridEditor
     * @exports TYPO3/CMS/Backend/GridEditor
     */
    var GridEditor = (function () {
        /**
         *
         * @param {GridEditorConfigurationInterface} config
         */
        function GridEditor(config) {
            if (config === void 0) { config = null; }
            var _this = this;
            this.colCount = 1;
            this.rowCount = 1;
            this.nameLabel = 'name';
            this.columnLabel = 'columen label';
            this.defaultCell = { spanned: 0, rowspan: 1, colspan: 1, name: '', colpos: '', column: undefined };
            this.selectorEditor = '.t3js-grideditor';
            this.selectorAddColumn = '.t3js-grideditor-addcolumn';
            this.selectorRemoveColumn = '.t3js-grideditor-removecolumn';
            this.selectorAddRowTop = '.t3js-grideditor-addrow-top';
            this.selectorRemoveRowTop = '.t3js-grideditor-removerow-top';
            this.selectorAddRowBottom = '.t3js-grideditor-addrow-bottom';
            this.selectorRemoveRowBottom = '.t3js-grideditor-removerow-bottom';
            this.selectorLinkEditor = '.t3js-grideditor-link-editor';
            this.selectorLinkExpandRight = '.t3js-grideditor-link-expand-right';
            this.selectorLinkShrinkLeft = '.t3js-grideditor-link-shrink-left';
            this.selectorLinkExpandDown = '.t3js-grideditor-link-expand-down';
            this.selectorLinkShrinkUp = '.t3js-grideditor-link-shrink-up';
            this.selectorDocHeaderSave = '.t3js-grideditor-savedok';
            this.selectorDocHeaderSaveClose = '.t3js-grideditor-savedokclose';
            this.selectorConfigPreview = '.t3js-grideditor-preview-config';
            this.selectorConfigPreviewButton = '.t3js-grideditor-preview-button';
            /**
             *
             * @param {Event} e
             */
            this.modalButtonClickHandler = function (e) {
                var button = e.target;
                if (button.name === 'cancel') {
                    Modal.currentModal.trigger('modal-dismiss');
                }
                else if (button.name === 'ok') {
                    _this.setName(Modal.currentModal.find('.t3js-grideditor-field-name').val(), Modal.currentModal.data('col'), Modal.currentModal.data('row'));
                    _this.setColumn(Modal.currentModal.find('.t3js-grideditor-field-colpos').val(), Modal.currentModal.data('col'), Modal.currentModal.data('row'));
                    _this.drawTable();
                    _this.writeConfig(_this.export2LayoutRecord());
                    Modal.currentModal.trigger('modal-dismiss');
                }
            };
            /**
             *
             * @param {Event} e
             */
            this.addColumnHandler = function (e) {
                e.preventDefault();
                _this.addColumn();
                _this.drawTable();
                _this.writeConfig(_this.export2LayoutRecord());
            };
            /**
             *
             * @param {Event} e
             */
            this.removeColumnHandler = function (e) {
                e.preventDefault();
                _this.removeColumn();
                _this.drawTable();
                _this.writeConfig(_this.export2LayoutRecord());
            };
            /**
             *
             * @param {Event} e
             */
            this.addRowTopHandler = function (e) {
                e.preventDefault();
                _this.addRowTop();
                _this.drawTable();
                _this.writeConfig(_this.export2LayoutRecord());
            };
            /**
             *
             * @param {Event} e
             */
            this.addRowBottomHandler = function (e) {
                e.preventDefault();
                _this.addRowBottom();
                _this.drawTable();
                _this.writeConfig(_this.export2LayoutRecord());
            };
            /**
             *
             * @param {Event} e
             */
            this.removeRowTopHandler = function (e) {
                e.preventDefault();
                _this.removeRowTop();
                _this.drawTable();
                _this.writeConfig(_this.export2LayoutRecord());
            };
            /**
             *
             * @param {Event} e
             */
            this.removeRowBottomHandler = function (e) {
                e.preventDefault();
                _this.removeRowBottom();
                _this.drawTable();
                _this.writeConfig(_this.export2LayoutRecord());
            };
            /**
             *
             * @param {Event} e
             */
            this.linkEditorHandler = function (e) {
                e.preventDefault();
                var $element = $(e.target);
                _this.showOptions($element.data('col'), $element.data('row'));
            };
            /**
             *
             * @param {Event} e
             */
            this.linkExpandRightHandler = function (e) {
                e.preventDefault();
                var $element = $(e.target);
                _this.addColspan($element.data('col'), $element.data('row'));
                _this.drawTable();
                _this.writeConfig(_this.export2LayoutRecord());
            };
            /**
             *
             * @param {Event} e
             */
            this.linkShrinkLeftHandler = function (e) {
                e.preventDefault();
                var $element = $(e.target);
                _this.removeColspan($element.data('col'), $element.data('row'));
                _this.drawTable();
                _this.writeConfig(_this.export2LayoutRecord());
            };
            /**
             *
             * @param {Event} e
             */
            this.linkExpandDownHandler = function (e) {
                e.preventDefault();
                var $element = $(e.target);
                _this.addRowspan($element.data('col'), $element.data('row'));
                _this.drawTable();
                _this.writeConfig(_this.export2LayoutRecord());
            };
            /**
             *
             * @param {Event} e
             */
            this.linkShrinkUpHandler = function (e) {
                e.preventDefault();
                var $element = $(e.target);
                _this.removeRowspan($element.data('col'), $element.data('row'));
                _this.drawTable();
                _this.writeConfig(_this.export2LayoutRecord());
            };
            /**
             *
             * @param {Event} e
             */
            this.configPreviewButtonHandler = function (e) {
                e.preventDefault();
                var $preview = $(_this.selectorConfigPreview);
                var $button = $(_this.selectorConfigPreviewButton);
                if ($preview.is(':visible')) {
                    $button.empty().append(TYPO3.lang['button.showPageTsConfig']);
                    $(_this.selectorConfigPreview).slideUp();
                }
                else {
                    $button.empty().append(TYPO3.lang['button.hidePageTsConfig']);
                    $(_this.selectorConfigPreview).slideDown();
                }
            };
            var $element = $(this.selectorEditor);
            this.colCount = $element.data('colcount');
            this.rowCount = $element.data('rowcount');
            this.field = $('input[name="' + $element.data('field') + '"]');
            this.data = $element.data('data');
            this.nameLabel = config !== null ? config.nameLabel : 'Name';
            this.columnLabel = config !== null ? config.columnLabel : 'Column';
            this.targetElement = $(this.selectorEditor);
            $(this.selectorConfigPreview).hide();
            $(this.selectorConfigPreviewButton).empty().append(TYPO3.lang['button.showPageTsConfig']);
            this.initializeEvents();
            this.drawTable();
            this.writeConfig(this.export2LayoutRecord());
        }
        /**
         * Remove all markup
         *
         * @param {String} input
         * @returns {string}
         */
        GridEditor.stripMarkup = function (input) {
            input = input.replace(/<(.*)>/gi, '');
            return $('<p>' + input + '</p>').text();
        };
        /**
         *
         */
        GridEditor.prototype.initializeEvents = function () {
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
            $(document).on('click', this.selectorConfigPreviewButton, this.configPreviewButtonHandler);
        };
        /**
         * Create a new cell from defaultCell
         * @returns {Object}
         */
        GridEditor.prototype.getNewCell = function () {
            return $.extend({}, this.defaultCell);
        };
        /**
         * write data back to hidden field
         *
         * @param data
         */
        GridEditor.prototype.writeConfig = function (data) {
            this.field.val(data);
            var configLines = data.split('\n');
            var config = '';
            try {
                for (var configLines_1 = __values(configLines), configLines_1_1 = configLines_1.next(); !configLines_1_1.done; configLines_1_1 = configLines_1.next()) {
                    var line = configLines_1_1.value;
                    if (line) {
                        config += '\t\t\t' + line + '\n';
                    }
                }
            }
            catch (e_1_1) { e_1 = { error: e_1_1 }; }
            finally {
                try {
                    if (configLines_1_1 && !configLines_1_1.done && (_a = configLines_1.return)) _a.call(configLines_1);
                }
                finally { if (e_1) throw e_1.error; }
            }
            $(this.selectorConfigPreview).find('code').empty().append('mod.web_layout.BackendLayouts {\n' +
                '  exampleKey {\n' +
                '    title = Example\n' +
                '    icon = EXT:example_extension/Resources/Public/Images/BackendLayouts/default.gif\n' +
                '    config {\n' +
                config.replace(new RegExp('\t', 'g'), '  ') +
                '    }\n' +
                '  }\n' +
                '}\n');
            var e_1, _a;
        };
        /**
         * Add a new row at the top
         */
        GridEditor.prototype.addRowTop = function () {
            var newRow = [];
            for (var i = 0; i < this.colCount; i++) {
                var newCell = this.getNewCell();
                newCell.name = i + 'x' + this.data.length;
                newRow[i] = newCell;
            }
            this.data.unshift(newRow);
            this.rowCount++;
        };
        /**
         * Add a new row at the bottom
         */
        GridEditor.prototype.addRowBottom = function () {
            var newRow = [];
            for (var i = 0; i < this.colCount; i++) {
                var newCell = this.getNewCell();
                newCell.name = i + 'x' + this.data.length;
                newRow[i] = newCell;
            }
            this.data.push(newRow);
            this.rowCount++;
        };
        /**
         * Removes the first row of the grid and adjusts all cells that might be effected
         * by that change. (Removing colspans)
         */
        GridEditor.prototype.removeRowTop = function () {
            if (this.rowCount <= 1) {
                return false;
            }
            var newData = [];
            for (var rowIndex = 1; rowIndex < this.rowCount; rowIndex++) {
                newData.push(this.data[rowIndex]);
            }
            // fix rowspan in former last row
            for (var colIndex = 0; colIndex < this.colCount; colIndex++) {
                if (this.data[0][colIndex].spanned === 1) {
                    this.findUpperCellWidthRowspanAndDecreaseByOne(colIndex, 0);
                }
            }
            this.data = newData;
            this.rowCount--;
            return true;
        };
        /**
         * Removes the last row of the grid and adjusts all cells that might be effected
         * by that change. (Removing colspans)
         */
        GridEditor.prototype.removeRowBottom = function () {
            if (this.rowCount <= 1) {
                return false;
            }
            var newData = [];
            for (var rowIndex = 0; rowIndex < this.rowCount - 1; rowIndex++) {
                newData.push(this.data[rowIndex]);
            }
            // fix rowspan in former last row
            for (var colIndex = 0; colIndex < this.colCount; colIndex++) {
                if (this.data[this.rowCount - 1][colIndex].spanned === 1) {
                    this.findUpperCellWidthRowspanAndDecreaseByOne(colIndex, this.rowCount - 1);
                }
            }
            this.data = newData;
            this.rowCount--;
            return true;
        };
        /**
         * Takes a cell and looks above it if there are any cells that have colspans that
         * spans into the given cell. This is used when a row was removed from the grid
         * to make sure that no cell with wrong colspans exists in the grid.
         *
         * @param {number} col
         * @param {number} row integer
         */
        GridEditor.prototype.findUpperCellWidthRowspanAndDecreaseByOne = function (col, row) {
            var upperCell = this.getCell(col, row - 1);
            if (!upperCell) {
                return false;
            }
            if (upperCell.spanned === 1) {
                this.findUpperCellWidthRowspanAndDecreaseByOne(col, row - 1);
            }
            else {
                if (upperCell.rowspan > 1) {
                    this.removeRowspan(col, row - 1);
                }
            }
            return true;
        };
        /**
         * Removes the outermost right column from the grid.
         */
        GridEditor.prototype.removeColumn = function () {
            if (this.colCount <= 1) {
                return false;
            }
            var newData = [];
            for (var rowIndex = 0; rowIndex < this.rowCount; rowIndex++) {
                var newRow = [];
                for (var colIndex = 0; colIndex < this.colCount - 1; colIndex++) {
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
        };
        /**
         * Checks if there are any cells on the left side of a given cell with a
         * rowspan that spans over the given cell.
         *
         * @param {number} col
         * @param {number} row
         */
        GridEditor.prototype.findLeftCellWidthColspanAndDecreaseByOne = function (col, row) {
            var leftCell = this.getCell(col - 1, row);
            if (!leftCell) {
                return false;
            }
            if (leftCell.spanned === 1) {
                this.findLeftCellWidthColspanAndDecreaseByOne(col - 1, row);
            }
            else {
                if (leftCell.colspan > 1) {
                    this.removeColspan(col - 1, row);
                }
            }
            return true;
        };
        /**
         * Adds a column at the right side of the grid.
         */
        GridEditor.prototype.addColumn = function () {
            for (var rowIndex = 0; rowIndex < this.rowCount; rowIndex++) {
                var newCell = this.getNewCell();
                newCell.name = this.colCount + 'x' + rowIndex;
                this.data[rowIndex].push(newCell);
            }
            this.colCount++;
        };
        /**
         * Draws the grid as table into a given container.
         * It also adds all needed links and bindings to the cells to make it editable.
         */
        GridEditor.prototype.drawTable = function () {
            var $colgroup = $('<colgroup>');
            for (var col = 0; col < this.colCount; col++) {
                var percent = 100 / this.colCount;
                $colgroup.append($('<col>').css({
                    width: parseInt(percent.toString(), 10) + '%',
                }));
            }
            var $table = $('<table id="base" class="table editor">');
            $table.append($colgroup);
            for (var row = 0; row < this.rowCount; row++) {
                var rowData = this.data[row];
                if (rowData.length === 0) {
                    continue;
                }
                var $row = $('<tr>');
                for (var col = 0; col < this.colCount; col++) {
                    var cell = this.data[row][col];
                    if (cell.spanned === 1) {
                        continue;
                    }
                    var percentRow = 100 / this.rowCount;
                    var percentCol = 100 / this.colCount;
                    var $cell = $('<td>').css({
                        height: parseInt(percentRow.toString(), 10) * cell.rowspan + '%',
                        width: parseInt(percentCol.toString(), 10) * cell.colspan + '%',
                    });
                    var $container = $('<div class="cell_container">');
                    $cell.append($container);
                    var $anchor = $('<a href="#" data-col="' + col + '" data-row="' + row + '">');
                    $container.append($anchor
                        .clone()
                        .attr('class', 't3js-grideditor-link-editor link link_editor')
                        .attr('title', TYPO3.lang.grid_editCell));
                    if (this.cellCanSpanRight(col, row)) {
                        $container.append($anchor
                            .clone()
                            .attr('class', 't3js-grideditor-link-expand-right link link_expand_right')
                            .attr('title', TYPO3.lang.grid_mergeCell));
                    }
                    if (this.cellCanShrinkLeft(col, row)) {
                        $container.append($anchor
                            .clone()
                            .attr('class', 't3js-grideditor-link-shrink-left link link_shrink_left')
                            .attr('title', TYPO3.lang.grid_splitCell));
                    }
                    if (this.cellCanSpanDown(col, row)) {
                        $container.append($anchor
                            .clone()
                            .attr('class', 't3js-grideditor-link-expand-down link link_expand_down')
                            .attr('title', TYPO3.lang.grid_mergeCell));
                    }
                    if (this.cellCanShrinkUp(col, row)) {
                        $container.append($anchor
                            .clone()
                            .attr('class', 't3js-grideditor-link-shrink-up link link_shrink_up')
                            .attr('title', TYPO3.lang.grid_splitCell));
                    }
                    $cell.append($('<div class="cell_data">')
                        .html(TYPO3.lang.grid_name + ': '
                        + (cell.name ? GridEditor.stripMarkup(cell.name) : TYPO3.lang.grid_notSet)
                        + '<br />'
                        + TYPO3.lang.grid_column + ': '
                        + (typeof cell.column === 'undefined' || isNaN(cell.column)
                            ? TYPO3.lang.grid_notSet
                            : parseInt(cell.column, 10))));
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
        };
        /**
         * Sets the name of a certain grid element.
         *
         * @param {String} newName
         * @param {number} col
         * @param {number} row
         *
         * @returns {Boolean}
         */
        GridEditor.prototype.setName = function (newName, col, row) {
            var cell = this.getCell(col, row);
            if (!cell) {
                return false;
            }
            cell.name = GridEditor.stripMarkup(newName);
            return true;
        };
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
        GridEditor.prototype.setColumn = function (newColumn, col, row) {
            var cell = this.getCell(col, row);
            if (!cell) {
                return false;
            }
            cell.column = parseInt(newColumn.toString(), 10);
            return true;
        };
        /**
         * Creates an ExtJs Window with two input fields and shows it. On save, the data
         * is written into the grid element.
         *
         * @param {number} col
         * @param {number} row
         *
         * @returns {Boolean}
         */
        GridEditor.prototype.showOptions = function (col, row) {
            var cell = this.getCell(col, row);
            if (!cell) {
                return false;
            }
            var colPos;
            if (cell.column === 0) {
                colPos = 0;
            }
            else if (cell.column) {
                colPos = parseInt(cell.column.toString(), 10);
            }
            else {
                colPos = '';
            }
            var $markup = $('<div>');
            var $formGroup = $('<div class="form-group">');
            var $label = $('<label>');
            var $input = $('<input>');
            $markup.append([
                $formGroup
                    .clone()
                    .append([
                    $label
                        .clone()
                        .text(TYPO3.lang.grid_nameHelp),
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
                        .text(TYPO3.lang.grid_columnHelp),
                    $input
                        .clone()
                        .attr('type', 'text')
                        .attr('class', 't3js-grideditor-field-colpos form-control')
                        .attr('name', 'column')
                        .val(colPos),
                ]),
            ]);
            var $modal = Modal.show(TYPO3.lang.grid_windowTitle, $markup, Severity.notice, [
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
        };
        /**
         * Returns a cell element from the grid.
         *
         * @param {number} col
         * @param {number} row
         */
        GridEditor.prototype.getCell = function (col, row) {
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
        };
        /**
         * Checks whether a cell can span to the right or not. A cell can span to the right
         * if it is not in the last column and if there is no cell beside it that is
         * already overspanned by some other cell.
         *
         * @param {number} col
         * @param {number} row
         * @returns {Boolean}
         */
        GridEditor.prototype.cellCanSpanRight = function (col, row) {
            if (col === this.colCount - 1) {
                return false;
            }
            var cell = this.getCell(col, row);
            var checkCell;
            if (cell.rowspan > 1) {
                for (var rowIndex = row; rowIndex < row + cell.rowspan; rowIndex++) {
                    checkCell = this.getCell(col + cell.colspan, rowIndex);
                    if (!checkCell || checkCell.spanned === 1 || checkCell.colspan > 1 || checkCell.rowspan > 1) {
                        return false;
                    }
                }
            }
            else {
                checkCell = this.getCell(col + cell.colspan, row);
                if (!checkCell || cell.spanned === 1 || checkCell.spanned === 1 || checkCell.colspan > 1
                    || checkCell.rowspan > 1) {
                    return false;
                }
            }
            return true;
        };
        /**
         * Checks whether a cell can span down or not.
         *
         * @param {number} col
         * @param {number} row
         * @returns {Boolean}
         */
        GridEditor.prototype.cellCanSpanDown = function (col, row) {
            if (row === this.rowCount - 1) {
                return false;
            }
            var cell = this.getCell(col, row);
            var checkCell;
            if (cell.colspan > 1) {
                // we have to check all cells on the right side for the complete colspan
                for (var colIndex = col; colIndex < col + cell.colspan; colIndex++) {
                    checkCell = this.getCell(colIndex, row + cell.rowspan);
                    if (!checkCell || checkCell.spanned === 1 || checkCell.colspan > 1 || checkCell.rowspan > 1) {
                        return false;
                    }
                }
            }
            else {
                checkCell = this.getCell(col, row + cell.rowspan);
                if (!checkCell || cell.spanned === 1 || checkCell.spanned === 1 || checkCell.colspan > 1
                    || checkCell.rowspan > 1) {
                    return false;
                }
            }
            return true;
        };
        /**
         * Checks if a cell can shrink to the left. It can shrink if the colspan of the
         * cell is bigger than 1.
         *
         * @param {number} col
         * @param {number} row
         * @returns {Boolean}
         */
        GridEditor.prototype.cellCanShrinkLeft = function (col, row) {
            return (this.data[row][col].colspan > 1);
        };
        /**
         * Returns if a cell can shrink up. This is the case if a cell has at least
         * a rowspan of 2.
         *
         * @param {number} col
         * @param {number} row
         * @returns {Boolean}
         */
        GridEditor.prototype.cellCanShrinkUp = function (col, row) {
            return (this.data[row][col].rowspan > 1);
        };
        /**
         * Adds a colspan to a grid element.
         *
         * @param {number} col
         * @param {number} row
         * @returns {Boolean}
         */
        GridEditor.prototype.addColspan = function (col, row) {
            var cell = this.getCell(col, row);
            if (!cell || !this.cellCanSpanRight(col, row)) {
                return false;
            }
            for (var rowIndex = row; rowIndex < row + cell.rowspan; rowIndex++) {
                this.data[rowIndex][col + cell.colspan].spanned = 1;
            }
            cell.colspan += 1;
            return true;
        };
        /**
         * Adds a rowspan to grid element.
         *
         * @param {number} col
         * @param {number} row
         * @returns {Boolean}
         */
        GridEditor.prototype.addRowspan = function (col, row) {
            var cell = this.getCell(col, row);
            if (!cell || !this.cellCanSpanDown(col, row)) {
                return false;
            }
            for (var colIndex = col; colIndex < col + cell.colspan; colIndex++) {
                this.data[row + cell.rowspan][colIndex].spanned = 1;
            }
            cell.rowspan += 1;
            return true;
        };
        /**
         * Removes a colspan from a grid element.
         *
         * @param {number} col
         * @param {number} row
         * @returns {Boolean}
         */
        GridEditor.prototype.removeColspan = function (col, row) {
            var cell = this.getCell(col, row);
            if (!cell || !this.cellCanShrinkLeft(col, row)) {
                return false;
            }
            cell.colspan -= 1;
            for (var rowIndex = row; rowIndex < row + cell.rowspan; rowIndex++) {
                this.data[rowIndex][col + cell.colspan].spanned = 0;
            }
            return true;
        };
        /**
         * Removes a rowspan from a grid element.
         *
         * @param {number} col
         * @param {number} row
         * @returns {Boolean}
         */
        GridEditor.prototype.removeRowspan = function (col, row) {
            var cell = this.getCell(col, row);
            if (!cell || !this.cellCanShrinkUp(col, row)) {
                return false;
            }
            cell.rowspan -= 1;
            for (var colIndex = col; colIndex < col + cell.colspan; colIndex++) {
                this.data[row + cell.rowspan][colIndex].spanned = 0;
            }
            return true;
        };
        /**
         * Exports the current grid to a TypoScript notation that can be read by the
         * page module and is human readable.
         *
         * @returns {String}
         */
        GridEditor.prototype.export2LayoutRecord = function () {
            var result = 'backend_layout {\n\tcolCount = ' + this.colCount + '\n\trowCount = ' + this.rowCount + '\n\trows {\n';
            for (var row = 0; row < this.rowCount; row++) {
                result += '\t\t' + (row + 1) + ' {\n';
                result += '\t\t\tcolumns {\n';
                var colIndex = 0;
                for (var col = 0; col < this.colCount; col++) {
                    var cell = this.getCell(col, row);
                    if (cell) {
                        if (!cell.spanned) {
                            colIndex++;
                            result += '\t\t\t\t' + (colIndex) + ' {\n';
                            result += '\t\t\t\t\tname = ' + ((!cell.name) ? col + 'x' + row : cell.name) + '\n';
                            if (cell.colspan > 1) {
                                result += '\t\t\t\t\tcolspan = ' + cell.colspan + '\n';
                            }
                            if (cell.rowspan > 1) {
                                result += '\t\t\t\t\trowspan = ' + cell.rowspan + '\n';
                            }
                            if (typeof (cell.column) === 'number') {
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
        };
        return GridEditor;
    }());
    exports.GridEditor = GridEditor;
});
