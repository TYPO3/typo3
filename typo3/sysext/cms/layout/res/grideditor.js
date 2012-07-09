/**
 * A JavaScript object to handle, edit, draw and export a grid. The grid is basically
 * a table with some colspan and rowspan. Each cell can additionally hold a name and
 * column.
 *
 * @author Thomas Hempel <thomas@typo3.org>
 */
Ext.namespace('TYPO3.Backend.t3Grid');

TYPO3.Backend.t3Grid = Ext.extend(Ext.Component, {

	constructor: function(config) {

		config = Ext.apply({
			colCount: config.colCount,
			rowCount: config.rowCount,
			data: config.data,
			nameLabel: config.nameLabel,
			columnLabel: config.columnLabel,
			targetElement: config.targetElement
		}, config);

		TYPO3.Backend.t3Grid.superclass.constructor.call(this, config);
	},

	/**
	 * Adds a row below the grid
	 */
	addRow: function() {
		var newRow = [];
		for (var i = 0; i < this.colCount; i++) {
			newRow[i] = {spanned:false,rowspan:1,colspan:1};
		}
		this.data.push(newRow);
		this.rowCount++;
	},

	/**
	 * Removes the last row of the grid and adjusts all cells that might be effected
	 * by that change. (Removing colspans)
	 *
	 * @returns void
	 */
	removeRow: function() {
		if (this.rowCount <= 1) return false;
		var newData = [];
		for (var rowIndex = 0; rowIndex < this.rowCount - 1; rowIndex++) {
			newData.push(this.data[rowIndex]);
		}

		// fix rowspan in former last row
		for (var colIndex = 0; colIndex < this.colCount; colIndex++) {
			if (this.data[this.rowCount - 1][colIndex].spanned == true) {
				this.findUpperCellWidthRowspanAndDecreaseByOne(colIndex, this.rowCount - 1);
			}
		}

		this.data = newData;
		this.rowCount--;
	},

	/**
	 * Takes a cell and looks above it if there are any cells that have colspans that
	 * spanns into the given cell. This is used when a row was removed from the grid
	 * to make sure that no cell with wrong colspans exists in the grid.
	 *
	 * @param col integer
	 * @param row integer
	 * @return void
	 */
	findUpperCellWidthRowspanAndDecreaseByOne: function(col, row) {
		var upperCell = this.getCell(col, row - 1);
		if (!upperCell) return false;

		if (upperCell.spanned == true) {
			this.findUpperCellWidthRowspanAndDecreaseByOne(col, row - 1);
		} else {
			if (upperCell.rowspan > 1) {
				this.removeRowspan(col, row - 1);
			}
		}
	},

	/**
	 * Removes the outermost right column from the grid.
	 *
	 * @return void
	 */
	removeColumn: function() {
		if (this.colCount <= 1) return false;
		var newData = [];

		for (var rowIndex = 0; rowIndex < this.rowCount; rowIndex++) {
			var newRow = [];
			for (colIndex = 0; colIndex < this.colCount - 1; colIndex++) {
				newRow.push(this.data[rowIndex][colIndex]);
			}
			if (this.data[rowIndex][this.colCount - 1].spanned == true) {
				this.findLeftCellWidthColspanAndDecreaseByOne(this.colCount - 1, rowIndex);
			}
			newData.push(newRow);
		}

		this.data = newData;
		this.colCount--;
	},

	/**
	 * Checks if there are any cells on the left side of a given cell with a
	 * rowspan that spans over the given cell.
	 *
	 * @param col integer
	 * @param row integer
	 * @return void
	 */
	findLeftCellWidthColspanAndDecreaseByOne: function(col, row) {
		var leftCell = this.getCell(col - 1, row);
		if (!leftCell) return false;

		if (leftCell.spanned == true) {
			this.findLeftCellWidthColspanAndDecreaseByOne(col - 1, row);
		} else {
			if (leftCell.colspan > 1) {
				this.removeColspan(col - 1, row);
			}
		}
	},

	/**
	 * Adds a column at the right side of the grid.
	 *
	 * @return void
	 */
	addColumn: function() {
		for (var rowIndex = 0; rowIndex < this.rowCount; rowIndex++) {
			this.data[rowIndex].push({
				spanned: false,
				rowspan: 1,
				colspan: 1,
				name: this.colCount + 'x' + rowIndex
			});
		}
		this.colCount++;
	},

	/**
	 * Draws the grid as table into a given container.
	 * It also adds all needed links and bindings to the cells to make it editable.
	 *
	 * @return void
	 */
	drawTable: function() {
		var domHelper = Ext.DomHelper;
		var newTable = {
			tag: 'table',
			children: [],
			id: 'base',
			border: '0',
			width: '100%',
			height: '100%',
			cls: 'editor',
			cellspacing: '0',
			cellpadding: '0'
		};

		var colgroups = {
			tag: 'colgroup',
			children: []
		};
		for (var col = 0; col < this.colCount; col++) {
			colgroups.children.push({
				tag: 'col',
				style: 'width:' + parseInt(100 / this.colCount, 10) + '%'
			});
		}
		newTable.children.push(colgroups);

		for (var row = 0; row < this.rowCount; row++) {
			var rowData = this.data[row];
			if (rowData.length == 0) continue;

			var rowSpec = {tag: 'tr', children:[]};

			for (var col = 0; col < this.colCount; col++) {
				var cell = this.data[row][col];
				if (cell.spanned == true) {
					continue;
				}

				var cellHtml = '<div class="cell_container"><a class="link_editor" id="e_'
						+ col + '_' + row + '" title="' + TYPO3.l10n.localize('editCell') + '" href="#"><!-- --></a>';
				if (this.cellCanSpanRight(col, row)) {
					cellHtml += '<a href="#" id="r_'
						+ col + '_' + row + '" title="' + TYPO3.l10n.localize('mergeCell') + '" class="link_expand_right"><!-- --></a>';
				}
				if (this.cellCanShrinkLeft(col, row)) {
					cellHtml += '<a href="#" id="l_'
						+ col + '_' + row + '" title="' + TYPO3.l10n.localize('splitCell') + '" class="link_shrink_left"><!-- --></a>';
				}
				if (this.cellCanSpanDown(col, row)) {
					cellHtml += '<a href="#" id="d_'
						+ col + '_' + row + '" title="' + TYPO3.l10n.localize('mergeCell') + '" class="link_expand_down"><!-- --></a>';
				}
				if (this.cellCanShrinkUp(col, row)) {
					cellHtml += '<a href="#" id="u_'
						+ col + '_' + row + '" title="' + TYPO3.l10n.localize('splitCell') + '" class="link_shrink_up"><!-- --></a>';
				}
				cellHtml += '</div>';

				cellHtml += '<div class="cell_data">' + TYPO3.l10n.localize('name') + ': ' + (cell.name ? cell.name : TYPO3.l10n.localize('notSet'))
						+ '<br />' + TYPO3.l10n.localize('column') + ': '
						+ (cell.column === undefined ? TYPO3.l10n.localize('notSet') : parseInt(cell.column, 10)) + '</div>';

				// create cells
				var child = {
					tag: 'td',
					height: parseInt(100 / this.rowCount, 10) * cell.rowspan + '%',
					width: parseInt(100 / this.colCount, 10) * cell.colspan + '%',
					html: cellHtml
				};
				if (cell.colspan > 1) {
					child.colspan = cell.colspan;
				}
				if (cell.rowspan > 1) {
					child.rowspan = cell.rowspan;
				}
				rowSpec.children.push(child);
			}

			newTable.children.push(rowSpec);

		}

		domHelper.overwrite(Ext.Element.get(this.targetElement), newTable);
		this.bindLinks();
	},

	/**
	 * Sets the name of a certain grid element.
	 *
	 * @param newName string
	 * @param col integer
	 * @param row integer
	 *
	 * @return boolean
	 */
	setName: function(newName, col, row) {
		var cell = this.getCell(col, row);
		if (!cell) return false;
		cell.name = newName;
		return true;
	},

	/**
	 * Sets the column field for a certain grid element. This is NOT the column of the
	 * element itself.
	 *
	 * @param newColumn integer
	 * @param col integer
	 * @param row integer
	 *
	 * @return boolean
	 */
	setColumn: function(newColumn, col, row) {
		var cell = this.getCell(col, row);
		if (!cell) return false;
		cell.column = newColumn;
		return true;
	},

	/**
	 * Searches for all a tags with certain classes and binds some actions to them.
	 *
	 * @return void
	 */
	bindLinks: function() {
		for (var row = 0; row < this.rowCount; row++) {
			for (var col = 0; col < this.colCount; col++) {
				// span right
				var el = Ext.Element.get('r_' + col + '_' + row);
				if (el) {
					el.addListener('click', function(e, sender, params) {
						this.addColspan(params.colIndex, params.rowIndex);
						this.drawTable();
					}, this, {stopEvent:true, colIndex:col, rowIndex:row});
				}

				// reduce to left
				var el = Ext.Element.get('l_' + col + '_' + row);
				if (el) {
					el.addListener('click', function(e, sender, params) {
						this.removeColspan(params.colIndex, params.rowIndex);
						this.drawTable();
					}, this, {stopEvent:true, colIndex:col, rowIndex:row});
				}

				// span down
				var el = Ext.Element.get('d_' + col + '_' + row);
				if (el) {
					el.addListener('click', function(e, sender, params) {
						this.addRowspan(params.colIndex, params.rowIndex);
						this.drawTable();
					}, this, {stopEvent:true, colIndex:col, rowIndex:row});
				}

				// reduce up
				var el = Ext.Element.get('u_' + col + '_' + row);
				if (el) {
					el.addListener('click', function(e, sender, params) {
						this.removeRowspan(params.colIndex, params.rowIndex);
						this.drawTable();
					}, this, {stopEvent:true, colIndex:col, rowIndex:row});
				}

				// edit
				var el = Ext.Element.get('e_' + col + '_' + row);
				if (el) {
					el.addListener('click', function(e, sender, params) {
						this.showOptions(sender, params.colIndex, params.rowIndex);
					}, this, {stopEvent:true, colIndex:col, rowIndex:row});
				}
			}
		}
	},

	/**
	 * Creates an ExtJs Window with two input fields and shows it. On save, the data
	 * is written into the grid element.
	 *
	 * @param sender DOM-object (the link)
	 * @param col integer
	 * @param row integer
	 */
	showOptions: function(sender, col, row) {
		var win;
		sender = Ext.get('base');
		var cell = this.getCell(col, row);
		if (!cell) return false;

		if (!win) {
			var fieldName = new Ext.form.TextField({
				fieldLabel: TYPO3.l10n.localize('name'),
				name: 'name',
				width: 270,
				value: cell.name,
				tabIndex: 1,
				listeners: {
					render: function(c) {
						Ext.QuickTips.register({
							target: c,
							text: TYPO3.l10n.localize('nameHelp')
						});
					}
				}
			});

			var fieldColumn = new Ext.form.NumberField({
				fieldLabel: TYPO3.l10n.localize('column'),
				name: 'column',
				width: 50,
				value: cell.column,
				tabIndex: 2,
				listeners: {
					render: function(c) {
						Ext.QuickTips.register({
							target: c,
							text: TYPO3.l10n.localize('columnHelp')
						});
					}
				}
			});

			win = new Ext.Window({
				layout: 'fit',
				title: TYPO3.l10n.localize('title'),
				width: 400,
				modal: true,
				closable: true,
				resizable: false,

				items: [
					{
						xtype: 'fieldset',
						autoHeight: true,
						autoWidth: true,
						labelWidth: 100,
						border: false,

						items: [fieldName, fieldColumn]
					}
				],

				buttons: [
					{
						iconCls:'save',
						text: TYPO3.l10n.localize('save'),
						handler: function(fieldName, fieldColumn, col, row) {
							t3Grid.setName(fieldName.getValue(), col, row);
							t3Grid.setColumn(fieldColumn.getValue(), col, row);
							win.close();
							t3Grid.drawTable();
						}.createDelegate(this, [fieldName, fieldColumn, col, row])
					}
				]
			});
		}
		win.show(this);
	},

	/**
	 * Returns a cell element from the grid.
	 *
	 * @param col integer
	 * @param row integer
	 * return Object
	 */
	getCell: function(col, row) {
		if (col > this.colCount - 1) return false;
		if (row > this.rowCount - 1) return false;
		return this.data[row][col];
	},

	/**
	 * Checks wether a cell can span to the right or not. A cell can span to the right
	 * if it is not in the last column and if there is no cell beside it that is
	 * already overspanned by some other cell.
	 *
	 * @param col integer
	 * @param row integer
	 *
	 * @return boolean
	 */
	cellCanSpanRight: function(col, row) {
		if (col == this.colCount - 1) {
			return false;
		}

		var cell = this.getCell(col, row);
		if (cell.rowspan > 1) {
			for (var rowIndex = row; rowIndex < row + cell.rowspan; rowIndex++) {
				var checkCell = this.getCell(col + cell.colspan, rowIndex);
				if (!checkCell || checkCell.spanned == true || checkCell.colspan > 1 || checkCell.rowspan > 1) {
					return false;
				}
			}
		} else {
			var checkCell = this.getCell(col + cell.colspan, row);
			if (!checkCell || cell.spanned == true || checkCell.spanned == true || checkCell.colspan > 1 || checkCell.rowspan > 1) {
				return false;
			}
		}

		return true;
	},

	/**
	 * Checks wether a cell can span down or not.
	 *
	 * @param col integer
	 * @param row integer
	 *
	 * @return boolean
	 */
	cellCanSpanDown: function(col, row) {
		if (row == this.rowCount - 1) {
			return false;
		}

		var cell = this.getCell(col, row);
		if (cell.colspan > 1) {
			// we have to check all cells on the right side for the complete colspan
			for (var colIndex = col; colIndex < col + cell.colspan; colIndex++) {
				var checkCell = this.getCell(colIndex, row + cell.rowspan);
				if (!checkCell || checkCell.spanned == true || checkCell.colspan > 1 || checkCell.rowspan > 1) {
					return false;
				}
			}
		} else {
			var checkCell = this.getCell(col, row + cell.rowspan);
			if (!checkCell || cell.spanned == true || checkCell.spanned == true || checkCell.colspan > 1 || checkCell.rowspan > 1) {
				return false;
			}
		}

		return true;
	},

	/**
	 * Checks if a cell can shrink to the left. It can shrink if the colspan of the
	 * cell is bigger than 1.
	 *
	 * @param col integr
	 * @param row integer
	 *
	 * @return boolean
	 */
	cellCanShrinkLeft: function(col, row) {
		return (this.data[row][col].colspan > 1);
	},

	/**
	 * Returns if a cell can shrink up. This is the case if a cell has at least
	 * a rowspan of 2.
	 *
	 * @param col integr
	 * @param row integer
	 *
	 * @return boolean
	 */
	cellCanShrinkUp: function(col, row) {
		return (this.data[row][col].rowspan > 1);
	},

	/**
	 * Adds a colspan to a grid element.
	 *
	 * @param col integr
	 * @param row integer
	 */
	addColspan: function(col, row) {
		var cell = this.getCell(col, row);
		if (!cell || !this.cellCanSpanRight(col, row)) return false;

		for (var rowIndex = row; rowIndex < row + cell.rowspan; rowIndex++) {
			this.data[rowIndex][col + cell.colspan].spanned = true;
		}
		cell.colspan += 1;
	},

	/**
	 * Adds a rowspan to grid element.
	 *
	 * @param col integr
	 * @param row integer
	 *
	 * @return void
	 */
	addRowspan: function(col, row) {
		var cell = this.getCell(col, row);
		if (!cell || !this.cellCanSpanDown(col, row)) return false;

		for (var colIndex = col; colIndex < col + cell.colspan; colIndex++) {
			this.data[row + cell.rowspan][colIndex].spanned = true;
		}
		cell.rowspan += 1;
	},

	/**
	 * Removes a colspan from a grid element.
	 *
	 * @param col integr
	 * @param row integer
	 *
	 * @return void
	 */
	removeColspan: function(col, row) {
		var cell = this.getCell(col, row);
		if (!cell || !this.cellCanShrinkLeft(col, row)) return false;

		cell.colspan -= 1;
		for (var rowIndex = row; rowIndex < row + cell.rowspan; rowIndex++) {
			this.data[rowIndex][col + cell.colspan].spanned = false;
		}
	},

	/**
	 * Removes a rowspan from a grid element.
	 *
	 * @param col integr
	 * @param row integer
	 *
	 * @return void
	 */
	removeRowspan: function(col, row) {
		var cell = this.getCell(col, row);
		if (!cell || !this.cellCanShrinkUp(col, row)) return false;

		cell.rowspan -= 1;
		for (var colIndex = col; colIndex < col + cell.colspan; colIndex++) {
			this.data[row + cell.rowspan][colIndex].spanned = false;
		}
	},

	/**
	 * Exports the current grid to a TypoScript notation that can be read by the
	 * page module and is human readable.
	 *
	 * @return string
	 */
	export2LayoutRecord: function() {
		var result = "backend_layout {\n\tcolCount = " + this.colCount + "\n\trowCount = " + this.rowCount + "\n\trows {\n";
		for (var row = 0; row < this.rowCount; row++) {
			result += "\t\t" + (row + 1) + " {\n";
			result += "\t\t\tcolumns {\n";
			colIndex = 0;
			for (var col = 0; col < this.colCount; col++) {
				var cell = this.getCell(col, row);
				if (cell && !cell.spanned) {
					colIndex++;
					result += "\t\t\t\t" + (colIndex) + " {\n";
					result += "\t\t\t\t\tname = " + ((!cell.name) ? col + "x" + row : cell.name) + "\n";
					if (cell.colspan > 1) result += "\t\t\t\t\tcolspan = " + cell.colspan + "\n";
					if (cell.rowspan > 1) result += "\t\t\t\t\trowspan = " + cell.rowspan + "\n";
					if (typeof(cell.column) === 'number') result += "\t\t\t\t\tcolPos = " + cell.column + "\n";
					result += "\t\t\t\t}\n";
				}

			}
			result += "\t\t\t}\n";
			result += "\t\t}\n";
		}

		result += "\t}\n}\n";
		return result;
	}
});
