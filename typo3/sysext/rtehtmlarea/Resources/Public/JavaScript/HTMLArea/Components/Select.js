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

/**
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/Components/Select
 * A select field used in dialog windows
 */
define([
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Toolbar/Select'
], function (Util, Dom, ToolbarSelect) {

	/**
	 * Select constructor
	 *
	 * @param {Object} config
	 * @constructor
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/Components/Select
	 */
	var Select = function (config) {
		this.constructor.super.call(this, config);

		this.selectElement = document.createElement('select');
	};
	Util.inherit(Select, ToolbarSelect);
	Util.apply(Select.prototype, {
		/**
		 * Render the select item (called by the toolbar)
		 *
		 * @param {Object} container The container of the select (the toolbar object)
		 */
		render: function (container) {
			this.el = document.createElement('div');
			Dom.addClass(this.el, 'form-group');
			this.selectElement = document.createElement('select');
			Dom.addClass(this.selectElement, 'form-control');
			if (this.id) {
				this.selectElement.setAttribute('id', this.id);
			}
			if (typeof this.cls === 'string') {
				Dom.addClass(this.selectElement, this.cls);
			}
			if (typeof this.tooltip === 'string') {
				this.selectElement.setAttribute('title', this.tooltip);
			}
			if (this.options) {
				for (var i = 0, n = this.options.length; i < n; i++) {
					this.addOption(this.options[i][0], this.options[i][1], this.options[i][1], this.options[i][2]);
				}
			}
			var fieldWrapper = document.createElement('div');
			Dom.addClass(fieldWrapper, 'col-sm-10');
			this.selectElement = fieldWrapper.appendChild(this.selectElement);
			fieldWrapper = this.el.appendChild(fieldWrapper);
			this.el = container.appendChild(this.el);
			if (this.fieldLabel) {
				var label = document.createElement('label');
				label.innerHTML = this.fieldLabel;
				Dom.addClass(label, 'col-sm-2');
				label.setAttribute('for', this.selectElement.id);
				this.el.insertBefore(label, fieldWrapper);
			} else if (typeof this.tooltip === 'string') {
				this.selectElement.setAttribute('aria-label', this.tooltip);
			}
		}
	});

	return Select;
});
