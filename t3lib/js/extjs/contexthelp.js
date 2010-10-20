/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Steffen Kamper <steffen@typo3.org>
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

Ext.ns('TYPO3', 'TYPO3.CSH.ExtDirect');

/**
 * Class to show tooltips for links that have the css t3-help-link
 * need the tags data-table and data-field (HTML5)
 */
TYPO3.ContextHelp = function() {

	/**
	 * Tooltip
	 * @type {Ext.ToolTip}
	 */
	var tip;

	/**
	 * Cache for CSH
	 * @type {Ext.util.MixedCollection}
	 */
	var cshHelp = new Ext.util.MixedCollection(true);

	return {
		/**
		 * Constructor
		 */
		init: function() {
			tip = new Ext.ToolTip({
				title: 'TYPO3 CSH',
				html: '',
				anchorToTarget: true,
				width: 160,
				renderTo: Ext.getBody(),
				cls: 'typo3-csh-tooltip',
				dismissDelay: 3000, // auto hide after 3 seconds
				showsDelay: 2000, // show after 2 seconds
				listeners: {
					'render': function(){
						this.body.on('click', function(e){
							e.stopEvent();
							top.TYPO3.ContextHelpWindow.open(this.cshLink);
						}, this);
					}
				}

			});
			Ext.select('div').on('mouseover', TYPO3.ContextHelp.showToolTipHelp, TYPO3.ContextHelp, {delegate: 'a.t3-help-link'});
			Ext.select('div').on('click', TYPO3.ContextHelp.openHelpWindow, TYPO3.ContextHelp, {delegate: 'a.t3-help-link'});
		},

		/**
		 * Shows the tooltip, triggered from mouseover event handler
		 *
		 * @param {Event} event
		 * @param {Node} link
		 */
		showToolTipHelp: function(event, link) {
			event.stopEvent();
			var table = link.getAttribute('data-table');
			var field = link.getAttribute('data-field');
			var key = table + '.' + field;
			var element = Ext.fly(link);
			var response;

			tip.target = element;

			if (response = cshHelp.key(key)) {
				tip.body.dom.innerHTML = response.description;
				tip.cshLink = response.id;
				tip.setTitle(response.title);
				tip.showBy(element, 'tl-tr');
			} else {
				TYPO3.CSH.ExtDirect.getContextHelp(table, field, function(response, options) {
					cshHelp[table + '.' + field] = response;
					tip.body.dom.innerHTML = response.description;
					tip.cshLink = response.id;
					tip.setTitle(response.title);
					cshHelp.add(response);
					tip.showAt(event.getXY());
				}, this);
			}
		},

		/**
		 * Opens the help window, triggered from click event handler
		 *
		 * @param {Event} event
		 * @param {Node} link
		 */
		openHelpWindow: function(event, link) {
			var id = link.getAttribute('data-table') + '.' + link.getAttribute('data-field');
			event.stopEvent();
			top.TYPO3.ContextHelpWindow.open(id);
		}
	}

}();

/**
 * Calls the init on Ext.onReady
 */
Ext.onReady(TYPO3.ContextHelp.init, TYPO3.ContextHelp);
