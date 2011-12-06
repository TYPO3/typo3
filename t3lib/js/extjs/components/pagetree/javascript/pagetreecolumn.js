/***************************************************************
*  Copyright notice
*
*  (c) 2011 Stanislas Rolland <typo3@sjbr.ca>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * @class TYPO3.Components.PageTree.Column
 *
 * Page tree column
 *
 * @namespace TYPO3.Components.PageTree
 * @extends Ext.grid.column.Column
 * @author Stanislas Rolland <typo3@sjbr.ca>
 */
Ext.define('TYPO3.Components.PageTree.Column', {
	extend: 'Ext.grid.column.Column',
	alias: 'widget.pagetreecolumn',
	
	/**
	 * Render a row of the page tree adding relevant icons depending on record depth and record type
	 *
	 */
	initComponent: function() {
		var origRenderer = this.renderer || this.defaultRenderer,
			origScope = this.scope || window;

		this.renderer = function(value, metaData, record, rowIdx, colIdx, store, view) {
			var buf   = [],
				format = Ext.String.format,
				depth = record.getDepth(),
				treePrefix = Ext.baseCSSPrefix + 'tree-',
				elbowPrefix = treePrefix + 'elbow-',
				expanderCls = treePrefix + 'expander',
				imgText = '<img src="{1}" class="{0}" />',
				checkboxText = '<input type="button" role="checkbox" class="{0}" {1} />',
				formattedValue = origRenderer.apply(origScope, arguments),
				checked = null,
				href = record.getNodeData('href'),
				target = record.getNodeData('hrefTarget'),
				cls = record.getNodeData('cls'),
				readableRootline = record.getNodeData('readableRootline');
	
			while (record) {
				if (!record.isRoot() || (record.isRoot() && view.rootVisible)) {
					if (record.getDepth() === depth) {
							// Add TYPO3 sprite icon code
						buf.unshift(record.getNodeData('spriteIconCode'));
							// Check if nodeData['checked'] is boolean
						checked = record.getNodeData('checked');
						if (Ext.isBoolean(checked)) {
							buf.unshift(format(
								checkboxText,
								(treePrefix + 'checkbox') + (checked ? ' ' + treePrefix + 'checkbox-checked' : ''),
								checked ? 'aria-checked="true"' : ''
							));
							if (checked) {
								metaData.tdCls += (' ' + treePrefix + 'checked');
							}
						}
							// Remove +/arrow from the TYPO3 root
						if (record.getDepth() === 1) {
							buf.unshift(format(imgText, (elbowPrefix + 'empty'), Ext.BLANK_IMAGE_URL));
						} else if (record.isLast()) {
							if (record.isExpandable()) {
								buf.unshift(format(imgText, (elbowPrefix + 'end-plus ' + expanderCls), Ext.BLANK_IMAGE_URL));
							} else {
								buf.unshift(format(imgText, (elbowPrefix + 'end'), Ext.BLANK_IMAGE_URL));
							}
						} else {
							if (record.isExpandable()) {
								buf.unshift(format(imgText, (elbowPrefix + 'plus ' + expanderCls), Ext.BLANK_IMAGE_URL));
							} else {
								buf.unshift(format(imgText, (treePrefix + 'elbow'), Ext.BLANK_IMAGE_URL));
							}
						}
					} else {
							// Remove elbow from the TYPO3 root
						if (record.isLast() || record.getDepth() === 1) {
							buf.unshift(format(imgText, (elbowPrefix + 'empty'), Ext.BLANK_IMAGE_URL));
						} else if (record.getDepth() !== 1) {
							buf.unshift(format(imgText, (elbowPrefix + 'line'), Ext.BLANK_IMAGE_URL));
						}                      
					}
				}
				record = record.parentNode;
			} 
			if (href) {
				buf.push('<a href="', href, '" target="', target, '">', formattedValue, '</a>');
			} else {
				buf.push(formattedValue);
			}
			if (cls) {
				metaData.tdCls += ' ' + cls;
			}
				// Show the readable rootline above the user mounts
			if (readableRootline !== '') {
				buf.unshift('<div class="x-tree-node-readableRootline">' + readableRootline + '</div>');
			}
			return buf.join('');
		};
		this.callParent(arguments);
	},

	defaultRenderer: function (value) {
		return value;
	},
	/**
	 * Create editing field with correct margin left
	 *
	 */
        getEditor: function (record, defaultField) {
		var depth = parseInt(record.getDepth()),
			marginLeft = 51;
		if (depth > 2) {
			marginLeft += (depth-2)*16;
		}
		var field = this.field;
        	if (!field && this.editor) {
			field = this.editor;
			delete this.editor;
		}

		if (!field && defaultField) {
			field = defaultField;
		}

		if (field) {
 			if (Ext.isString(field)) {
				field = { xtype: field };
			}
			if (field.isFormField) {
				field.setFieldStyle({
					marginLeft: marginLeft + 'px'
				});
			}
			if (Ext.isObject(field) && !field.isFormField) {
				field = Ext.ComponentManager.create(field, 'textfield');
				this.field = field;
			}
			Ext.apply(field, {
				name: this.dataIndex
			});
			return field;
		}
	}
});
