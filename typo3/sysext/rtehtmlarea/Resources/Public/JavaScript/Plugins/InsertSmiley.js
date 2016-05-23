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
 * Insert Smiley Plugin for TYPO3 htmlArea RTE
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

	var InsertSmiley = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(InsertSmiley, Plugin);
	Util.apply(InsertSmiley.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {
			this.pageTSConfiguration = this.editorConfiguration.buttons.emoticon;
			// Default set of emoticons from Mozilla Thunderbird
			var path = HTMLArea.editorUrl + 'Resources/Public/Images/Plugins/InsertSmiley/Smileys/';
			this.icons = [
				{ file: path + 'mozilla_smile.png', alt: ':-)', title: this.localize('mozilla_smile')},
				{ file: path + 'mozilla_frown.png', alt: ':-(', title: this.localize('mozilla_frown')},
				{ file: path + 'mozilla_wink.png', alt: ';-)', title: this.localize('mozilla_wink')},
				{ file: path + 'mozilla_tongueout.png', alt: ':-P', title: this.localize('mozilla_tongueout')},
				{ file: path + 'mozilla_laughing.png', alt: ':-D', title: this.localize('mozilla_laughing')},
				{ file: path + 'mozilla_embarassed.png', alt: ':-[', title: this.localize('mozilla_embarassed')},
				{ file: path + 'mozilla_undecided.png', alt: ':-\\', title: this.localize('mozilla_undecided')},
				{ file: path + 'mozilla_surprised.png', alt: '=-O', title: this.localize('mozilla_surprised')},
				{ file: path + 'mozilla_kiss.png', alt: ':-*', title: this.localize('mozilla_kiss')},
				{ file: path + 'mozilla_yell.png', alt: '>:o', title: this.localize('mozilla_yell')},
				{ file: path + 'mozilla_cool.png', alt: '8-)', title: this.localize('mozilla_cool')},
				{ file: path + 'mozilla_moneyinmouth.png', alt: ':-$', title: this.localize('mozilla_moneyinmouth')},
				{ file: path + 'mozilla_footinmouth.png', alt: ':-!', title: this.localize('mozilla_footinmouth')},
				{ file: path + 'mozilla_innocent.png', alt: 'O:-)', title: this.localize('mozilla_innocent')},
				{ file: path + 'mozilla_cry.png', alt: ':\'(', title: this.localize('mozilla_cry')},
				{ file: path + 'mozilla_sealed.png', alt: ':-X', title: this.localize('mozilla_sealed')}
			];
			/**
			 * Registering plugin "About" information
			 */
			var pluginInformation = {
				version		: '2.2',
				developer	: 'Ki Master George & Stanislas Rolland',
				developerUrl	: 'http://www.sjbr.ca/',
				copyrightOwner	: 'Ki Master George & Stanislas Rolland',
				sponsor		: 'Ki Master George & SJBR',
				sponsorUrl	: 'http://www.sjbr.ca/',
				license		: 'GPL'
			};
			this.registerPluginInformation(pluginInformation);
			/**
			 * Registering the button
			 */
			var buttonId = 'InsertSmiley';
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize('Insert Smiley'),
				iconCls		: 'htmlarea-action-smiley-insert',
				action		: 'onButtonPress',
				hotKey		: (this.pageTSConfiguration ? this.pageTSConfiguration.hotKey : null),
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
		onButtonPress: function (editor, id) {
			this.dialog = Modal.show(this.localize('Insert Smiley'), this.generateDialogContent(), Severity.notice, [
				this.buildButtonConfig('Cancel', $.proxy(this.onCancel, this), false, Severity.notice)
			]);
			this.dialog.on('modal-dismiss', $.proxy(this.onClose, this));
		},

		/**
		 * Generates the dialog content
		 */
		generateDialogContent: function() {
			var $emoticons = $('<div />');

			$.each(this.icons, function() {
				$emoticons.append(
					$('<a />', {href: '#', 'class': 'btn btn-default emoticon', title: this.title}).append(
						$('<img />', {src: this.file, alt: this.alt})
					)
				);
			});

			$emoticons.on('click', 'a.emoticon', $.proxy(this.insertImageTag, this));

			return $emoticons;
		},

		/**
		 * Insert the selected emoticon
		 *
		 * @param {Event} event The jQuery click event
		 */
		insertImageTag: function (event) {
			event.preventDefault();
			var icon = event.currentTarget.childNodes[0];
			this.restoreSelection();
			var imgTag = this.editor.document.createElement('img');
			imgTag.setAttribute('src', icon.getAttribute('src'));
			imgTag.setAttribute('alt', icon.parentNode.getAttribute('ext:qtitle'));
			imgTag.setAttribute('title', icon.parentNode.getAttribute('ext:qtip'));
			this.editor.getSelection().insertNode(imgTag);
			this.editor.getSelection().selectNode(imgTag, false);
			this.close();
			return false;
		}
	});

	return InsertSmiley;
});
