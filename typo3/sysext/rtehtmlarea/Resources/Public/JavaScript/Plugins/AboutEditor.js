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
 * About Plugin for TYPO3 htmlArea RTE
 */
define([
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'jquery',
	'TYPO3/CMS/Backend/Modal',
	'TYPO3/CMS/Backend/Severity'
], function (Plugin, Util, $, Modal, Severity) {

	var AboutEditor = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(AboutEditor, Plugin);
	Util.apply(AboutEditor.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function(editor) {

			/**
			 * Registering plugin "About" information
			 */
			var pluginInformation = {
				version		: '2.1',
				developer	: 'Stanislas Rolland',
				developerUrl	: 'http://www.sjbr.ca/',
				copyrightOwner	: 'Stanislas Rolland',
				sponsor		: 'SJBR',
				sponsorUrl	: 'http://www.sjbr.ca/',
				license		: 'GPL'
			};
			this.registerPluginInformation(pluginInformation);
			/**
			 * Registering the button
			 */
			var buttonId = 'About';
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize(buttonId.toLowerCase()),
				action		: 'onButtonPress',
				textMode	: true,
				dialog		: true,
				iconCls		: 'htmlarea-action-editor-show-about'
			};
			this.registerButton(buttonConfiguration);
			return true;
		 },
		/**
		 * Supported browsers
		 */
		browsers: [
			 'Firefox 1.5+',
			 'Google Chrome 1.0+',
			 'Internet Explorer 9.0+',
			 'Opera 9.62+',
			 'Safari 3.0.4+',
			 'SeaMonkey 1.0+'
		],
		/**
		 * This function gets called when the button was pressed.
		 *
		 * @param {Object} editor The editor instance
		 * @param {String} id The button id or the key
		 *
		 * @return {Boolean} false if action is completed
		 */
		onButtonPress: function (editor, id) {
			// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
			this.openDialogue(
				buttonId,
				'About HTMLArea',
				this.buildTabItems(),
				function () {
					Modal.currentModal.trigger('modal-dismiss');
				}
			);
			return false;
		},
		/**
		 * Open the dialogue window
		 *
		 * @param {String} buttonId The button id
		 * @param {String} title The window title
		 * @param {Object} tabItems The pre-rendered window content
		 * @param {Function} handler Handler when the OK button is clicked
		 */
		openDialogue: function (buttonId, title, tabItems, handler) {
			this.dialog = Modal.show(title, tabItems, Severity.info, [
				this.buildButtonConfig('Close', handler, true, Severity.info)
			]);

			this.dialog.on('modal-dismiss', $.proxy(this.onClose, this));
		},
		/**
		 * Build the configuration of the the tab items
		 *
		 * @return {Array} The configuration array of tab items
		 */
		buildTabItems: function () {
			var $finalMarkup,
				$tabs = $('<ul />', {'class': 'nav nav-tabs', role: 'tablist'}),
				$tabContent;

			$tabs.append(
				$('<li />', {'class': 'active'}).append(
					$('<a />', {href: '#about', 'aria-controls': 'about', role: 'tab', 'data-toggle': 'tab'}).text(this.localize('About'))
				),
				$('<li />').append(
					$('<a />', {href: '#plugins', 'aria-controls': 'plugins', role: 'tab', 'data-toggle': 'tab'}).text(this.localize('Plugins'))
				)
			);

			// About tab
			var $aboutTab = $('<div />', {'class': 'panel panel-default'}).append(
				$('<div />', {'class': 'panel-heading'}).text('htmlArea RTE ' +  RTEarea[0].version),
				$('<div />', {'class': 'panel-body'}).append(
					$('<p />').text(this.localize('free_editor')),
					$('<p />').text(this.localize('Browser support') + ': ' + this.browsers.join(', ')),
					$('<p />').text(this.localize('product_documentation')).append(
						$('<a />', {href: 'https://docs.typo3.org/typo3cms/extensions/rtehtmlarea/', target: '_blank'}).text('typo3.org')
					),
					$('<p />', {'class': 'text-center'}).html(
						'<br />'
						+ '&copy; 2002-2004 <a href="http://interactivetools.com" target="_blank">interactivetools.com, inc.</a><br />'
						+ '&copy; 2003-2004 <a href="http://dynarch.com" target="_blank">dynarch.com LLC.</a><br />'
						+ '&copy; 2004-2016 <a href="http://www.sjbr.ca" target="_blank">Stanislas Rolland</a><br />'
						+ this.localize('All rights reserved.')
					)
				)
			);

			// Plugins tab
			var $pluginTab = $('<div />', {'class': 'panel panel-default'}).append(
					$('<div />', {'class': 'panel-heading'}).text(this.localize('Plugins'))
				),
				$pluginTable = $('<table />', {'class': 'table'}).append(
					$('<thead />').append(
						$('<tr />').append(
							$('<th />').text(this.localize('Name')),
							$('<th />').text(this.localize('Developer')),
							$('<th />').text(this.localize('Sponsored by'))
						)
					)
				),
				$pluginRows = $('<tbody />');


			for (var i = 0, plugins = this.getPluginsInfo(); i < plugins.length; ++i) {
				$pluginRows.append(
					$('<tr />').append(
						$('<td />').text(plugins[i][0]),
						$('<td />').html(plugins[i][1]),
						$('<td />').html(plugins[i][2])
					)
				);
			}
			$pluginTab.append($pluginTable.append($pluginRows));

			$tabContent = $('<div />', {'class': 'tab-content'}).append(
				$('<div />', {'class': 'tab-pane active', id: 'about'}).append($aboutTab),
				$('<div />', {'class': 'tab-pane', id: 'plugins'}).append($pluginTab)
			);
			$finalMarkup = $('<div />').append($tabs, $tabContent);

			return $finalMarkup
		},
		/**
		 * Format an array of information on each configured plugin
		 *
		 * @return {Array} Array of data objects
		 */
		getPluginsInfo: function () {
			var pluginsInfo = [];
			for (var pluginId in this.editor.plugins) {
				if (this.editor.plugins.hasOwnProperty(pluginId)) {
					var plugin = this.editor.plugins[pluginId];
					pluginsInfo.push([
						plugin.name + ' ' + plugin.version,
						'<a href="' + plugin.developerUrl + '" target="_blank">' + plugin.developer + '</a>',
						'<a href="' + plugin.sponsorUrl + '" target="_blank">' + plugin.sponsor + '</a>'
					]);
				}
			}
			return pluginsInfo;
		}
	});

	return AboutEditor;

});
