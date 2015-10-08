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
 * Module: TYPO3/CMS/Backend/FormEngineLinkBrowserAdapter
 * LinkBrowser communication with parent window
 */
define(['jquery', 'TYPO3/CMS/Recordlist/LinkBrowser'], function($, LinkBrowser) {
	'use strict';

	/**
	 *
	 * @type {{updateFunctions: null}}
	 * @exports TYPO3/CMS/Backend/FormEngineLinkBrowserAdapter
	 */
	var FormEngineLinkBrowserAdapter = {
		updateFunctions: null // those are set in the module initializer function in PHP
	};

	/**
	 * Return reference to parent's form element
	 *
	 * @returns {Element}
	 */
	FormEngineLinkBrowserAdapter.checkReference = function () {
		var selector = 'form[name="' + LinkBrowser.parameters.formName + '"] [data-formengine-input-name="' + LinkBrowser.parameters.itemName + '"]';
		if (window.opener && window.opener.document && window.opener.document.querySelector(selector)) {
			return window.opener.document.querySelector(selector);
		} else {
			close();
		}
	};

	/**
	 * Save the current link back to the opener
	 *
	 * @param {String} input
	 */
	LinkBrowser.finalizeFunction = function(input) {
		var field = FormEngineLinkBrowserAdapter.checkReference();
		if (field) {
			var attributeValues = LinkBrowser.getLinkAttributeValues();

			// remove page: prefix from page links
			if (input.indexOf('page:') === 0) {
				input = input.substr(5);
			}

			// remove the mailto: prefix from mail links
			if (input.indexOf('mailto:') === 0) {
				input = input.substr(7);
			}

			// remove http:// schema for external links
			if (attributeValues['data-htmlarea-external'] && input.substr(0, 7) === "http://") {
				input = input.substr(7);
			}

			// encode link on server
			attributeValues.url = input;

			$.ajax({
				url: TYPO3.settings.ajaxUrls['link_browser_encodetypolink'],
				data: attributeValues,
				method: 'GET',
				async: false, // todo: check if we can use promises
				success: function(data) {
					if (data.typoLink) {
						field.value = data.typoLink;
						if (typeof field.onchange === 'function') {
							field.onchange();
						}

						FormEngineLinkBrowserAdapter.updateFunctions();

						close();
					}
				}
			});
		}
	};

	return FormEngineLinkBrowserAdapter;
});
