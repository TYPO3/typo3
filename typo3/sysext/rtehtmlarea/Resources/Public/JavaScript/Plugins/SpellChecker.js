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
 * Spell Checker Plugin for TYPO3 htmlArea RTE
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

	var SpellChecker = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(SpellChecker, Plugin);
	Util.apply(SpellChecker.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function(editor) {
			this.pageTSconfiguration = this.editorConfiguration.buttons.spellcheck;
			this.contentISOLanguage = this.pageTSconfiguration.contentISOLanguage;
			this.contentCharset = 'utf-8';
			this.spellCheckerMode = this.pageTSconfiguration.spellCheckerMode;
			this.enablePersonalDicts = this.pageTSconfiguration.enablePersonalDicts;
			this.userUid = this.editorConfiguration.userUid;
			this.defaultDictionary = (this.pageTSconfiguration.dictionaries && this.pageTSconfiguration.dictionaries[this.contentISOLanguage] && this.pageTSconfiguration.dictionaries[this.contentISOLanguage].defaultValue) ? this.pageTSconfiguration.dictionaries[this.contentISOLanguage].defaultValue : '';
			this.restrictToDictionaries = (this.pageTSconfiguration.dictionaries && this.pageTSconfiguration.dictionaries.restrictToItems) ? this.pageTSconfiguration.dictionaries.restrictToItems : '';

			/**
			 * Registering plugin "About" information
			 */
			var pluginInformation = {
				version		: '3.2',
				developer	: 'Mihai Bazon & Stanislas Rolland',
				developerUrl	: 'http://www.sjbr.ca/',
				copyrightOwner	: 'Mihai Bazon & Stanislas Rolland',
				sponsor		: 'American Bible Society & SJBR',
				sponsorUrl	: 'http://www.sjbr.ca/',
				license		: 'GPL'
			};
			this.registerPluginInformation(pluginInformation);
			/**
			 * Registering the button
			 */
			var buttonId = 'SpellCheck';
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize('SC-spell-check'),
				iconCls		: 'htmlarea-action-spell-check',
				action		: 'onButtonPress',
				dialog		: true
			};
			this.registerButton(buttonConfiguration);
			return true;
		},
		/**
		 * This function gets called when the button was pressed.
		 *
		 * @param {Object} editor The editor instance
		 * @param {String} id The button id or the key
		 * @return {Boolean} False if action is completed
		 */
		onButtonPress: function (editor, id, target) {
			// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
			// Open dialogue window
			this.openDialogue(
				buttonId,
				'Spell Checker'
			);
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
				this.buildButtonConfig('Cancel', $.proxy(this.onCancel, this), true),
				this.buildButtonConfig('Info', $.proxy(this.onInfoClick, this), false),
				this.buildButtonConfig('OK', $.proxy(this.onOK, this), false, Severity.notice)
			]);
			this.dialog
				.on('modal-dismiss', $.proxy(this.onClose, this))
				.on('shown.bs.modal', $.proxy(this.onWindowAfterRender, this));
		},
		/**
		 * Generates the content for the dialog window
		 */
		generateDialogContent: function() {
			var $finalMarkup = $('<div />', {'class': 'row t3js-spellcheck-container'}),
				$sidebar = $('<div />', {'class': 'col-sm-4'}),
				$content = $('<div />', {'class': 'col-sm-8'});

			$sidebar.append(
				$('<form />', {name: 'spell-check-form', 'method': 'post', 'class': 'hidden', action: this.pageTSconfiguration.path}).append(
					$('<input />', {type: 'hidden', name: 'editorId', value: this.editor.editorId}),
					$('<input />', {type: 'hidden', name: 'content', value: this.editor.getHTML()}),
					$('<input />', {type: 'hidden', name: 'dictionary', value: this.defaultDictionary ? this.defaultDictionary : this.contentISOLanguage.toLowerCase()}),
					$('<input />', {type: 'hidden', name: 'pspell_charset', value: this.contentCharset}),
					$('<input />', {type: 'hidden', name: 'pspell_mode', value: this.spellCheckerMode}),
					$('<input />', {type: 'hidden', name: 'userUid', value: this.userUid}),
					$('<input />', {type: 'hidden', name: 'enablePersonalDicts', value: this.enablePersonalDicts}),
					$('<input />', {type: 'hidden', name: 'restrictToDictionaries', value: this.restrictToDictionaries})
				)
			);

			$sidebar.append(
				$('<fieldset />', {'class': 'form-section'}).append(
					$('<h4 />', {'class': 'form-section-headline'}).text(this.localize('Original word')),
					$('<div />', {'class': 'form-group'}).append(
						$('<input />', {'class': 'form-control', name: 'word'}),
						$('<button />', {'class': 'btn btn-default btn-block', name: 'revert', type: 'button'})
							.prop('disabled', true)
							.text(this.localize('Revert'))
							.on('click', $.proxy(this.onRevertClick, this))
					)
				),
				$('<fieldset />', {'class': 'form-section'}).append(
					$('<h4 />', {'class': 'form-section-headline'}).text(this.localize('Replacement')),
					$('<div />', {'class': 'form-group'}).append(
						$('<input />', {'class': 'form-control', name: 'replacement'}),
						$('<div />', {'class': 'btn-group-vertical btn-block'}).append(
							$('<button />', {'class': 'btn btn-default', name: 'replace', type: 'button'})
								.prop('disabled', true)
								.text(this.localize('Replace'))
								.on('click', $.proxy(this.onReplaceClick, this)),
							$('<button />', {'class': 'btn btn-default', name: 'replaceAll', type: 'button'})
								.prop('disabled', true)
								.text(this.localize('Replace all'))
								.on('click', $.proxy(this.onReplaceAllClick, this)),
							$('<button />', {'class': 'btn btn-default', name: 'ignore', type: 'button'})
								.prop('disabled', true)
								.text(this.localize('Ignore'))
								.on('click', $.proxy(this.onIgnoreClick, this)),
							$('<button />', {'class': 'btn btn-default', name: 'ignoreAll', type: 'button'})
								.prop('disabled', true)
								.text(this.localize('Ignore all'))
								.on('click', $.proxy(this.onIgnoreAllClick, this)),
							$('<button />', {'class': 'btn btn-default', name: 'learn', type: 'button'})
								.prop('disabled', true)
								.text(this.localize('Learn'))
								.on('click', $.proxy(this.onLearnClick, this)).toggle(this.enablePersonalDicts)
						)
					)
				),
				$('<fieldset />', {'class': 'form-section'}).append(
					$('<h4 />', {'class': 'form-section-headline'}).text(this.localize('Suggestions')),
					$('<div />', {'class': 'form-group'}).append(
						$('<select />', {'class': 'form-control', name: 'suggestions'})
							.on('change', $.proxy(this.onSuggestionSelect, this))
					)
				),
				$('<fieldset />', {'class': 'form-section'}).append(
					$('<h4 />', {'class': 'form-section-headline'}).text(this.localize('Dictionary')),
					$('<div />', {'class': 'form-group'}).append(
						$('<select />', {'class': 'form-control', name: 'dictionaries'})
							.on('change', $.proxy(this.onDictionarySelect, this)),
						$('<button />', {'class': 'btn btn-default btn-block', name: 'recheck', type: 'button'})
							.prop('disabled', true)
							.text(this.localize('Re-check'))
							.on('click', $.proxy(this.onRecheckClick, this))
					)
				)
			);

			$content.append(
				$('<iframe />', {
					id: 'spell-check-iframe',
					name: 'contentframe',
					src: UserAgent.isGecko ? 'javascript:void(0);' : HTMLArea.editorUrl + 'Resources/Public/Html/blank.html',
					frameborder: 0
				})
			);

			return $finalMarkup.append($sidebar, $content);
		},
		synchronizeIframeHeight: function () {
			var $iframe = $('iframe[name="contentframe"]'),
				$parentContainer = $iframe.closest('.t3js-spellcheck-container');

			$iframe.height($parentContainer.height());
		},
		/**
		 * Handler invoked after the window has been rendered
		 */
		onWindowAfterRender: function () {
			var self = this;
			// True when some word has been modified
			this.modified = false;
			// Array of words to add to the personal dictionary
			this.addToPersonalDictionary = [];
			// List of word pairs to add to replacement list of the personal dictionary
			this.addToReplacementList = [];
			// Initial submit
			var $form = this.dialog.find('[name="spell-check-form"]');
			$form
				.attr('target', 'contentframe')
				.attr('accept-charset', self.contentCharset.toUpperCase())
				.submit();

			this.synchronizeIframeHeight();

			this.status = $('<p />', {'class': 'spell-check-status col-sm-12'}).appendTo('.t3js-spellcheck-container');
		},
		/**
		 * Handler invoked when the OK button is pressed
		 */
		onOK: function () {
			if (this.modified) {
				this.editor.setHTML(this.cleanDocument(false));
			}
				// Post additions to the Aspell personal dictionary
			if ((this.addToPersonalDictionary.length || this.addToReplacementList.length) && this.enablePersonalDicts) {
				var data = {
					cmd: 'learn',
					enablePersonalDicts: this.enablePersonalDicts,
					userUid: this.userUid,
					dictionary: this.dialog.find('[name="dictionary"]').val(),
					pspell_charset: this.contentCharset,
					pspell_mode: this.spellCheckerMode
				};
				var word;
				for (var index = this.addToPersonalDictionary.length; --index >= 0;) {
					word = this.addToPersonalDictionary[index];
					data['to_p_dict[' + index + ']'] = word;
				}
				var replacement;
				for (var index = this.addToReplacementList.length; --index >= 0;) {
					replacement = this.addToReplacementList[index];
					data['to_r_list[' + index + '][0]'] = replacement[0];
					data['to_r_list[' + index + '][1]'] = replacement[1];
				}
				this.postData(this.pageTSconfiguration.path, data);
			}
			this.close();
			return false;
		},

		/**
		 * Handler invoked when the Cancel button is pressed
		 */
		onCancel: function () {
			if (this.modified) {
				var confirm = window.confirm(this.localize('QUIT_CONFIRMATION'));
				if (confirm) {
					this.close();
				}
				return false;
			} else {
				return SpellChecker.super.prototype.onCancel.call(this);
			}
		},
		/**
		 * Clean away span elements from the text before leaving or re-submitting
		 *
		 * @param {Boolean} leaveFixed If true, span elements of corrected words will be left in the text (re-submit case)
		 * @return {String} cleaned-up html
		 */
		cleanDocument: function (leaveFixed) {
			var iframeDocument = this.dialog.find('#spell-check-iframe').get(0).contentWindow.document;
			var spanElements = this.misspelledWords.concat(this.correctedWords);
			for (var i = spanElements.length; --i >= 0;) {
				var element = spanElements[i];
				element.onclick = null;
				element.onmouseover = null;
				element.onmouseout = null;
				if (!leaveFixed || !Dom.hasClass(element, 'htmlarea-spellcheck-fixed')) {
					element.parentNode.insertBefore(element.firstChild, element);
					element.parentNode.removeChild(element);
				} else {
					Dom.removeClass(element, 'htmlarea-spellcheck-error');
					Dom.removeClass(element, 'htmlarea-spellcheck-same');
					Dom.removeClass(element, 'htmlarea-spellcheck-current');
				}
			}
			// Cleanup event handlers on links
			var linkElements = iframeDocument.getElementsByTagName('a');
			for (var i = linkElements.length; --i >= 0;) {
				var link = linkElements[i];
				link.onclick = null;
			}
			return this.editor.iframe.htmlRenderer.render(iframeDocument.body, false);
		},
		/**
		 * Handler invoked when the response from the server has finished loading.
		 * This is triggered by SpellCheckingController.php
		 */
		spellCheckComplete: function () {
			var contentWindow = this.dialog.find('#spell-check-iframe').get(0).contentWindow;
			this.currentElement = null;
			// Array of misspelled words
			this.misspelledWords = [];
			// Array of corrected words
			this.correctedWords = [];
			// Object containing array of occurrences of each misspelled word
			this.allWords = {};
			// Suggested words
			this.suggestedWords = contentWindow.suggestedWords;
			// Set status
			this.status.text(this.localize('statusBarReady'));
			// Process all misspelled words
			var id = 0;
			var self = this;
			var spanElements = contentWindow.document.getElementsByTagName('span');
			for (var i = spanElements.length; --i >= 0;) {
				var span = spanElements[i];
				if (Dom.hasClass(span, 'htmlarea-spellcheck-error')) {
					this.misspelledWords.push(span);
					span.onclick = function (event) { self.setCurrentWord(this, false); };
					span.onmouseover = function (event) { Dom.addClass(this, 'htmlarea-spellcheck-hover'); };
					span.onmouseout = function (event) { Dom.removeClass(this, 'htmlarea-spellcheck-hover'); };
					span.htmlareaId = id++;
					span.htmlareaOriginalWord = span.firstChild.data;
					span.htmlareaFixed = false;
					if (typeof this.allWords[span.htmlareaOriginalWord] === 'undefined') {
						this.allWords[span.htmlareaOriginalWord] = [];
					}
					this.allWords[span.htmlareaOriginalWord].push(span);
				} else if (Dom.hasClass(span, 'htmlarea-spellcheck-fixed')) {
					this.correctedWords.push(span);
				}
			}
			// Do not open links in the iframe
			var linkElements = contentWindow.document.getElementsByTagName('a');
			for (var i = linkElements.length; --i >= 0;) {
				var link = linkElements[i];
				link.onclick = function (event) { return false; };
			}
			// Enable buttons
			var buttons = this.dialog.find('button');
			buttons.each(function() {
				$(this).prop('disabled', false);
			});
			if (this.misspelledWords.length) {
				// Set current element to first misspelled word
				this.currentElement = this.misspelledWords[0];
				this.setCurrentWord(this.currentElement, true);
				// Populate the dictionaries combo
				var dictionaries = contentWindow.dictionaries.split(/,/);
				if (dictionaries.length) {
					var select = this.dialog.find('[name="dictionaries"]');
					select.empty();
					var dictionary;
					for (var i = dictionaries.length; --i >= 0;) {
						dictionary = dictionaries[i];
						$('<option />', {value: dictionary}).text(dictionary).prependTo(select);
					}
					$('<option />').text('Please select').prependTo(select);
					select.val(contentWindow.selectedDictionary);
				}
			} else {
				if (!this.modified) {
					Notification.info(
						this.getButton('SpellCheck').tooltip.title,
						this.localize('NO_ERRORS_CLOSING')
					);
					this.close();
				} else {
					Notification.info(
						this.getButton('SpellCheck').tooltip.title,
						this.localize('NO_ERRORS')
					);
				}
				return false;
			}
		},
		/**
		 * Get absolute position of an element inside the iframe
		 */
		getAbsolutePosition: function (element) {
			var position = {
				x: element.offsetLeft,
				y: element.offsetTop
			};
			if (element.offsetParent) {
				var tmp = this.getAbsolutePosition(element.offsetParent);
				position.x += tmp.x;
				position.y += tmp.y;
			}
			return position;
		},
		/**
		 * Update current word
		 */
		setCurrentWord: function (element, scroll) {
			// Scroll element into view
			if (scroll) {
				var frame = this.dialog.find('#spell-check-iframe').get(0);
				var position = this.getAbsolutePosition(element);
				var frameSize = {
					x: frame.offsetWidth - 4,
					y: frame.offsetHeight - 4
				};
				position.x -= Math.round(frameSize.x/2);
				if (position.x < 0) {
					position.x = 0;
				}
				position.y -= Math.round(frameSize.y/2);
				if (position.y < 0) {
					position.y = 0;
				}
				frame.contentWindow.scrollTo(position.x, position.y);
			}
			// De-highlight all occurrences of current word
			if (this.currentElement) {
				Dom.removeClass(this.currentElement, 'htmlarea-spellcheck-current');
				var occurrences = this.allWords[this.currentElement.htmlareaOriginalWord];
				for (var i = occurrences.length; --i >= 0;) {
					var word = occurrences[i];
					Dom.removeClass(word, 'htmlarea-spellcheck-same');
				}
			}
			// Highlight all occurrences of new current word
			this.currentElement = element;
			Dom.addClass(this.currentElement, 'htmlarea-spellcheck-current');
			var occurrences = this.allWords[this.currentElement.htmlareaOriginalWord];
			for (var i = occurrences.length; --i >= 0;) {
				var word = occurrences[i];
				if (word != this.currentElement) {
					Dom.addClass(word, 'htmlarea-spellcheck-same');
				}
			}
			this.dialog.find('[name="replaceAll"]').prop('disabled', occurrences.length <= 1);
			this.dialog.find('[name="ignoreAll"]').prop('disabled', occurrences.length <= 1);
			// Display status
			var txt;
			var txt2;
			if (occurrences.length === 1) {
				txt = this.localize('One occurrence');
				txt2 = this.localize('was found.');
			} else if (occurrences.length === 2) {
				txt = this.localize('Two occurrences');
				txt2 = this.localize('were found.');
			} else {
				txt = occurrences.length + ' ' + this.localize('occurrences');
				txt2 = this.localize('were found.');
			}
			this.status.html(txt + ' ' + this.localize('of the word') + ' "<b>' + this.currentElement.htmlareaOriginalWord + '</b>" ' + txt2);
			// Update suggestions
			var suggestions = this.suggestedWords[this.currentElement.htmlareaOriginalWord];
			if (suggestions) {
				suggestions = suggestions.split(/,/);
			} else {
				suggestions = [];
			}
			var select = this.dialog.find('[name="suggestions"]');
			select.empty();
			var suggestion;
			for (var i = suggestions.length; --i >= 0;) {
				suggestion = suggestions[i];
				$('<option />', {value: suggestion}).text(suggestion).prependTo(select);
			}
			$('<option />').text('Please select').prependTo(select);
			// Update the current word
			this.dialog.find('[name="word"]').val(this.currentElement.htmlareaOriginalWord);
			if (suggestions.length > 0) {
				select.val(select.find('option:first').val());
			} else {
				this.dialog.find('[name="replacement"]').val(this.currentElement.innerHTML);
			}
			return false;
		},
		/**
		 * Handler invoked when a suggestion is selected
		 *
		 * @param {Event} e
		 */
		onSuggestionSelect: function (e) {
			this.dialog.find('[name="replacement"]').val($(e.currentTarget).val());
		},
		/**
		 * Handler invoked when a dictionary is selected
		 *
		 * @param {Event} e
		 */
		onDictionarySelect: function (e) {
			this.dialog.find('[name="dictionary"]').val($(e.currentTarget).val());
		},
		/**
		 * Handler invoked when the Revert button is clicked
		 */
		onRevertClick: function () {
			this.dialog.find('[name="replacement"]').val(this.currentElement.htmlareaOriginalWord);
			this.replaceWord(this.currentElement);
			Dom.removeClass(this.currentElement, 'htmlarea-spellcheck-fixed');
			Dom.addClass(this.currentElement, 'htmlarea-spellcheck-error');
			Dom.addClass(this.currentElement, 'htmlarea-spellcheck-current');
			return false;
		},
		/**
		 * Replace the word contained in the element
		 */
		replaceWord: function (element) {
			Dom.removeClass(element, 'htmlarea-spellcheck-hover');
			Dom.addClass(element, 'htmlarea-spellcheck-fixed');
			element.htmlareaFixed = true;
			var replacement = this.dialog.find('[name="replacement"]').val();
			if (element.innerHTML != replacement) {
				this.addToReplacementList.push([element.innerHTML, replacement]);
				element.innerHTML = replacement;
				this.modified = true;
			}
		},
		/**
		 * Handler invoked when the Replace button is clicked
		 */
		onReplaceClick: function () {
			this.replaceWord(this.currentElement);
			var start = this.currentElement.htmlareaId;
			var index = start;
			do {
				++index;
				if (index == this.misspelledWords.length) {
					index = 0;
				}
			} while (index != start && this.misspelledWords[index].htmlareaFixed);
			if (index == start) {
				index = 0;
				Notification.info(
					this.getButton('SpellCheck').tooltip.title,
					this.localize('Finished list of mispelled words')
				);
			}
			this.setCurrentWord(this.misspelledWords[index], true);
			return false;
		},
		/**
		 * Handler invoked when the Replace all button is clicked
		 */
		onReplaceAllClick: function () {
			var words = this.allWords[this.currentElement.htmlareaOriginalWord];
			for (var i = words.length; --i >= 0;) {
				var element = words[i];
				if (element != this.currentElement) {
					this.replaceWord(element);
				}
			}
			// Replace current element last, so that we jump to the next word
			return this.onReplaceClick();
		},
		/**
		 * Handler invoked when the Ignore button is clicked
		 */
		onIgnoreClick: function () {
			this.dialog.find('[name="replacement"]').val(this.currentElement.htmlareaOriginalWord);
			return this.onReplaceClick();
		},
		/**
		 * Handler invoked when the Ignore all button is clicked
		 */
		onIgnoreAllClick: function () {
			this.dialog.find('[name="replacement"]').val(this.currentElement.htmlareaOriginalWord);
			return this.onReplaceAllClick();
		},
		/**
		 * Handler invoked when the Learn button is clicked
		 */
		onLearnClick: function () {
			this.addToPersonalDictionary.push(this.currentElement.htmlareaOriginalWord);
			return this.onIgnoreAllClick();
		},
		/**
		 * Handler invoked when the Re-check button is clicked
		 */
		onRecheckClick: function () {
			// Disable buttons
			var buttons = this.dialog.find('button');
			buttons.each(function() {
				$(this).prop('disabled', true);
			});
			this.status.text(this.localize('Please wait: changing dictionary to') + ': "' + this.dialog.find('[name="dictionary"]').val() + '".');
			this.dialog.find('[name="content"]').val(this.cleanDocument(true));
			this.dialog.find('[name="spell-check-form"]').submit();
		},

		/**
		 * Handler invoked when the Info button is clicked
		 */
		onInfoClick: function () {
			var info = this.dialog.find('#spell-check-iframe').get(0).contentWindow.spellcheckInfo;
			if (!info) {
				Notification.info(
					this.getButton('SpellCheck').tooltip.title,
					this.localize('No information available')
				);
			} else {
				var txt = '';
				for (var key in info) {
					txt += (txt ? "\n" : '') + this.localize(key) + ': ' + info[key];
				}
				txt += ' ' + this.localize('seconds');
				Notification.info(
					this.localize('Document information'),
					txt
				);
			}
			return false;
		}
	});

	return SpellChecker;
});
