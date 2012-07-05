/***************************************************************
*  Copyright notice
*
*  (c) 2004 Bernhard Pfeifer novocaine@gmx.net
*  (c) 2004 systemconcept.de. Authored by Holger Hees based on HTMLArea XTD 1.5 (http://mosforge.net/projects/htmlarea3xtd/).
*  (c) 2005-2012 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
*  This script is a modified version of a script published under the htmlArea License.
*  A copy of the htmlArea License may be found in the textfile HTMLAREA_LICENSE.txt.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * Character Map Plugin for TYPO3 htmlArea RTE
 */
HTMLArea.CharacterMap = Ext.extend(HTMLArea.Plugin, {
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function(editor) {
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '4.0',
			developer	: 'Holger Hees, Bernhard Pfeifer, Stanislas Rolland',
			developerUrl	: 'http://www.sjbr.ca/',
			copyrightOwner	: 'Holger Hees, Bernhard Pfeifer, Stanislas Rolland',
			sponsor		: 'System Concept GmbH, Bernhard Pfeifer, SJBR, BLE',
			sponsorUrl	: 'http://www.sjbr.ca/',
			license		: 'GPL'
		};
		this.registerPluginInformation(pluginInformation);
		/*
		 * Registering the buttons
		 */
		for (var i = 0, n = this.buttons.length; i < n; ++i) {
			var button = this.buttons[i];
			buttonId = button[0];
			var buttonConfiguration = {
				id: buttonId,
				tooltip: this.localize(buttonId + '-Tooltip'),
				action: 'onButtonPress',
				context: button[1],
				dialog: false,
				iconCls: 'htmlarea-action-' + button[2]
			};
			this.registerButton(buttonConfiguration);
		}
		/*
		 * Localizing the maps
		 */
		Ext.iterate(this.maps, function (key, map, maps) {
			for (var i = map.length; --i >= 0;) {
				maps[key][i].push(this.localize(map[i][1]));
			}
		}, this);
		return true;
	 },
	/*
	 * The list of buttons added by this plugin
	 */
	buttons: [
		['InsertCharacter', null, 'character-insert-from-map'],
		['InsertSoftHyphen', null, 'soft-hyphen-insert']
	],
	/*
	 * Character maps
	 */
	maps: {
		general: [
			['&nbsp;', 'nbsp'],
			['&Agrave;', 'Agrave'],
			['&agrave;', 'agrave'],
			['&Aacute;', 'Aacute'],
			['&aacute;', 'aacute'],
			['&Acirc;', 'Acirc'],
			['&acirc;', 'acirc'],
			['&Atilde;', 'Atilde'],
			['&atilde;', 'atilde'],
			['&Auml;', 'Auml'],
			['&auml;', 'auml'],
			['&Aring;', 'Aring'],
			['&aring;', 'aring'],
			['&AElig;', 'AElig'],
			['&aelig;', 'aelig'],
			['&ordf;', 'ordf'],
			['&Ccedil;', 'Ccedil'],
			['&ccedil;', 'ccedil'],
			['&ETH;', 'ETH'],
			['&eth;', 'eth'],
			['&Egrave;', 'Egrave'],
			['&egrave;', 'egrave'],
			['&Eacute;', 'Eacute'],
			['&eacute;', 'eacute'],
			['&Ecirc;', 'Ecirc'],
			['&ecirc;', 'ecirc'],
			['&Euml;', 'Euml'],
			['&euml;', 'euml'],
			['&Igrave;', 'Igrave'],
			['&igrave;', 'igrave'],
			['&Iacute;', 'Iacute'],
			['&iacute;', 'iacute'],
			['&Icirc;', 'Icirc'],
			['&icirc;', 'icirc'],
			['&Iuml;', 'Iuml'],
			['&iuml;', 'iuml'],
			['&Ntilde;', 'Ntilde'],
			['&ntilde;', 'ntilde'],
			['&Ograve;', 'Ograve'],
			['&ograve;', 'ograve'],
			['&Oacute;', 'Oacute'],
			['&oacute;', 'oacute'],
			['&Ocirc;', 'Ocirc'],
			['&ocirc;', 'ocirc'],
			['&Otilde;', 'Otilde'],
			['&otilde;', 'otilde'],
			['&Ouml;', 'Ouml'],
			['&ouml;', 'ouml'],
			['&Oslash;', 'Oslash'],
			['&oslash;', 'oslash'],
			['&OElig;', 'OElig'],
			['&oelig;', 'oelig'],
			['&ordm;', 'ordm'],
			['&Scaron;', 'Scaron'],
			['&scaron;', 'scaron'],
			['&szlig;', 'szlig'],
			['&THORN;', 'THORN'],
			['&thorn;', 'thorn'],
			['&Ugrave;', 'Ugrave'],
			['&ugrave;', 'ugrave'],
			['&Uacute;', 'Uacute'],
			['&uacute;', 'uacute'],
			['&Ucirc;', 'Ucirc'],
			['&ucirc;', 'ucirc'],
			['&Uuml;', 'Uuml'],
			['&uuml;', 'uuml'],
			['&Yacute;', 'Yacute'],
			['&yacute;', 'yacute'],
			['&Yuml;', 'Yuml'],
			['&yuml;', 'yuml'],
			['&acute;', 'acute'],
			['&circ;', 'circ'],
			['&tilde;', 'tilde'],
			['&uml;', 'uml'],
			['&cedil;', 'cedil'],
			['&shy;', 'shy'],
			['&ndash;', 'ndash'],
			['&mdash;', 'mdash'],
			['&lsquo;', 'lsquo'],
			['&rsquo;', 'rsquo'],
			['&sbquo;', 'sbquo'],
			['&ldquo;', 'ldquo'],
			['&rdquo;', 'rdquo'],
			['&bdquo;', 'bdquo'],
			['&lsaquo;', 'lsaquo'],
			['&rsaquo;', 'rsaquo'],
			['&laquo;', 'laquo'],
			['&raquo;', 'raquo'],
			['&quot;', 'quot'],
			['&hellip;', 'hellip'],
			['&iquest;', 'iquest'],
			['&iexcl;', 'iexcl'],
			['&bull;', 'bull'],
			['&dagger;', 'dagger'],
			['&Dagger;', 'Dagger'],
			['&brvbar;', 'brvbar'],
			['&para;', 'para'],
			['&sect;', 'sect'],
			['&loz;', 'loz'],
			['&#064;', '#064'],
			['&copy;', 'copy'],
			['&reg;', 'reg'],
			['&trade;', 'trade'],
			['&curren;', 'curren'],
			['&cent;', 'cent'],
			['&euro;', 'euro'],
			['&pound;', 'pound'],
			['&yen;', 'yen'],
			['&emsp;', 'emsp'],
			['&ensp;', 'ensp'],
			['&thinsp;', 'thinsp'],
			['&zwj;', 'zwj'],
			['&zwnj;', 'zwnj']
		],
		mathematical: [
			['&minus;', 'minus'],
			['&plusmn;', 'plusmn'],
			['&times;', 'times'],
			['&divide;', 'divide'],
			['&radic;', 'radic'],
			['&sdot;', 'sdot'],
			['&otimes;', 'otimes'],
			['&lowast;', 'lowast'],
			['&ge;', 'ge'],
			['&le;', 'le'],
			['&ne;', 'ne'],
			['&asymp;', 'asymp'],
			['&sim;', 'sim'],
			['&prop;', 'prop'],
			['&deg;', 'deg'],
			['&prime;', 'prime'],
			['&Prime;', 'Prime'],
			['&micro;', 'micro'],
			['&ang;', 'ang'],
			['&perp;', 'perp'],
			['&permil;', 'permil'],
			['&frasl;', 'frasl'],
			['&frac14;', 'frac14'],
			['&frac12;', 'frac12'],
			['&frac34;', 'frac34'],
			['&sup1;', 'sup1'],
			['&sup2;', 'sup2'],
			['&sup3;', 'sup3'],
			['&not;', 'not'],
			['&and;', 'and'],
			['&or;', 'or'],
			['&there4;', 'there4'],
			['&cong;', 'cong'],
			['&isin;', 'isin'],
			['&ni;', 'ni'],
			['&notin;', 'notin'],
			['&sub;', 'sub'],
			['&sube;', 'sube'],
			['&nsub;', 'nsub'],
			['&sup;', 'sup'],
			['&supe;', 'supe'],
			['&cap;', 'cap'],
			['&cup;', 'cup'],
			['&oplus;', 'oplus'],
			['&nabla;', 'nabla'],
			['&empty;', 'empty'],
			['&equiv;', 'equiv'],
			['&sum;', 'sum'],
			['&prod;', 'prod'],
			['&weierp;', 'weierp'],
			['&exist;', 'exist'],
			['&forall;', 'forall'],
			['&infin;', 'infin'],
			['&alefsym;', 'alefsym'],
			['&real;', 'real'],
			['&image;', 'image'],
			['&fnof;', 'fnof'],
			['&int;', 'int'],
			['&part;', 'part'],
			['&Alpha;', 'Alpha'],
			['&alpha;', 'alpha'],
			['&Beta;', 'Beta'],
			['&beta;', 'beta'],
			['&Gamma;', 'Gamma'],
			['&gamma;', 'gamma'],
			['&Delta;', 'Delta'],
			['&delta;', 'delta'],
			['&Epsilon;', 'Epsilon'],
			['&epsilon;', 'epsilon'],
			['&Zeta;', 'Zeta'],
			['&zeta;', 'zeta'],
			['&Eta;', 'Eta'],
			['&eta;', 'eta'],
			['&Theta;', 'Theta'],
			['&theta;', 'theta'],
			['&thetasym;', 'thetasym'],
			['&Iota;', 'Iota'],
			['&iota;', 'iota'],
			['&Kappa;', 'Kappa'],
			['&kappa;', 'kappa'],
			['&Lambda;', 'Lambda'],
			['&lambda;', 'lambda'],
			['&Mu;', 'Mu'],
			['&mu;', 'mu'],
			['&Nu;', 'Nu'],
			['&nu;', 'nu'],
			['&Xi;', 'Xi'],
			['&xi;', 'xi'],
			['&Omicron;', 'Omicron'],
			['&omicron;', 'omicron'],
			['&Pi;', 'Pi'],
			['&pi;', 'pi'],
			['&piv;', 'piv'],
			['&Rho;', 'Rho'],
			['&rho;', 'rho'],
			['&Sigma;', 'Sigma'],
			['&sigma;', 'sigma'],
			['&sigmaf;', 'sigmaf'],
			['&Tau;', 'Tau'],
			['&tau;', 'tau'],
			['&Upsilon;', 'Upsilon'],
			['&upsih;', 'upsih'],
			['&upsilon;', 'upsilon'],
			['&Phi;', 'Phi'],
			['&phi;', 'phi'],
			['&Chi;', 'Chi'],
			['&chi;', 'chi'],
			['&Psi;', 'Psi'],
			['&psi;', 'psi'],
			['&Omega;', 'Omega'],
			['&omega;', 'omega']
		],
		graphical: [
			['&crarr;', 'crarr'],
			['&uarr;', 'uarr'],
			['&darr;', 'darr'],
			['&larr;', 'larr'],
			['&rarr;', 'rarr'],
			['&harr;', 'harr'],
			['&uArr;', 'uArr'],
			['&dArr;', 'dArr'],
			['&lArr;', 'lArr'],
			['&rArr;', 'rArr'],
			['&hArr;', 'hArr'],
			['&nbsp;', 'nbsp'],
			['&nbsp;', 'nbsp'],
			['&nbsp;', 'nbsp'],
			['&nbsp;', 'nbsp'],
			['&clubs;', 'clubs'],
			['&diams;', 'diams'],
			['&hearts;', 'hearts'],
			['&spades;', 'spades']
		]
	},
	/*
	 * This function gets called when the button was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress: function (editor, id) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		switch (buttonId) {
			case 'InsertCharacter':
				this.openDialogue(
					buttonId,
					'Insert special character',
					this.getWindowDimensions(
						{
							width: 434,
							height: 360
						},
						buttonId
					),
					this.buildTabItems()
				);
				break;
			case 'InsertSoftHyphen':
				this.insertEntity('\xAD');
				break;
		}
		return false;
	},
	/*
	 * Open the dialogue window
	 *
	 * @param	string		buttonId: the button id
	 * @param	string		title: the window title
	 * @param	integer		dimensions: the opening width of the window
	 * @param	object		tabItems: the configuration of the tabbed panel
	 * @param	function	handler: handler when the OK button if clicked
	 *
	 * @return	void
	 */
	openDialogue: function (buttonId, title, dimensions, tabItems, handler) {
		this.dialog = new Ext.Window({
			title: this.localize(title),
			cls: 'htmlarea-window',
			border: false,
			width: dimensions.width,
			height: 'auto',
			iconCls: this.getButton(buttonId).iconCls,
			listeners: {
				close: {
					fn: this.onClose,
					scope: this
				}
			},
			items: {
				xtype: 'tabpanel',
				activeTab: 0,
				listeners: {
					activate: {
						fn: this.resetFocus,
						scope: this
					},
					tabchange: {
						fn: this.syncHeight,
						scope: this
					}
				},
				items: tabItems
			},
			buttons: [
				this.buildButtonConfig('Cancel', this.onCancel)
			]
		});
		this.show();
	},
	/*
	 * Build the configuration of the the tab items
	 *
	 * @return	array	the configuration array of tab items
	 */
	buildTabItems: function () {
		var tabItems = [];
		Ext.iterate(this.maps, function (id, map) {
			tabItems.push({
				xtype: 'box',
				cls: 'character-map',
				title: this.localize(id),
				itemId: id,
				tpl: new Ext.XTemplate(
					'<tpl for="."><a href="#" class="character" hidefocus="on" ext:qtitle="<span>&</span>{1};" ext:qtip="{2}">{0}</a></tpl>'
				),
				listeners: {
					render: {
						fn: this.renderMap,
						scope: this
					}
				}
			});
		}, this);
		return tabItems;
	},
	/*
	 * Render an array of characters
	 *
	 * @param	object		component: the box containing the characters
	 *
	 * @return	void
	 */
	renderMap: function (component) {
		component.tpl.overwrite(component.el, this.maps[component.itemId]);
		component.mon(component.el, 'click', this.insertCharacter, this, {delegate: 'a'});
	},
	/*
	 * Handle the click on an item of the map
	 *
	 * @param	object		event: the Ext event
	 * @param	HTMLelement	target: the html element target
	 *
	 * @return	boolean
	 */
	insertCharacter: function (event, target) {
		event.stopEvent();
		this.restoreSelection();
		var entity = Ext.get(target).dom.innerHTML;
		this.insertEntity(entity);
		this.saveSelection();
		return false;
	},
	/*
	 * Insert the selected entity
	 *
	 * @param	string		entity: the entity to insert at the current selection
	 *
	 * @return	void
	 */
	insertEntity: function (entity) {
		if (HTMLArea.isIEBeforeIE9) {
			this.editor.getSelection().insertHtml(entity);
		} else {
				// Firefox, WebKit and IE convert '&nbsp;' to '&amp;nbsp;'
			var node = this.editor.document.createTextNode(((Ext.isGecko || Ext.isWebKit || Ext.isIE) && entity == '&nbsp;') ? '\xA0' : entity);
			this.editor.getSelection().insertNode(node);
			this.editor.getSelection().selectNode(node, false);
		}
	},
	/*
	 * Reset focus on the the current selection, if at all possible
	 *
	 */
	resetFocus: function () {
		this.restoreSelection();
	}
});
