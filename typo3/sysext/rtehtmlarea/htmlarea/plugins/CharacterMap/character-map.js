/***************************************************************
*  Copyright notice
*
*  (c) 2004 Bernhard Pfeifer novocaine@gmx.net
*  (c) 2004 systemconcept.de. Authored by Holger Hees based on HTMLArea XTD 1.5 (http://mosforge.net/projects/htmlarea3xtd/).
*  (c) 2005-2010 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 *
 * TYPO3 SVN ID: $Id$
 */
HTMLArea.CharacterMap = HTMLArea.Plugin.extend({
	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function(editor) {
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '2.0',
			developer	: 'Holger Hees, Bernhard Pfeifer, Stanislas Rolland',
			developerUrl	: 'http://www.sjbr.ca/',
			copyrightOwner	: 'Holger Hees, Bernhard Pfeifer, Stanislas Rolland',
			sponsor		: 'System Concept GmbH, Bernhard Pfeifer, SJBR',
			sponsorUrl	: 'http://www.sjbr.ca/',
			license		: 'GPL'
		};
		this.registerPluginInformation(pluginInformation);
		/*
		 * Registering the button
		 */
		var buttonId = 'InsertCharacter';
		var buttonConfiguration = {
			id		: buttonId,
			tooltip		: this.localize(buttonId + '-Tooltip'),
			action		: 'onButtonPress',
			dialog		: true,
			iconCls		: 'htmlarea-action-character-insert-from-map'
		};
		this.registerButton(buttonConfiguration);
		return true;
	 },
	/*
	 * Character maps
	 */
	maps: {
		general: [
			['&nbsp;', '&amp;nbsp;'],
			['&Agrave;', '&amp;Agrave;'],
			['&agrave;', '&amp;agrave;'],
			['&Aacute;', '&amp;Aacute;'],
			['&aacute;', '&amp;aacute;'],
			['&Acirc;', '&amp;Acirc;'],
			['&acirc;', '&amp;acirc;'],
			['&Atilde;', '&amp;Atilde;'],
			['&atilde;', '&amp;atilde;'],
			['&Auml;', '&amp;Auml;'],
			['&auml;', '&amp;auml;'],
			['&Aring;', '&amp;Aring;'],
			['&aring;', '&amp;aring;'],
			['&AElig;', '&amp;AElig;'],
			['&aelig;', '&amp;aelig;'],
			['&ordf;', '&amp;ordf;'],
			['&Ccedil;', '&amp;Ccedil;'],
			['&ccedil;', '&amp;ccedil;'],
			['&ETH;', '&amp;ETH;'],
			['&eth;', '&amp;eth;'],
			['&Egrave;', '&amp;Egrave;'],
			['&egrave;', '&amp;egrave;'],
			['&Eacute;', '&amp;Eacute;'],
			['&eacute;', '&amp;eacute;'],
			['&Ecirc;', '&amp;Ecirc;'],
			['&ecirc;', '&amp;ecirc;'],
			['&Euml;', '&amp;Euml;'],
			['&euml;', '&amp;euml;'],
			['&Igrave;', '&amp;Igrave;'],
			['&igrave;', '&amp;igrave;'],
			['&Iacute;', '&amp;Iacute;'],
			['&iacute;', '&amp;iacute;'],
			['&Icirc;', '&amp;Icirc;'],
			['&icirc;', '&amp;icirc;'],
			['&Iuml;', '&amp;Iuml;'],
			['&iuml;', '&amp;iuml;'],
			['&Ntilde;', '&amp;Ntilde;'],
			['&ntilde;', '&amp;ntilde;'],
			['&Ograve;', '&amp;Ograve;'],
			['&ograve;', '&amp;ograve;'],
			['&Oacute;', '&amp;Oacute;'],
			['&oacute;', '&amp;oacute;'],
			['&Ocirc;', '&amp;Ocirc;'],
			['&ocirc;', '&amp;ocirc;'],
			['&Otilde;', '&amp;Otilde;'],
			['&otilde;', '&amp;otilde;'],
			['&Ouml;', '&amp;Ouml;'],
			['&ouml;', '&amp;ouml;'],
			['&Oslash;', '&amp;Oslash;'],
			['&oslash;', '&amp;oslash;'],
			['&OElig;', '&amp;OElig;'],
			['&oelig;', '&amp;oelig;'],
			['&ordm;', '&amp;ordm;'],
			['&Scaron;', '&amp;Scaron;'],
			['&scaron;', '&amp;scaron;'],
			['&szlig;', '&amp;szlig;'],
			['&THORN;', '&amp;THORN;'],
			['&thorn;', '&amp;thorn;'],
			['&Ugrave;', '&amp;Ugrave;'],
			['&ugrave;', '&amp;ugrave;'],
			['&Uacute;', '&amp;Uacute;'],
			['&uacute;', '&amp;uacute;'],
			['&Ucirc;', '&amp;Ucirc;'],
			['&ucirc;', '&amp;ucirc;'],
			['&Uuml;', '&amp;Uuml;'],
			['&uuml;', '&amp;uuml;'],
			['&Yacute;', '&amp;Yacute;'],
			['&yacute;', '&amp;yacute;'],
			['&Yuml;', '&amp;Yuml;'],
			['&yuml;', '&amp;yuml;'],
			['&acute;', '&amp;acute;'],
			['&circ;', '&amp;circ;'],
			['&tilde;', '&amp;tilde;'],
			['&uml;', '&amp;uml;'],
			['&cedil;', '&amp;cedil;'],
			['&shy;', '&amp;shy;'],
			['&ndash;', '&amp;ndash;'],
			['&mdash;', '&amp;mdash;'],
			['&lsquo;', '&amp;lsquo;'],
			['&rsquo;', '&amp;rsquo;'],
			['&sbquo;', '&amp;sbquo;'],
			['&ldquo;', '&amp;ldquo;'],
			['&rdquo;', '&amp;rdquo;'],
			['&bdquo;', '&amp;bdquo;'],
			['&lsaquo;', '&amp;lsaquo;'],
			['&rsaquo;', '&amp;rsaquo;'],
			['&laquo;', '&amp;laquo;'],
			['&raquo;', '&amp;raquo;'],
			['&quot;', '&amp;quot;'],
			['&hellip;', '&amp;hellip;'],
			['&iquest;', '&amp;iquest;'],
			['&iexcl;', '&amp;iexcl;'],
			['&bull;', '&amp;bull;'],
			['&dagger;', '&amp;dagger;'],
			['&Dagger;', '&amp;Dagger;'],
			['&brvbar;', '&amp;brvbar;'],
			['&para;', '&amp;para;'],
			['&sect;', '&amp;sect;'],
			['&loz;', '&amp;loz;'],
			['&#064;', '&amp;#064;'],
			['&copy;', '&amp;copy;'],
			['&reg;', '&amp;reg;'],
			['&trade;', '&amp;trade;'],
			['&curren;', '&amp;curren;'],
			['&cent;', '&amp;cent;'],
			['&euro;', '&amp;euro;'],
			['&pound;', '&amp;pound;'],
			['&yen;', '&amp;yen;'],
			['&emsp;', '&amp;emsp;'],
			['&ensp;', '&amp;ensp;'],
			['&thinsp;', '&amp;thinsp;'],
			['&zwj;', '&amp;zwj;'],
			['&zwnj;', '&amp;zwnj;']
		],
		mathematical: [
			['&minus;', '&amp;minus;'],
			['&plusmn;', '&amp;plusmn;'],
			['&times;', '&amp;times;'],
			['&divide;', '&amp;divide;'],
			['&radic;', '&amp;radic;'],
			['&sdot;', '&amp;sdot;'],
			['&otimes;', '&amp;otimes;'],
			['&lowast;', '&amp;lowast;'],
			['&ge;', '&amp;ge;'],
			['&le;', '&amp;le;'],
			['&ne;', '&amp;ne;'],
			['&asymp;', '&amp;asymp;'],
			['&sim;', '&amp;sim;'],
			['&prop;', '&amp;prop;'],
			['&deg;', '&amp;deg;'],
			['&prime;', '&amp;prime;'],
			['&Prime;', '&amp;Prime;'],
			['&micro;', '&amp;micro;'],
			['&ang;', '&amp;ang;'],
			['&perp;', '&amp;perp;'],
			['&permil;', '&amp;permil;'],
			['&frasl;', '&amp;frasl;'],
			['&frac14;', '&amp;frac14;'],
			['&frac12;', '&amp;frac12;'],
			['&frac34;', '&amp;frac34;'],
			['&sup1;', '&amp;sup1;'],
			['&sup2;', '&amp;sup2;'],
			['&sup3;', '&amp;sup3;'],
			['&not;', '&amp;not;'],
			['&and;', '&amp;and;'],
			['&or;', '&amp;or;'],
			['&there4;', '&amp;there4;'],
			['&cong;', '&amp;cong;'],
			['&isin;', '&amp;isin;'],
			['&ni;', '&amp;ni;'],
			['&notin;', '&amp;notin;'],
			['&sub;', '&amp;sub;'],
			['&sube;', '&amp;sube;'],
			['&nsub;', '&amp;nsub;'],
			['&sup;', '&amp;sup;'],
			['&supe;', '&amp;supe;'],
			['&cap;', '&amp;cap;'],
			['&cup;', '&amp;cup;'],
			['&oplus;', '&amp;oplus;'],
			['&nabla;', '&amp;nabla;'],
			['&empty;', '&amp;empty;'],
			['&equiv;', '&amp;equiv;'],
			['&sum;', '&amp;sum;'],
			['&prod;', '&amp;prod;'],
			['&weierp;', '&amp;weierp;'],
			['&exist;', '&amp;exist;'],
			['&forall;', '&amp;forall;'],
			['&infin;', '&amp;infin;'],
			['&alefsym;', '&amp;alefsym;'],
			['&real;', '&amp;real;'],
			['&image;', '&amp;image;'],
			['&fnof;', '&amp;fnof;'],
			['&int;', '&amp;int;'],
			['&part;', '&amp;part;'],
			['&Alpha;', '&amp;Alpha;'],
			['&alpha;', '&amp;alpha;'],
			['&Beta;', '&amp;Beta;'],
			['&beta;', '&amp;beta;'],
			['&Gamma;', '&amp;Gamma;'],
			['&gamma;', '&amp;gamma;'],
			['&Delta;', '&amp;Delta;'],
			['&delta;', '&amp;delta;'],
			['&Epsilon;', '&amp;Epsilon;'],
			['&epsilon;', '&amp;epsilon;'],
			['&Zeta;', '&amp;Zeta;'],
			['&zeta;', '&amp;zeta;'],
			['&Eta;', '&amp;Eta;'],
			['&eta;', '&amp;eta;'],
			['&Theta;', '&amp;Theta;'],
			['&theta;', '&amp;theta;'],
			['&thetasym;', '&amp;thetasym;'],
			['&Iota;', '&amp;Iota;'],
			['&iota;', '&amp;iota;'],
			['&Kappa;', '&amp;Kappa;'],
			['&kappa;', '&amp;kappa;'],
			['&Lambda;', '&amp;Lambda;'],
			['&lambda;', '&amp;lambda;'],
			['&Mu;', '&amp;Mu;'],
			['&mu;', '&amp;mu;'],
			['&Nu;', '&amp;Nu;'],
			['&nu;', '&amp;nu;'],
			['&Xi;', '&amp;Xi;'],
			['&xi;', '&amp;xi;'],
			['&Omicron;', '&amp;Omicron;'],
			['&omicron;', '&amp;omicron;'],
			['&Pi;', '&amp;Pi;'],
			['&pi;', '&amp;pi;'],
			['&piv;', '&amp;piv;'],
			['&Rho;', '&amp;Rho;'],
			['&rho;', '&amp;rho;'],
			['&Sigma;', '&amp;Sigma;'],
			['&sigma;', '&amp;sigma;'],
			['&sigmaf;', '&amp;sigmaf;'],
			['&Tau;', '&amp;Tau;'],
			['&tau;', '&amp;tau;'],
			['&Upsilon;', '&amp;Upsilon;'],
			['&upsih;', '&amp;upsih;'],
			['&upsilon;', '&amp;upsilon;'],
			['&Phi;', '&amp;Phi;'],
			['&phi;', '&amp;phi;'],
			['&Chi;', '&amp;Chi;'],
			['&chi;', '&amp;chi;'],
			['&Psi;', '&amp;Psi;'],
			['&psi;', '&amp;psi;'],
			['&Omega;', '&amp;Omega;'],
			['&omega;', '&amp;omega;']
		],
		graphical: [
			['&crarr;', '&amp;crarr;'],
			['&uarr;', '&amp;uarr;'],
			['&darr;', '&amp;darr;'],
			['&larr;', '&amp;larr;'],
			['&rarr;', '&amp;rarr;'],
			['&harr;', '&amp;harr;'],
			['&uArr;', '&amp;uArr;'],
			['&dArr;', '&amp;dArr;'],
			['&lArr;', '&amp;lArr;'],
			['&rArr;', '&amp;rArr;'],
			['&hArr;', '&amp;hArr;'],
			['&nbsp;', '&amp;nbsp;'],
			['&nbsp;', '&amp;nbsp;'],
			['&nbsp;', '&amp;nbsp;'],
			['&nbsp;', '&amp;nbsp;'],
			['&clubs;', '&amp;clubs;'],
			['&diams;', '&amp;diams;'],
			['&hearts;', '&amp;hearts;'],
			['&spades;', '&amp;spades;']
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
		this.openDialogue(
			buttonId,
			'Insert special character',
			this.getWindowDimensions({width:434, height:360}, buttonId),
			this.buildTabItems()
		);
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
				// As of ExtJS 3.1, JS error with IE when the window is resizable
			resizable: !Ext.isIE,
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
					'<tpl for="."><a href="#" title="{1}" class="character" hidefocus="on" ext:qtitle"{1}">{0}</a></tpl>'
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
	 * Insert the selected entity
	 *
	 * @param	object		event: the Ext event
	 * @param	HTMLelement	target: the html element target
	 *
	 * @return	void
	 */
	insertCharacter: function (event, target) {
		event.stopEvent();
		this.editor.focus();
		this.restoreSelection();
		var entity = Ext.get(target).dom.innerHTML;
		if (Ext.isIE) {
			this.editor.insertHTML(entity);
			this.saveSelection();
		} else {
				// Firefox and WebKit convert '&nbsp;' to '&amp;nbsp;'
			this.editor.insertNodeAtSelection(this.editor.document.createTextNode(((Ext.isGecko || Ext.isWebKit) && entity == '&nbsp;') ? '\xA0' : entity));
		}
		return false;
	},
	/*
	 * Reset focus on the the current selection, if at all possible
	 *
	 */
	resetFocus: function () {
		this.editor.focus();
		this.restoreSelection();
	}
});
