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
		//save states in user cookie, TODO: use ucStateProvider instead
	Ext.state.Manager.setProvider(new Ext.state.CookieProvider());
	Ext.QuickTips.init();

		// fire app
	var EM = new TYPO3.EM.App.init();
	TYPO3.Flashmessage.display(TYPO3.Severity.information, TYPO3.lang.header, TYPO3.lang.emLoaded, 2);


});

TYPO3.EM.App = {

	init : function() {
		if (!TYPO3.settings.hasCredentials &&  TYPO3.settings.EM.mainTab == '4') {
			TYPO3.settings.EM.mainTab = 0;
		}
		var appPanel = new Ext.TabPanel( {
			renderTo : 'em-app',
			id: 'em-main',
			activeTab : TYPO3.settings.EM.mainTab ? TYPO3.settings.EM.mainTab : 0,
			layoutOnTabChange: true,
			plain: true,
			height: 450,
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
