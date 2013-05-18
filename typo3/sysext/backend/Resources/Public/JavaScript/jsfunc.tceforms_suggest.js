/***************************************************************
*  AJAX selectors for TCEforms
*
*  Copyright notice
*
*  (c) 2007-2011 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Class for JS handling of suggest fields in TCEforms.
 *
 * @author  Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @author  Benni Mack <benni@typo3.org>
 */
if (!TCEForms) {
	var TCEForms = {};
}

TCEForms.Suggest = Class.create({
	objectId: '',
	suggestField: '',
	suggestResultList: '',
	minimumCharacters: 2,
	defaultValue: '',

	/**
	 * Wrapper for script.aculo.us' Autocompleter functionality: Assigns a new autocompletion object to
	 * the input field of a given Suggest selector.
	 *
	 * @param  string  objectId  The ID of the object to assign the completer to
	 * @param  string  table     The table of the record which is currently edited
	 * @param  string  field     The field which is currently edited
	 * @param  integer uid       The uid of the record which is currently edited
	 * @param  integer pid       The pid of the record which is currently edited
	 * @param  integer minimumCharacters the minimum characaters that is need to trigger the initial search
	 */
	initialize: function(objectId, table, field, uid, pid, minimumCharacters) {
		var PATH_typo3 = top.TS.PATH_typo3 || window.opener.top.TS.PATH_typo3;
		this.objectId = objectId;
		this.suggestField = objectId + 'Suggest';
		this.suggestResultList = objectId + 'SuggestChoices';

		new Ajax.Autocompleter(this.suggestField, this.suggestResultList, PATH_typo3 + 'ajax.php', {
				paramName: 'value',
				minChars: (minimumCharacters ? minimumCharacters : this.minimumCharacters),
				updateElement: this.addElementToList.bind(this),
				parameters: 'ajaxID=t3lib_TCEforms_suggest::searchRecord&table=' + table + '&field=' + field + '&uid=' + uid + '&pid=' + pid,
				indicator: objectId + 'SuggestIndicator'
			}
		);

		$(this.suggestField).observe('focus', this.checkDefaultValue.bind(this));
		$(this.suggestField).observe('keydown', this.checkDefaultValue.bind(this));
	},

	/**
	 * check for default value of the input field and set it to empty once somebody wants to type something in
	 */
	checkDefaultValue: function() {
		if ($(this.suggestField).value == this.defaultValue) {
			$(this.suggestField).value = '';
		}
	},

	/**
	 * Adds an element to the list of items in a group selector.
	 *
	 * @param  object  item  The item to add to the list (usually an li element)
	 * @return void
	 */
	addElementToList: function(item) {
		if (item.className.indexOf('noresults') == -1) {
			var arr = item.id.split('-');
			var ins_table = arr[0];
			var ins_uid = arr[1];
			var rec_table = arr[2];
			var rec_uid = arr[3];
			var rec_field = arr[4];

			var formEl = 'data[' + rec_table + '][' + rec_uid + '][' + rec_field + ']';
			var suggestLabelNode = Element.select(this.escapeObjectId(item.id), '.suggest-label')[0];
			var label = (suggestLabelNode.textContent ? suggestLabelNode.textContent : suggestLabelNode.innerText)
			setFormValueFromBrowseWin(formEl, ins_table + '_' + ins_uid, label);
			TBE_EDITOR.fieldChanged(rec_table, rec_uid, rec_field, formEl);

			$(this.suggestField).value = this.defaultValue;
		}
	},

	/**
	 * Escapes object identifiers of e.g. Flexform CSS IDs
	 *
	 * @param string objectId
	 * @return string
	 */
	escapeObjectId: function(objectId) {
		var escapedObjectId;
		escapedObjectId = objectId.replace(/:/g, '\\:');
		escapedObjectId = objectId.replace(/\./g, '\\.');
		escapedObjectId = objectId.replace(/\[/g, '\\[');
		escapedObjectId = objectId.replace(/\]/g, '\\]');
		return escapedObjectId;
	}
});
