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
 * Find and Replace Plugin for TYPO3 htmlArea RTE
 */
define([
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'jquery',
	'TYPO3/CMS/Backend/Modal',
	'TYPO3/CMS/Backend/Notification',
	'TYPO3/CMS/Backend/Severity'
], function (Plugin, Util, $, Modal, Notification, Severity) {

	var FindReplace = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(FindReplace, Plugin);
	Util.apply(FindReplace.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {

			/**
			 * Registering plugin "About" information
			 */
			var pluginInformation = {
				version		: '2.2',
				developer	: 'Cau Guanabara & Stanislas Rolland',
				developerUrl	: 'http://www.sjbr.ca',
				copyrightOwner	: 'Cau Guanabara & Stanislas Rolland',
				sponsor		: 'Independent production & SJBR',
				sponsorUrl	: 'http://www.sjbr.ca',
				license		: 'GPL'
			};
			this.registerPluginInformation(pluginInformation);

			/**
			 * Registering the button
			 */
			var buttonId = 'FindReplace';
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize('Find and Replace'),
				iconCls		: 'htmlarea-action-find-replace',
				action		: 'onButtonPress',
				dialog		: true
			};
			this.registerButton(buttonConfiguration);

			// Compile regular expression to clean up marks
			this.marksCleaningRE = /(<span\s+[^>]*id="?htmlarea-frmark[^>]*"?>)([^<>]*)(<\/span>)/gi;
			return true;
		},

		/**
		 * This function gets called when the 'Find & Replace' button is pressed.
		 *
		 * @param {Object} editor The editor instance
		 * @param {String} id The button id or the key
		 *
		 * @return boolean false if action is completed
		 */
		onButtonPress: function (editor, id, target) {
			// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
			// Initialize search variables
			this.buffer = null;
			this.initVariables();
			// Disable the toolbar undo/redo buttons and snapshots while this window is opened
			var plugin = this.getPluginInstance('UndoRedo');
			if (plugin) {
				plugin.stop();
				var undo = this.getButton('Undo');
				if (undo) {
					undo.setDisabled(true);
				}
				var redo = this.getButton('Redo');
				if (redo) {
					redo.setDisabled(true);
				}
			}
			// Open dialogue window
			this.openDialogue(buttonId, 'Find and Replace');
			return false;
		},

		/**
		 * Open the dialogue window
		 *
		 * @param {String} buttonId The button id
		 * @param {String} title The window title
		 */
		openDialogue: function (buttonId, title) {
			this.dialog = Modal.show(this.localize(title), this.generateDialogContent(), Severity.notice, [
				this.buildButtonConfig('Next', $.proxy(this.onNext, this), true),
				this.buildButtonConfig('Done', $.proxy(this.onCancel, this), false, Severity.notice)
			]);
			this.dialog.on('modal-dismiss', $.proxy(this.onClose, this));
		},
		/**
		 * Generates the content for the dialog window
		 *
		 * @returns {Object}
		 */
		generateDialogContent: function() {
			var $searchReplaceFields = $('<fieldset />', {'class': 'form-section'}),
				$optionFields = $('<fieldset />', {'class': 'form-section'}),
				$actions = $('<fieldset />', {'class': 'form-section'});

			$searchReplaceFields.append(
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).text(this.localize('Search for:')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$('<input />', {name: 'pattern', 'class': 'form-control'})
					)
				),
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).text(this.localize('Replace with:')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$('<input />', {name: 'replacement', 'class': 'form-control'})
					)
				)
			);
			this.initPattern($searchReplaceFields);

			$optionFields.append(
				$('<h4 />', {'class': 'form-section-headline'}).text(this.localize('Options')),
				$('<div />', {'class': 'form-group col-sm-12'}).append(
					$('<div />', {'class': 'checkbox'}).append(
						$('<label />').text(this.localize('Whole words only')).prepend(
							$('<input />', {type: 'checkbox', name: 'words'}).on('click', $.proxy(this.clearDoc, this))
						)
					),
					$('<div />', {'class': 'checkbox'}).append(
						$('<label />').text(this.localize('Case sensitive search')).prepend(
							$('<input />', {type: 'checkbox', name: 'matchCase'}).on('click', $.proxy(this.clearDoc, this))
						)
					),
					$('<div />', {'class': 'checkbox'}).append(
						$('<label />').text(this.localize('Substitute all occurrences')).prepend(
							$('<input />', {type: 'checkbox', name: 'replaceAll'}).on('click', $.proxy(this.requestReplacement, this))
						)
					)
				)
			);

			$actions.append(
				$('<h4 />', {'class': 'form-section-headline'}).text(this.localize('Actions')),
				$('<div />', {'class': 'form-group col-sm-12'}).append(
					$('<div />', {'class': 'btn-group'}).append(
						$('<button />', {'class': 'btn btn-default', type: 'button', name: 'clear'})
							.text(this.localize('Clear'))
							.prop('disabled', true)
							.on('click', $.proxy(this.clearMarks, this)),
						$('<button />', {'class': 'btn btn-default', type: 'button', name: 'hiliteall'})
							.text(this.localize('Highlight'))
							.prop('disabled', true)
							.on('click', $.proxy(this.hiliteAll, this)),
						$('<button />', {'class': 'btn btn-default', type: 'button', name: 'undo'})
							.text(this.localize('Undo'))
							.prop('disabled', true)
							.on('click', $.proxy(this.resetContents, this))
					)
				)
			);

			return $('<form />', {'class': 'form-horizontal'}).append($searchReplaceFields, $optionFields, $actions);
		},
		/**
		 * Handler invoked to initialize the pattern to search
		 *
		 * @param {Object} $fieldset The fieldset component
		 */
		initPattern: function ($fieldset) {
			var selection = this.editor.getSelection().getHtml();
			if (/\S/.test(selection)) {
				selection = selection.replace(/<[^>]*>/g, '');
				selection = selection.replace(/&nbsp;/g, '');
			}
			if (/\S/.test(selection)) {
				$fieldset.find('input[name="pattern"]').val(selection);
				$fieldset.find('input[name="replacement"]').focus();
			} else {
				$fieldset.find('input[name="pattern"]').focus();
			}
		},
		/**
		 * Handler invoked when the replace all checkbox is checked
		 */
		requestReplacement: function () {
			var $replacementField = this.dialog.find('input[name="replacement"]');
			if ($replacementField.val() === '' && this.dialog.find('input[name="replaceAll"]').is(':checked')) {
				$replacementField.focus();
			}
			this.clearDoc();
		},
		/*
		 * Handler invoked when the 'Next' button is pressed
		 */
		onNext: function () {
			if (!this.dialog.find('input[name="pattern"]').val()) {
				Notification.warning(
					this.getButton('FindReplace').tooltip,
					this.localize('Enter the text you want to find'),
					10
				);
				this.dialog.find('input[name="pattern"]').focus();
				return false;
			}
			var $currentField,
				fields = [
					'pattern',
					'replacement',
					'words',
					'matchCase',
					'replaceAll'
				];
			var params = {}, field;
			for (var i = fields.length; --i >= 0;) {
				field = fields[i];
				$currentField = this.dialog.find('[name="' + field + '"]');

				if ($currentField.attr('type') === 'checkbox') {
					params[field] = $currentField.prop('checked');
				} else {
					params[field] = $currentField.val();
				}
			}
			this.search(params);
			return false;
		},
		/*
		 * Search the pattern and insert span tags
		 *
		 * @param	object		params: the parameters of the search corresponding to the values of fields:
		 *					pattern
		 *					replacement
		 *					words
		 *					matchCase
		 *					replaceAll
		 *
		 * @return	void
		 */
		search: function (params) {
			var html = this.editor.getInnerHTML();
			if (this.buffer == null) {
				this.buffer = html;
			}
			if (this.matches == 0) {
				var pattern = new RegExp(params.words ? '(?!<[^>]*)(\\b' + params.pattern + '\\b)(?![^<]*>)' : '(?!<[^>]*)(' + params.pattern + ')(?![^<]*>)', 'g' + (params.matchCase? '' : 'i'));
				this.editor.setHTML(html.replace(pattern, '<span id="htmlarea-frmark">' + "$1" + '</span>'));
				var spanElements = this.editor.document.body.getElementsByTagName('span');
				for (var i = 0, n = spanElements.length; i < n; i++) {
					var mark = spanElements[i];
					if (/^htmlarea-frmark/.test(mark.id)) {
						this.spans.push(mark);
					}
				}
			}
			this.spanWalker(params.pattern, params.replacement, params.replaceAll);
		},
		/**
		 * Walk the span tags
		 *
		 * @param {String} pattern The pattern being searched for
		 * @param {String} replacement The replacement string
		 * @param {Boolean} replaceAll True if all occurrences should be replaced
		 */
		spanWalker: function (pattern, replacement, replaceAll) {
			this.clearMarks();
			if (this.spans.length) {
				for (var i = 0, n = this.spans.length; i < n; i++) {
					var mark = this.spans[i];
					if (i >= this.matches && !/[0-9]$/.test(mark.id)) {
						this.matches++;
						this.disableActions('clear', false);
						mark.id = 'htmlarea-frmark_' + this.matches;
						mark.style.color = 'white';
						mark.style.backgroundColor = 'highlight';
						mark.style.fontWeight = 'bold';
						mark.scrollIntoView(false);
						var self = this;
						function replace(button) {
							if (button == 'yes') {
								mark.firstChild.replaceData(0, mark.firstChild.data.length, replacement);
								self.replaces++;
								self.disableActions('undo', false);
							}
							self.endWalk(pattern, i);
						}
						if (replaceAll) {
							replace('yes');
						} else {
							// Due to some scope issues, `confirm` only does not work.
							var confirm = window.confirm('Substitute this occurrence?');
							replace(confirm ? 'yes' : 'no');
							break;
						}
					}
				}
			} else {
				this.endWalk(pattern, 0);
			}
		},
		/**
		 * End the replacement walk
		 *
		 * @param {String} pattern The pattern being searched for
		 * @param {Integer} index The index reached in the walk
		 */
		endWalk: function (pattern, index) {
			if (index >= this.spans.length - 1 || !this.spans.length) {
				var message = '',
					action = '';
				if (this.matches > 0) {
					if (this.matches == 1) {
						message += this.matches + ' ' + this.localize('found item');
					} else {
						message += this.matches + ' ' + this.localize('found items');
					}
					if (this.replaces > 0) {
						if (this.replaces == 1) {
							message += ', ' + this.replaces + ' ' + this.localize('replaced item');
						} else {
							message += ', ' + this.replaces + ' ' + this.localize('replaced items');
						}
					}
					this.hiliteAll();
					action = 'success';
				} else {
					message += '"' + pattern + '" ' + this.localize('not found');
					action = 'warning';
					this.disableActions('hiliteall,clear', true);
				}
				Notification[action](
					this.getButton('FindReplace').tooltip,
					message + '.',
					10
				);
			}
		},
		/**
		 * Remove all marks
		 */
		clearDoc: function () {
			this.editor.setHTML(this.editor.getInnerHTML().replace(this.marksCleaningRE, "$2"));
			this.initVariables();
			this.disableActions('hiliteall,clear', true);
		},
		/**
		 * De-highlight all marks
		 */
		clearMarks: function () {
			var spanElements = this.editor.document.body.getElementsByTagName('span');
			for (var i = spanElements.length; --i >= 0;) {
				var mark = spanElements[i];
				if (/^htmlarea-frmark/.test(mark.id)) {
					mark.style.backgroundColor = '';
					mark.style.color = '';
					mark.style.fontWeight = '';
				}
			}
			this.disableActions('hiliteall', false);
			this.disableActions('clear', true);
		},
		/*
		 * Highlight all marks
		 */
		hiliteAll: function () {
			var spanElements = this.editor.document.body.getElementsByTagName('span');
			for (var i = spanElements.length; --i >= 0;) {
				var mark = spanElements[i];
				if (/^htmlarea-frmark/.test(mark.id)) {
					mark.style.backgroundColor = 'highlight';
					mark.style.color = 'white';
					mark.style.fontWeight = 'bold';
				}
			}
			this.disableActions('clear', false);
			this.disableActions('hiliteall', true);
		},
		/**
		 * Undo the replace operation
		 */
		resetContents: function () {
			if (this.buffer != null) {
				var transp = this.editor.getInnerHTML();
				this.editor.setHTML(this.buffer);
				this.buffer = transp;
				this.disableActions('clear', true);
			}
		},
		/**
		 * Disable action buttons
		 *
		 * @param {String} actions Comma separated list of buttonIds to set disabled/enabled
		 * @param {Boolean} disabled True to set disabled
		 */
		disableActions: function (actions, disabled) {
			var buttonIds = actions.split(/[,; ]+/), action;
			for (var i = buttonIds.length; --i >= 0;) {
				action = buttonIds[i];
				this.dialog.find('[value="' + action + '"]').prop('disabled', disabled);
			}
		},
		/**
		 * Initialize find & replace variables
		 */
		initVariables: function () {
			this.matches = 0;
			this.replaces = 0;
			this.spans = [];
		},

		/**
		 * Clear the document before leaving on 'Done' button
		 */
		onCancel: function () {
			this.clearDoc();
			var plugin = this.getPluginInstance('UndoRedo');
			if (plugin) {
				plugin.start();
			}
			FindReplace.super.prototype.onCancel.call(this);
		},

		/**
		 * Clear the document before leaving on window close handle
		 */
		onClose: function () {
			this.clearDoc();
			var plugin = this.getPluginInstance('UndoRedo');
			if (plugin) {
				plugin.start();
			}
			FindReplace.super.prototype.onClose.call(this);
		}
	});

	return FindReplace;

});
