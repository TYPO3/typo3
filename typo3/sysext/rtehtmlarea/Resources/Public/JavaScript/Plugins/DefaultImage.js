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
 * Image Plugin for TYPO3 htmlArea RTE
 */
define([
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'jquery',
	'TYPO3/CMS/Backend/Modal',
	'TYPO3/CMS/Backend/Notification',
	'TYPO3/CMS/Backend/Severity'
], function (Plugin, UserAgent, Util, $, Modal, Notification, Severity) {

	var DefaultImage = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(DefaultImage, Plugin);
	Util.apply(DefaultImage.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {
			this.baseURL = this.editorConfiguration.baseURL;
			this.pageTSConfiguration = this.editorConfiguration.buttons.image;
			if (this.pageTSConfiguration && this.pageTSConfiguration.properties && this.pageTSConfiguration.properties.removeItems) {
				this.removeItems = this.pageTSConfiguration.properties.removeItems.split(',');
				var layout = 0;
				var padding = 0;
				for (var i = 0, n = this.removeItems.length; i < n; ++i) {
					this.removeItems[i] = this.removeItems[i].replace(/(?:^\s+|\s+$)/g, '');
					if (/^(align|border|float)$/i.test(this.removeItems[i])) {
						++layout;
					}
					if (/^(paddingTop|paddingRight|paddingBottom|paddingLeft)$/i.test(this.removeItems[i])) {
						++padding;
					}
				}
				if (layout === 3) {
					this.removeItems.push('layout');
				}
				if (layout === 4) {
					this.removeItems.push('padding');
				}
				this.removeItems = new RegExp( '^(' + this.removeItems.join('|') + ')$', 'i');
			} else {
				this.removeItems = new RegExp( '^(none)$', 'i');
			}

			/**
			 * Registering plugin "About" information
			 */
			var pluginInformation = {
				version		: '2.3',
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
			var buttonId = 'InsertImage';
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize('insertimage'),
				action		: 'onButtonPress',
				hotKey		: (this.pageTSConfiguration ? this.pageTSConfiguration.hotKey : null),
				dialog		: true,
				iconCls		: 'htmlarea-action-image-edit'
			};
			this.registerButton(buttonConfiguration);
			return true;
		},
		/**
		 * This function gets called when the button was pressed.
		 *
		 * @param {Object} editor the editor instance
		 * @param {String} id: the button id or the key
		 *
		 * @return {Boolean} false if action is completed
		 */
		onButtonPress: function(editor, id) {
			// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
			this.image = this.editor.getSelection().getParentElement();
			if (this.image && !/^img$/i.test(this.image.nodeName)) {
				this.image = null;
			}
			if (this.image) {
				this.parameters = {
					base: 		this.baseURL,
					url: 		this.image.getAttribute('src'),
					alt:		this.image.alt,
					border:		isNaN(parseInt(this.image.style.borderWidth)) ? '' : parseInt(this.image.style.borderWidth),
					align:		this.image.style.verticalAlign ? this.image.style.verticalAlign : '',
					paddingTop:	isNaN(parseInt(this.image.style.paddingTop)) ? '' : parseInt(this.image.style.paddingTop),
					paddingRight:	isNaN(parseInt(this.image.style.paddingRight)) ? '' : parseInt(this.image.style.paddingRight),
					paddingBottom:	isNaN(parseInt(this.image.style.paddingBottom)) ? '' : parseInt(this.image.style.paddingBottom),
					paddingLeft:	isNaN(parseInt(this.image.style.paddingLeft)) ? '' : parseInt(this.image.style.paddingLeft),
					cssFloat: 	this.image.style.cssFloat
				};
			} else {
				this.parameters = {
					base: 	this.baseURL,
					url: 	'',
					alt:	'',
					border:	'',
					align:	'',
					paddingTop:	'',
					paddingRight:	'',
					paddingBottom:	'',
					paddingLeft:	'',
					cssFloat: ''
				};
			}
			// Open dialogue window
			this.openDialogue(
				buttonId,
				this.getButton(buttonId).tooltip
			);
			return false;
		},
		/**
		 * Open the dialogue window
		 *
		 * @param {String} buttonId: the button id
		 * @param {String} title: the window title
		 */
		openDialogue: function (buttonId, title) {
			this.dialog = Modal.show(this.localize(title), this.buildTabItems(), Severity.notice, [
				this.buildButtonConfig('Next', $.proxy(this.onOK, this), true),
				this.buildButtonConfig('Done', $.proxy(this.onCancel, this), false, Severity.notice)
			]);
			this.dialog.on('modal-dismiss', $.proxy(this.onClose, this));
		},

		/**
		 * Build the configuration of the the tab items
		 *
		 * @return {Array} the configuration array of tab items
		 */
		buildTabItems: function () {
			var $finalMarkup,
				$tabs = $('<ul />', {'class': 'nav nav-tabs', role: 'tablist'}),
				$tabContent = $('<div />', {'class': 'tab-content'});

			$tabs.append(
				$('<li />', {'class': 'active'}).append(
					$('<a />', {href: '#general', 'aria-controls': 'general', role: 'tab', 'data-toggle': 'tab'}).text(this.localize('General'))
				)
			);

			$tabContent.append(
				$('<div />', {'class': 'tab-pane active', id: 'general'}).append(
					$('<fieldset />', {'class': 'form-section'}).append(
						$('<div />', {'class': 'form-group'}).append(
							$('<label />', {'class': 'col-sm-2'}).text(this.localize('Image URL:')),
							$('<div />', {'class': 'col-sm-10'}).append(
								$('<input />', {name: 'url', 'class': 'form-control', value: this.parameters.url})
							)
						),
						$('<div />', {'class': 'form-group'}).append(
							$('<label />', {'class': 'col-sm-2'}).text(this.localize('Alternate text:')),
							$('<div />', {'class': 'col-sm-10'}).append(
								$('<input />', {name: 'alt', 'class': 'form-control', value: this.parameters.alt})
							)
						)
					),
					$('<fieldset />', {'class': 'form-section'}).append(
						$('<h4 />', {'class': 'form-section-headline'}).text(this.localize('Image Preview')),
						$('<div />').append(
							$('<iframe />', {name: 'ipreview', 'class': 'image-preview', src: this.parameters.url})
						),
						$('<button />', {'class': 'btn btn-block'})
							.text(this.localize('Preview'))
							.on('click', $.proxy(this.onPreviewClick, this))
					)
				)
			);

			// Layout tab
			if (!this.removeItems.test('layout')) {
				$tabs.append(
					$('<li />').append(
						$('<a />', {href: '#layout', 'aria-controls': 'layout', role: 'tab', 'data-toggle': 'tab'}).text(this.localize('Layout'))
					)
				);

				$tabContent.append(
					$('<div />', {'class': 'tab-pane active', id: 'layout'}).append(
						$('<fieldset />', {'class': 'form-section'}).append(
							$('<div />', {'class': 'form-group'}).append(
								$('<label />', {'class': 'col-sm-2'}).text(this.localize('Image alignment:')),
								$('<div />', {'class': 'col-sm-10'}).append(
									$('<select />', {name: 'align', 'class': 'form-control'}).append(
										$('<option />', {value: ''}).text(this.localize('Not set')),
										$('<option />', {value: 'bottom'}).text(this.localize('Bottom')),
										$('<option />', {value: 'middle'}).text(this.localize('Middle')),
										$('<option />', {value: 'top'}).text(this.localize('Top'))
									)
								)
							).toggle(!this.removeItems.test('align')),
							$('<div />', {'class': 'form-group'}).append(
								$('<label />', {'class': 'col-sm-2'}).text(this.localize('Border thickness:')),
								$('<div />', {'class': 'col-sm-10'}).append(
									$('<input />', {name: 'border', type: 'number', 'class': 'form-control', value: this.parameters.border})
								)
							).toggle(!this.removeItems.test('border')),
							$('<div />', {'class': 'form-group'}).append(
								$('<label />', {'class': 'col-sm-2'}).text(this.localize('Float:')),
								$('<div />', {'class': 'col-sm-10'}).append(
									$('<select />', {name: 'cssFloat', 'class': 'form-control'}).append(
										$('<option />', {value: ''}).text(this.localize('Not set')),
										$('<option />', {value: 'none'}).text(this.localize('Non-floating')),
										$('<option />', {value: 'left'}).text(this.localize('Left')),
										$('<option />', {value: 'right'}).text(this.localize('Right'))
									)
								)
							)
						)
					)
				);
			}
				// Padding tab
			if (!this.removeItems.test('padding')) {
				$tabs.append(
					$('<li />').append(
						$('<a />', {href: '#spacing', 'aria-controls': 'spacing', role: 'tab', 'data-toggle': 'tab'}).text(this.localize('Spacing and padding'))
					)
				);

				$tabContent.append(
					$('<div />', {'class': 'tab-pane active', id: 'spacing'}).append(
						$('<fieldset />', {'class': 'form-section'}).append(
							$('<div />', {'class': 'form-group'}).append(
								$('<label />', {'class': 'col-sm-2'}).text(this.localize('Top:')),
								$('<div />', {'class': 'col-sm-10'}).append(
									$('<input />', {name: 'paddingTop', 'class': 'form-control', value: this.parameters.paddingTop})
								)
							).toggle(!this.removeItems.test('paddingTop')),
							$('<div />', {'class': 'form-group'}).append(
								$('<label />', {'class': 'col-sm-2'}).text(this.localize('Right:')),
								$('<div />', {'class': 'col-sm-10'}).append(
									$('<input />', {name: 'paddingRight', 'class': 'form-control', value: this.parameters.paddingRight})
								)
							).toggle(!this.removeItems.test('paddingRight')),
							$('<div />', {'class': 'form-group'}).append(
								$('<label />', {'class': 'col-sm-2'}).text(this.localize('Bottom:')),
								$('<div />', {'class': 'col-sm-10'}).append(
									$('<input />', {name: 'paddingBottom', 'class': 'form-control', value: this.parameters.paddingBottom})
								)
							).toggle(!this.removeItems.test('paddingBottom')),
							$('<div />', {'class': 'form-group'}).append(
								$('<label />', {'class': 'col-sm-2'}).text(this.localize('Left:')),
								$('<div />', {'class': 'col-sm-10'}).append(
									$('<input />', {name: 'paddingLeft', 'class': 'form-control', value: this.parameters.paddingLeft})
								)
							).toggle(!this.removeItems.test('paddingLeft'))
						)
					)
				);
			}
			$finalMarkup = $('<div />').append($tabs, $tabContent);

			return $finalMarkup;
		},
		/**
		 * Handler invoked when the Preview button is clicked
		 */
		onPreviewClick: function () {
			var $urlField = this.dialog.find('[name="url"]');
			var url = $.trim($urlField.val());
			if (url) {
				try {
					window.ipreview.location.replace(url);
				} catch (e) {
					Notification.info(
						this.localize('Image Preview'),
						this.localize('image_url_invalid')
					);
					this.dialog.find('.nav-tabs a[href="#general"]').tab('show');
					$urlField.focus();
				}
			} else {
				Notification.info(
					this.localize('Image Preview'),
					this.localize('image_url_first')
				);
				this.dialog.find('.nav-tabs a[href="#general"]').tab('show');
				$urlField.focus();
			}

			return false;
		},
		/**
		 * Handler invoked when the OK button is clicked
		 */
		onOK: function () {
			var $urlField = this.dialog.find('[name="url"]');
			var url = $.trim($urlField.val());
			if (url) {
				var fieldNames = ['url', 'alt', 'align', 'border', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft', 'cssFloat'],
					fieldName;
				for (var i = fieldNames.length; --i >= 0;) {
					fieldName = fieldNames[i];
					var field = this.dialog.find('[name="' + fieldName + '"]');
					if (field && field.is(':visible')) {
						this.parameters[fieldName] = field.val();
					}
				}
				this.insertImage();
				this.close();
			} else {
				Notification.info(
					this.localize('image_url'),
					this.localize('image_url_required')
				);
				this.dialog.find('.nav-tabs a[href="#general"]').tab('show');
				$urlField.focus();
			}
			return false;
		},
		/**
		 * Insert the image
		 */
		insertImage: function() {
			this.restoreSelection();
			var image = this.image;
			if (!image) {
				this.editor.getSelection().createRange();
				this.editor.getSelection().execCommand('InsertImage', false, this.parameters.url);
				if (UserAgent.isWebKit) {
					this.editor.getDomNode().cleanAppleStyleSpans(this.editor.document.body);
				}
				var range = this.editor.getSelection().createRange();
				image = range.startContainer;
				image = image.lastChild;
				while (image && !/^img$/i.test(image.nodeName)) {
					image = image.previousSibling;
				}
			} else {
				image.src = this.parameters.url;
			}
			if (/^img$/i.test(image.nodeName)) {
				var value;
				for (var fieldName in this.parameters) {
					value = this.parameters[fieldName];
					switch (fieldName) {
						case 'alt':
							image.alt = value;
							break;
						case 'border':
							if (parseInt(value)) {
								image.style.borderWidth = parseInt(value) + 'px';
								image.style.borderStyle = 'solid';
							} else {
								image.style.borderWidth = '';
								image.style.borderStyle = 'none';
							}
							break;
						case 'align':
							image.style.verticalAlign = value;
							break;
						case 'paddingTop':
						case 'paddingRight':
						case 'paddingBottom':
						case 'paddingLeft':
							if (parseInt(value)) {
								image.style[fieldName] = parseInt(value) + 'px';
							} else {
								image.style[fieldName] = '';
							}
							break;
						case 'cssFloat':
							image.style.cssFloat = value;
							break;
					}
				}
			}
		},

		/**
		 * This function gets called when the toolbar is updated
		 */
		onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
			button.setInactive(true);
			if (mode === 'wysiwyg' && this.editor.isEditable() && button.itemId === 'InsertImage' && !button.disabled) {
				var image = this.editor.getSelection().getParentElement();
				if (image && !/^img$/i.test(image.nodeName)) {
					image = null;
				}
				if (image) {
					button.setTooltip(this.localize('Modify image'));
					button.setInactive(false);
				} else {
					button.setTooltip(this.localize('Insert image'));
				}
			}
		}
	});

	return DefaultImage;
});