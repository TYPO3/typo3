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
 * Default Link Plugin for TYPO3 htmlArea RTE
 */
define([
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'jquery',
	'TYPO3/CMS/Backend/Modal',
	'TYPO3/CMS/Backend/Notification',
	'TYPO3/CMS/Backend/Severity'
], function (Plugin, UserAgent, Util, Dom, $, Modal, Notification, Severity) {

	var DefaultLink = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(DefaultLink, Plugin);
	Util.apply(DefaultLink.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {
			this.baseURL = this.editorConfiguration.baseURL;
			this.pageTSConfiguration = this.editorConfiguration.buttons.link;
			this.showTarget = !(this.pageTSConfiguration && this.pageTSConfiguration.targetSelector && this.pageTSConfiguration.targetSelector.disabled);

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
			var buttonList = this.buttonList, buttonId;
			for (var i = 0; i < buttonList.length; ++i) {
				var button = buttonList[i];
				buttonId = button[0];
				var buttonConfiguration = {
					id		: buttonId,
					tooltip		: this.localize(buttonId.toLowerCase()),
					iconCls		: 'htmlarea-action-' + button[4],
					action		: 'onButtonPress',
					hotKey		: (this.pageTSConfiguration ? this.pageTSConfiguration.hotKey : null),
					context		: button[1],
					selection	: button[2],
					dialog		: button[3]
				};
				this.registerButton(buttonConfiguration);
			}
			return true;
		},

		/**
		 * The list of buttons added by this plugin
		 */
		buttonList: [
			['CreateLink', 'a,img', false, true, 'link-edit'],
			['UnLink', 'a', false, false, 'unlink']
		],

		/**
		 * This function gets called when the editor is generated
		 */
		onGenerate: function () {
		},
		/**
		 * This function gets called when the button was pressed.
		 *
		 * @param {Object} editor: the editor instance
		 * @param {String} id: the button id or the key
		 * @param {Object} target: the target element of the contextmenu event, when invoked from the context menu
		 *
		 * @return {Boolean} false if action is completed
		 */
		onButtonPress: function (editor, id, target) {
			// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
			this.link = this.editor.getSelection().getFirstAncestorOfType('a');
			switch (buttonId) {
				case 'UnLink':
					this.unLink();
					break;
				case 'CreateLink':
					if (!this.link) {
						if (this.editor.getSelection().isEmpty()) {
							TYPO3.Dialog.InformationDialog({
								title: this.getButton(buttonId).tooltip,
								msg: this.localize('Select some text')
							});
							break;
						}
						this.parameters = {
							href:	'http://',
							title:	'',
							target:	''
						};
					} else {
						this.parameters = {
							href:	this.link.getAttribute('href'),
							title:	this.link.title,
							target:	this.link.target
						};
					}
						// Open dialogue window
					this.openDialogue(
						buttonId,
						this.getButton(buttonId).tooltip
					);
					break;
			}
			return false;
		},
		/**
		 * Open the dialogue window
		 *
		 * @param {String} buttonId: the button id
		 * @param {String} title: the window title
		 */
		openDialogue: function (buttonId, title) {
			this.dialog = Modal.show(this.localize(title), this.generateDialogContent(), Severity.notice, [
				this.buildButtonConfig('Next', $.proxy(this.onOK, this), true),
				this.buildButtonConfig('Done', $.proxy(this.onCancel, this), false, Severity.notice)
			]);
			this.dialog.on('modal-dismiss', $.proxy(this.onClose, this));

			this.onAfterRender();
		},
		/**
		 * Generates the content for the dialog window
		 *
		 * @returns {Object}
		 */
		generateDialogContent: function() {
			var $fieldset = $('<fieldset />', {'class': 'form-section'});
			$fieldset.append(
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).text(this.localize('URL:')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$('<input />', {name: 'href', 'class': 'form-control', value: this.parameters.href})
					)
				),
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).text(this.localize('Title (tooltip):')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$('<input />', {name: 'title', 'class': 'form-control', value: this.parameters.title})
					)
				),
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).text(this.localize('Title (tooltip):')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$('<select />', {name: 'target', 'class': 'form-control'}).append(
							$('<option />', {value: ''}).text(this.localize('target_none')),
							$('<option />', {value: '_blank'}).text(this.localize('target_blank')),
							$('<option />', {value: '_self'}).text(this.localize('target_self')),
							$('<option />', {value: '_top'}).text(this.localize('target_top')),
							$('<option />', {value: '_other'}).text(this.localize('target_other'))
						).on('change', $.proxy(this.onTargetSelect, this))
					)
				).toggle(this.showTarget),
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).text(this.localize(frame)),
					$('<div />', {'class': 'col-sm-10'}).append(
						$('<input />', {name: 'frame', 'class': 'form-control', value: this.parameters.frame})
					)
				).hide()
			);

			return $fieldset;
		},
		/**
		 * Handler invoked after the dialogue window is rendered
		 * If the current target is not in the available options, show frame field
		 */
		onAfterRender: function (dialog) {
			var targetCombo = dialog.find('[name="target"]');
			if (targetCombo.is(':visible') && this.parameters.target) {
				var frameField = dialog.find('[name="frame"]');
				var frameExists = targetCombo.find('option[value="' + this.parameters.target + '"]').length > 0;
				if (!frameExists) {
					// The target is a specific frame name
					targetCombo.val('_other');
					frameField.val(this.parameters.target);
					frameField.closest('.form-group').show();
				} else {
					targetCombo.val(this.parameters.target);
				}
			}
		},
		/**
		 * Handler invoked when a target is selected
		 *
		 * @param {Event} e
		 */
		onTargetSelect: function (e) {
			var $me = $(e.currentTarget),
				frameField = $me.closest('fieldset').find('[name="frame"]');
			if ($me.val() === '_other') {
				frameField.closest('.form-group').show();
				frameField.focus();
			} else if (frameField.is(':visible')) {
				frameField.closest('.form-group').hide();
			}
		},
		/**
		 * Handler invoked when the OK button is clicked
		 */
		onOK: function () {
			var hrefField = this.dialog.find('[name="href"]');
			var href = $.trim(hrefField.val());
			if (href && href !== 'http://') {
				var title = this.dialog.find('[name="title"]').val();
				var target = this.dialog.find('[name="target"]').val();
				if (target === '_other') {
					target = $.trim(this.dialog.find('[name="frame"]').val());
				}
				this.createLink(href, title, target);
				this.close();
			} else {
				Notification.warning(
					this.localize('URL'),
					this.localize('link_url_required')
				);
				hrefField.focus();
			}
			return false;
		},
		/**
		 * Create the link
		 *
		 * @param {String} href: the value of href attribute
		 * @param {String} title: the value of title attribute
		 * @param {String} target: the value of target attribute
		 */
		createLink: function (href, title, target) {
			var a = this.link;
			if (!a) {
				this.restoreSelection();
				this.editor.getSelection().execCommand('CreateLink', false, href);
				a = this.editor.getSelection().getParentElement();
				if (!/^a$/i.test(a.nodeName)) {
					var range = this.editor.getSelection().createRange();
					if (range.startContainer.nodeType !== Dom.TEXT_NODE) {
						a = range.startContainer.childNodes[range.startOffset];
					} else {
						a = range.startContainer.nextSibling;
					}
					this.editor.getSelection().selectNode(a);
				}
				var el = this.editor.getSelection().getFirstAncestorOfType('a');
				if (el != null) {
					a = el;
				}
			} else {
				a.href = href;
			}
			if (a && /^a$/i.test(a.nodeName)) {
				a.title = title;
				a.target = target;
				if (UserAgent.isOpera) {
					this.editor.getSelection().selectNodeContents(a, false);
				} else {
					this.editor.getSelection().selectNodeContents(a);
				}
			}
		},
		/**
		 * Unlink the selection
		 */
		unLink: function () {
			this.restoreSelection();
			if (this.link) {
				this.editor.getSelection().selectNode(this.link);
			}
			this.editor.getSelection().execCommand('Unlink', false, '');
		},
		/**
		 * This function gets called when the toolbar is updated
		 */
		onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
			button.setInactive(true);
			if (mode === 'wysiwyg' && this.editor.isEditable()) {
				switch (button.itemId) {
					case 'CreateLink':
						button.setDisabled(selectionEmpty && !button.isInContext(mode, selectionEmpty, ancestors));
						if (!button.disabled) {
							var el = this.editor.getSelection().getFirstAncestorOfType('a');
							if (el != null) {
								node = el;
							}
							if (node != null && /^a$/i.test(node.nodeName)) {
								button.setTooltip(this.localize('Modify link'));
								button.setInactive(false);
							} else {
								button.setTooltip(this.localize('Insert link'));
							}
						}
						break;
					case 'UnLink':
						var link = false;
						// Let's see if a link was double-clicked in Firefox
						if (UserAgent.isGecko && !selectionEmpty) {
							var range = this.editor.getSelection().createRange();
							if (range.startContainer.nodeType === Dom.ELEMENT_NODE
								&& range.startContainer == range.endContainer
								&& (range.endOffset - range.startOffset == 1)
							) {
								var node = range.startContainer.childNodes[range.startOffset];
								if (node && /^a$/i.test(node.nodeName) && node.textContent == range.toString()) {
									link = true;
								}
							}
						}
						button.setDisabled(!link && !button.isInContext(mode, selectionEmpty, ancestors));
						break;
				}
			}
		}
	});

	return DefaultLink;
});