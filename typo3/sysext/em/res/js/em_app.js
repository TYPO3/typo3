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
		key: 'moduleData.tools_em.States'
	}));

	if (Ext.isObject(TYPO3.settings.EM.States)) {
		Ext.state.Manager.getProvider().initState(TYPO3.settings.EM.States);
	}
	Ext.QuickTips.init();


		// fire app
	var EM = new TYPO3.EM.App.init();
	TYPO3.Flashmessage.display(TYPO3.Severity.information, TYPO3.lang.header, TYPO3.lang.emLoaded, 2);

	/*Ext.state.Manager.getProvider().logState();
	var val = Ext.state.Manager.getProvider().get('mainTab', '');
	console.log(val);*/
});

TYPO3.EM.App = {

	init : function() {
		var appPanel = new Ext.TabPanel( {
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
			items : [
				TYPO3.EM.LocalListTab,
				TYPO3.EM.RepositoryListTab,
				TYPO3.EM.LanguageTab,
				TYPO3.EM.SettingsTab,
				TYPO3.EM.UserTab
			],
			plugins: [new Ext.ux.plugins.FitToParent()]
		});
	}
};
