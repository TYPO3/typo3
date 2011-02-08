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
Ext.ns('TYPO3.EM', 'TYPO3.EM.ExtDirect');

Ext.onReady(function() {
		//save states in BE_USER->uc
	Ext.state.Manager.setProvider(new TYPO3.state.ExtDirectProvider({
		key: 'moduleData.tools_em.States',
		autoRead: false
	}));

	if (Ext.isObject(TYPO3.settings.EM.States)) {
		Ext.state.Manager.getProvider().initState(TYPO3.settings.EM.States);
	}

	Ext.QuickTips.init();
	TYPO3.EM.ImportWindow = null;

		// fire app
	var EM = new TYPO3.EM.App.init();
});

TYPO3.EM.AdditionalApplicationItems = [];

TYPO3.EM.App = {
	refreshLocalList: false,
	loadingIndicor: '<div class="loading-indicator">' + TYPO3.lang.action_loading + '</div>',

	categoryLabels : [
			TYPO3.lang.category_BE,
			TYPO3.lang.category_BE_modules,
			TYPO3.lang.category_FE,
			TYPO3.lang.category_FE_plugins,
			TYPO3.lang.category_miscellanous,
			TYPO3.lang.category_services,
			TYPO3.lang.category_templates,
			'',
			TYPO3.lang.category_documentation,
			TYPO3.lang.category_examples
	],

	init : function() {

		TYPO3.settings.EM.selectedRepository = TYPO3.settings.EM.selectedRepository || 1;
		var items = [
			TYPO3.EM.LocalListTab,
			TYPO3.EM.RepositoryListTab,
			TYPO3.EM.LanguageTab,
			TYPO3.EM.SettingsTab
		];
		if (TYPO3.settings.EM.displayMyExtensions == 1) {
			items.push(TYPO3.EM.UserTab)
		}

		if (TYPO3.EM.AdditionalApplicationItems.length) {
			items.push(TYPO3.EM.AdditionalApplicationItems);
		}

		this.appPanel = new Ext.TabPanel( {
			renderTo : 'em-app',
			id: 'em-main',
			layoutOnTabChange: true,
			plain: true,
			activeTab: 0,
			stateful: true,
			stateId: 'mainTab',
			stateEvents:['tabchange'],
			autoScroll: true,
			defaults: {
				layout: 'fit'
			},
			getState: function() {
				return {
					activeTab: this.items.indexOf(this.getActiveTab())
				};
			},
			items : items,
			plugins: [new Ext.ux.plugins.FitToParent()]
		});
	},

	getCategoryLabel: function(index) {
		return this.categoryLabels[parseInt(index, 10)];
	}
};
