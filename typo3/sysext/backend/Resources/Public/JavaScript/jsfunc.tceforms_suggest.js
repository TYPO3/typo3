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
	fieldType: '',

	/**
	 * Wrapper for script.aculo.us' Autocompleter functionality: Assigns a new autocompletion object to
	 * the input field of a given Suggest selector.
	 *
	 * @param  string  objectId  The ID of the object to assign the completer to
	 * @param  string  table     The table of the record which is currently edited
	 * @param  string  field     The field which is currently edited
	 * @param  integer uid       The uid of the record which is currently edited
	 * @param  integer pid       The pid of the record which is currently edited
	 * @param  integer minimumCharacters the minimum characters that is need to trigger the initial search
	 * @param  string  fieldType The TCA type of the field (e.g. group, select, ...)
	 * @param string newRecordRow JSON encoded new content element. Only set when new record is inside flexform
	 */
	initialize: function(objectId, table, field, uid, pid, minimumCharacters, fieldType, newRecordRow) {
		var PATH_typo3 = top.TS.PATH_typo3 || window.opener.top.TS.PATH_typo3;
		this.objectId = objectId;
		this.suggestField = objectId + 'Suggest';
		this.suggestResultList = objectId + 'SuggestChoices';
		this.fieldType = fieldType;

		new Ajax.Autocompleter(this.suggestField, this.suggestResultList, PATH_typo3 + TYPO3.settings.ajaxUrls['t3lib_TCEforms_suggest::searchRecord'], {
				paramName: 'value',
				minChars: (minimumCharacters ? minimumCharacters : this.minimumCharacters),
				updateElement: this.addElementToList.bind(this),
				parameters: 'table=' + table + '&field=' + field + '&uid=' + uid + '&pid=' + pid + '&newRecordRow=' + newRecordRow,
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
			var ins_uid_string = (this.fieldType == 'select') ? ins_uid : (ins_table + '_' + ins_uid);
			var rec_table = arr[2];
			var rec_uid = arr[3];
			var rec_field = arr[4];

			var formEl = this.objectId;

			var suggestLabelNode = Element.select(item, '.suggest-label')[0];
			var label = suggestLabelNode.textContent ? suggestLabelNode.textContent : suggestLabelNode.innerText;
			var suggestLabelTitleNode = Element.select(suggestLabelNode, '[title]')[0];
			var title = suggestLabelTitleNode ? suggestLabelTitleNode.readAttribute('title') : '';

			setFormValueFromBrowseWin(formEl, ins_uid_string, label, title);
			TBE_EDITOR.fieldChanged(rec_table, rec_uid, rec_field, formEl);

			$(this.suggestField).value = this.defaultValue;
		}
	}
});
