/**
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
 * Class for JS handling of selectbox filter in TCEforms.
 *
 * @author  Marc Bastian Heinrichs <mbh@mbh-software.de>
 */

if (!TCEForms) {
	var TCEForms = {};
}

TCEForms.SelectBoxFilter = Class.create({
	selectBox: '',
	selectBoxOriginal: '',
	selectBoxOriginalOptionsLength: 0,
	filterTextfield: false,
	filterDropDown: false,
	delayObject: '',

	/**
	 * Assigns a new filter object to the available items select box object.
	 *
	 * @param selectBoxId  The ID of the object to assign the filter to
	 */
	initialize: function(selectBoxId) {

		this.selectBox = $(selectBoxId);
		this.selectBoxOriginal = this.selectBox.cloneNode(true);
		this.selectBoxOriginalOptionsLength = this.selectBoxOriginal.options.length;

		if ($(selectBoxId + '_filtertextfield') != undefined) {
			this.filterTextfield = $(selectBoxId + '_filtertextfield');
		}
		if ($(selectBoxId + '_filterdropdown') != undefined) {
			this.filterDropDown = $(selectBoxId + '_filterdropdown');
		}

		// setting
		if (this.filterTextfield) {
			this.filterTextfield.observe('keyup', function(event) {
				this.delayObject = this.updateSelectOptions.bindAsEventListener(this).delay(0.5);
			}.bindAsEventListener(this));

			this.filterTextfield.observe('keydown', function(event) {
			if (this.delayObject != undefined)
				window.clearTimeout(this.delayId);
			}.bindAsEventListener(this));
		}

		if (this.filterDropDown) {
			this.filterDropDown.observe('change', function(event) {
				this.updateSelectOptions();
			}.bindAsEventListener(this));
		}
	},

	/**
	 * Updates the available items select box based the filter textfield or filter drop-down
	 */
	updateSelectOptions: function() {

		var filterTextFromTextfield = '';
		var filterTextFromDropDown = '';

		if (this.filterTextfield) {
			filterTextFromTextfield = this.filterTextfield.getValue();
		}

		if (this.filterDropDown) {
			filterTextFromDropDown = this.filterDropDown.getValue();
		}

		this.selectBox.innerHTML = '';

		if (filterTextFromTextfield.length > 0 || filterTextFromDropDown.length > 0) {
			var matchStringTextfield = new RegExp(filterTextFromTextfield, 'i');
			var matchStringDropDown = new RegExp(filterTextFromDropDown, 'i');
			for (var i = 0; i < this.selectBoxOriginalOptionsLength; i++) {
				if (this.selectBoxOriginal.options[i].firstChild.nodeValue.match(matchStringTextfield) != null &&
					this.selectBoxOriginal.options[i].firstChild.nodeValue.match(matchStringDropDown) != null) {
					var tempNode = this.selectBoxOriginal.options[i].cloneNode(true);
					this.selectBox.appendChild(tempNode);
				}
			}
		} else {
			// recopy original list
			for (var i = 0; i < this.selectBoxOriginalOptionsLength; i++) {
				var tempNode = this.selectBoxOriginal.options[i].cloneNode(true);
				this.selectBox.appendChild(tempNode);
			}
		}
	}
});

