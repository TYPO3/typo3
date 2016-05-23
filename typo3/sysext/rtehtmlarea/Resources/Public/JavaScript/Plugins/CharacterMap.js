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
 * Character Map Plugin for TYPO3 htmlArea RTE
 */
define([
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'jquery',
	'TYPO3/CMS/Backend/Modal',
	'TYPO3/CMS/Backend/Severity'
], function (Plugin, UserAgent, Event, Util, $, Modal, Severity) {

	var CharacterMap = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(CharacterMap, Plugin);
	Util.apply(CharacterMap.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function(editor) {
			/**
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

			/**
			 * Registering the buttons
			 */
			for (var i = 0, n = this.buttons.length; i < n; ++i) {
				var button = this.buttons[i];
				var buttonId = button[0];
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

			/**
			 * Localizing the maps
			 */
			for (var key in this.maps) {
				if (this.maps.hasOwnProperty(key)) {
					var map = this.maps[key];
					for (var i = map.length; --i >= 0;) {
						this.maps[key][i].push(this.localize(map[i][1]));
					}
				}
			}
			return true;
		},

		/**
		 * The list of buttons added by this plugin
		 */
		buttons: [
			['InsertCharacter', null, 'character-insert-from-map'],
			['InsertSoftHyphen', null, 'soft-hyphen-insert']
		],

		/**
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
				['&clubs;', 'clubs'],
				['&diams;', 'diams'],
				['&hearts;', 'hearts'],
				['&spades;', 'spades']
			]
		},
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
			switch (buttonId) {
				case 'InsertCharacter':
					this.openDialogue(
						buttonId,
						'Insert special character',
						this.buildTabItems(),
						function () {
							Modal.currentModal.trigger('modal-dismiss');
						}
					);
					break;
				case 'InsertSoftHyphen':
					this.insertEntity('\xAD');
					break;
			}
			return false;
		},
		/**
		 * Open the dialogue window
		 *
		 * @param {String} buttonId The button id
		 * @param {String} title The window title
		 * @param {Object} tabItems The configuration of the tabbed panel
		 * @param {Function} handler Handler when the OK button is clicked
		 */
		openDialogue: function (buttonId, title, tabItems, handler) {
			this.dialog = Modal.show(title, tabItems, Severity.notice, [
				this.buildButtonConfig('Close', handler, true, Severity.notice)
			]);

			this.resetFocus();

			this.dialog.on('modal-dismiss', $.proxy(this.onClose, this));
		},
		/**
		 * Build the configuration of the the tab items
		 *
		 * @return {Object} The configuration array of tab items
		 */
		buildTabItems: function () {
			var self = this,
				$finalMarkup,
				$tabs = $('<ul />', {'class': 'nav nav-tabs', role: 'tablist'}),
				$tabContent = $('<div />', {'class': 'tab-content'});

			for (var id in this.maps) {
				if (this.maps.hasOwnProperty(id)) {
					var isFirst = Object.keys(this.maps).indexOf(id) === 0;
					$tabs.append(
						$('<li />', {'class': (isFirst ? 'active' : '')}).append(
							$('<a />', {
								href: '#' + id,
								'aria-controls': id,
								role: 'tab',
								'data-toggle': 'tab'
							}).text(this.localize(id))
						)
					);

					var $characters = $('<div />', {'class': 'form-section htmlarea-character-map', id: id});
					for (var charDefinition in this.maps[id]) {
						if (this.maps[id].hasOwnProperty(charDefinition)) {
							var char = this.maps[id][charDefinition];
							$characters.append(
								$('<a />', {
									'class': 'character btn btn-default',
									hidefocus: 'on',
									title: char[2] + ' (' + char[1] + ')'
								}).html(char[0])
							);
						}
					}
					$characters.on('click', 'a.character', function (e) {
						return self.insertCharacter(e);
					});

					$tabContent.append(
						$('<div />', {'class': 'tab-pane ' + (isFirst ? 'active' : ''), id: id}).append($characters)
					);
				}
			}

			$finalMarkup = $('<div />').append($tabs, $tabContent);

			return $finalMarkup;
		},

		/**
		 * Handle the click on an item of the map
		 *
		 * @param {Event} event The jQuery event
		 * @return {Boolean}
		 */
		insertCharacter: function (event) {
			Event.stopEvent(event);
			this.restoreSelection();
			var entity = event.target.innerHTML;
			this.insertEntity(entity);
			this.saveSelection();
			return false;
		},

		/**
		 * Insert the selected entity
		 *
		 * @param {String} entity The entity to insert at the current selection
		 */
		insertEntity: function (entity) {
			// Firefox, WebKit and IE convert '&nbsp;' to '&amp;nbsp;'
			var node = this.editor.document.createTextNode(((UserAgent.isGecko || UserAgent.isWebKit || UserAgent.isIE) && entity == '&nbsp;') ? '\xA0' : entity);
			this.editor.getSelection().insertNode(node);
			this.editor.getSelection().selectNode(node, false);
		},

		/**
		 * Reset focus on the the current selection, if at all possible
		 *
		 */
		resetFocus: function () {
			this.restoreSelection();
		}
	});

	return CharacterMap;

});
