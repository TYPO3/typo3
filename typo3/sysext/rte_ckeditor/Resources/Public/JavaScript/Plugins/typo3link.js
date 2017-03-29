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

'use strict';

(function () {

	CKEDITOR.plugins.add('typo3link', {
		elementBrowser: null,
		init: function (editor) {
			var allowedAttributes = ['!href', 'title', 'class', 'target', 'rel'],
				required = 'a[href]';

			var additionalAttributes = getAdditionalAttributes(editor);
			if (additionalAttributes.length) {
				allowedAttributes.push.apply(allowedAttributes, additionalAttributes);
			}

			// Override link command
			editor.addCommand('link', {
				exec: openLinkBrowser,
				allowedContent: 'a[' + allowedAttributes.join(',') + ']',
				requiredContent: required
			});

			// Override doubleclick opening default link dialog
			editor.on('doubleclick', function (evt) {
				var element = CKEDITOR.plugins.link.getSelectedLink(editor) || evt.data.element;
				if (!element.isReadOnly() && element.is('a') && element.getAttribute('href')) {
					evt.stop();
					openLinkBrowser(editor, element);
				}
			}, null, null, 30);

		}
	});

	/**
	 * Open link browser
	 *
	 * @param {Object} editor CKEditor object
	 * @param {Object} element Selected link element
	 */
	function openLinkBrowser(editor, element) {
		var additionalParameters = '';

		if (!element) {
			element = CKEDITOR.plugins.link.getSelectedLink(editor);
		}
		if (element) {
			additionalParameters = '&curUrl[url]=' + encodeURIComponent(element.getAttribute('href'));
			var i = 0,
				attributeNames = ["target", "class", "title", "rel"];
			for (i = 0; i < attributeNames.length; ++i) {
				if (element.getAttribute(attributeNames[i])) {
					additionalParameters += '&curUrl[' + attributeNames[i] + ']=';
					additionalParameters += encodeURIComponent(element.getAttribute(attributeNames[i]));
				}
			}

			var additionalAttributes = getAdditionalAttributes(editor);
			for (i = additionalAttributes.length; --i >= 0;) {
				if (element.hasAttribute(additionalAttributes[i])) {
					additionalParameters += '&curUrl[' + additionalAttributes[i] + ']=';
					additionalParameters += encodeURIComponent(element.getAttribute(additionalAttributes[i]));
				}
			}
		}

		openElementBrowser(
			editor,
			editor.lang.link.toolbar,
			TYPO3.settings.Textarea.RTEPopupWindow.height,
			makeUrlFromModulePath(
				editor,
				editor.config.typo3link.routeUrl,
				additionalParameters
			));
	}

	/**
	 * Make url from url
	 *
	 * @param {Object} editor CKEditor object
	 * @param {String} routeUrl URL
	 * @param {String} parameters Additional parameters
	 *
	 * @return {String} The url
	 */
	function makeUrlFromModulePath(editor, routeUrl, parameters) {

		return routeUrl
			+ (routeUrl.indexOf('?') === -1 ? '?' : '&')
			+ '&contentsLanguage=' + editor.config.contentsLanguage
			+ '&editorId=' + editor.id
			+ (parameters ? parameters : '');
	}

	/**
	 * Open a window with container iframe
	 *
	 * @param {Object} editor The CKEditor instance
	 * @param {String} title The window title (will be localized here)
	 * @param {Integer} height The height of the containing iframe
	 * @param {String} url The url to load ino the iframe
	 */
	function openElementBrowser(editor, title, height, url) {
		require([
			'jquery',
			'TYPO3/CMS/Backend/Modal'
			], function ($, Modal) {

			Modal.advanced({
				type: Modal.types.iframe,
				title: title,
				content: url,
				size: Modal.sizes.large,
				callback: function(currentModal) {
					currentModal.find('.t3js-modal-body')
						.addClass('rte-ckeditor-window')
						.attr('id', editor.id);
				}
			});
		});
	}

	/**
	 * Fetch attributes for the <a> tag which are allowed additionally
	 * @param {Object} editor The CKEditor instance
	 *
	 * @return {Array} registered attributes available for the link
	 */
	function getAdditionalAttributes(editor) {
		if (editor.config.typo3link.additionalAttributes && editor.config.typo3link.additionalAttributes.length) {
			return editor.config.typo3link.additionalAttributes;
		} else {
			return [];
		}
	}

})();
