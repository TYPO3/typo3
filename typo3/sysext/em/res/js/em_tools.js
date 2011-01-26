/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Steffen Kamper <info@sk-typo3.de>
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
 * ExtJS for the extension manager.
 *
 *
 * @author Steffen Kamper <info@sk-typo3.de>
 * @package TYPO3
 * @subpackage extension manager
 * @version $Id: $
 */
Ext.ns('TYPO3.EM');

TYPO3.EM.Tools = function() {
	return {
		displayLocalExtension: function(extKey, reload) {
			localStore = Ext.StoreMgr.get('localstore');
				// select local extension list
			Ext.getCmp('em-main').setActiveTab(0);
			if (reload === true) {
				TYPO3.EM.Filters.clearFilters();
				localStore.showAction = extKey;
				var search = Ext.getCmp('localSearchField');
				search.setValue(extKey);
				search.refreshTrigger();
				localStore.load();
			}
		},

		uploadExtension: function() {
			if (Ext.isObject(TYPO3.EM.ExtensionUploadWindowInstance)) {
				TYPO3.EM.ExtensionUploadWindowInstance.show();
			} else {
				TYPO3.EM.ExtensionUploadWindowInstance = new TYPO3.EM.ExtensionUploadWindow().show();
			}
		},

		renderExtensionTitle: function(record) {
			var description = record.data.description;
			var value = record.data.title;
			if (value == '') {
				value = '[no title]';
			}
			if (record.data.reviewstate < 0) {
				description += '<br><br><strong>' + TYPO3.lang.insecureExtension + '</strong>';
			}
			return record.data.icon + ' ' + value + ' (v' + record.data.version + ')';
		},

		closeImportWindow: function() {
	   		TYPO3.EM.ImportWindow.close();
		},

		refreshMenu: function(record, installAction) {
			if (installAction == 'import') {
				Ext.StoreMgr.get('repositoryliststore').getById(record.extkey).set('exists', 1);
				TYPO3.EM.Tools.displayLocalExtension(record.extkey, true);
			}
			if (top.TYPO3ModuleMenu && installAction == 'install') {
				top.TYPO3ModuleMenu.refreshMenu();
			}
		}
	}
}();