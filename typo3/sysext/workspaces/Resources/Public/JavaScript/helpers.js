TYPO3.Workspaces.Helpers = {
	/**
	 * Gets an form values object like {'element-1':on, 'element-2':on} and returns
	 * the checked results in an array like ['1', '2'].
	 *
	 * @param object values
	 * @param string elementPrefix
	 * @return array
	 */
	getElementIdsFromFormValues: function(values, elementPrefix) {
		var results = [];
		var pattern = new RegExp('^' + elementPrefix + '-' + '(.+)$');

		Ext.iterate(values, function(key, value) {
			if (value == 'on' && pattern.test(key)) {
				results.push(RegExp.$1);
			}
		});

		return results;
	},

	getSendToStageWindow: function(configuration) {
		return top.TYPO3.Windows.showWindow({
			id: 'sendToStageWindow',
			title: configuration.title,
			items: [
				{
					xtype: 'form',
					id: 'sendToStageForm',
					width: '100%',
					bodyStyle: 'padding: 5px 5px 3px 5px; border-width: 0; margin-bottom: 7px;',
					items: configuration.items
				}
			],
			buttons: [
				{
					text: 'OK',
					handler: configuration.executeHandler
				},
				{
					text: 'Cancel',
					handler: function(event) {
						top.TYPO3.Windows.close('sendToStageWindow');
					}
				}
			]
		});
	},

	getPropertyOfElementsArray: function(elements, property) {
		var result = [];

		Ext.each(elements, function(element) {
			result.push(element[property]);
		});

		return result;
	},

	getElementsArrayOfSelection: function(selection) {
		var elements = [];

		Ext.each(selection, function(item) {
			var element = {
				table: item.data.table,
				t3ver_oid: item.data.t3ver_oid,
				uid: item.data.uid
			}
			elements.push(element);
		});

		return elements;
	}
};