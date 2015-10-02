/**
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
 * LinkBrowser communication with parent window
 */
define('TYPO3/CMS/Recordlist/LinkBrowser', ['jquery'], function($) {
	"use strict";

	var LinkBrowser = {
		thisScriptUrl: '',
		urlParameters: {},
		parameters: {},
		addOnParams: '',
		linkAttributeFields: [],
		updateFunctions: null // those are set in the module initializer function in PHP
	};

	/**
	 * Return reference to parent's form element
	 *
	 * @returns {Element}
	 */
	LinkBrowser.checkReference = function () {
		var selector = 'form[name="' + LinkBrowser.parameters.formName + '"] [data-formengine-input-name="' + LinkBrowser.parameters.itemName + '"]';
		if (window.opener && window.opener.document && window.opener.document.querySelector(selector)) {
			return window.opener.document.querySelector(selector);
		} else {
			close();
		}
	};

	/**
	 * Collect the link attributes values as object
	 *
	 * @returns {object}
	 */
	LinkBrowser.getLinkAttributeValues = function() {
		var attributeValues = {};
		$.each(LinkBrowser.linkAttributeFields, function(index, fieldName) {
			var val = $('[name="l' + fieldName + '"]').val();
			if (val) {
				attributeValues[fieldName] = val;
			}
		});
		return attributeValues;
	};

	/**
	 * Save the current link back to the opener
	 *
	 * @param input
	 */
	LinkBrowser.updateValueInMainForm = function(input) {
		var field = LinkBrowser.checkReference();
		if (field) {
			var attributeValues = LinkBrowser.getLinkAttributeValues();

			// encode link on server
			attributeValues.url = input;

			$.ajax({
				url: TYPO3.settings.ajaxUrls['link_browser_encodeTypoLink'],
				data: attributeValues,
				method: 'GET',
				async: false,
				success: function(data) {
					if (data.typoLink) {
						field.value = data.typoLink;
						if (typeof field.onchange === 'function') {
							field.onchange();
						}

						LinkBrowser.updateFunctions();
					}
				}
			});
		}
	};

	LinkBrowser.loadTarget = function() {
		$('#linkTarget').val($(this).val());
		this.selectedIndex = 0;
	};

	/**
	 * Encode objects to GET parameter arrays in PHP notation
	 *
	 * @param {object} obj
	 * @param {string} prefix
	 * @param {string} url
	 * @returns {string}
	 */
	LinkBrowser.encodeGetParameters = function(obj, prefix, url) {
		var str = [];
		for(var p in obj) {
			if (obj.hasOwnProperty(p)) {
				var k = prefix ? prefix + "[" + p + "]" : p, v = obj[p];
				if (url.indexOf(k + "=") === -1) {
					str.push(
						typeof v === "object"
							? LinkBrowser.encodeGetParameters(v, k, url)
							: encodeURIComponent(k) + "=" + encodeURIComponent(v)
					);
				}
			}
		}
		return '&' + str.join("&");
	};

	$(function() {
		var data = $('body').data();

		LinkBrowser.thisScriptUrl = data.thisScriptUrl;
		LinkBrowser.urlParameters = data.urlParameters;
		LinkBrowser.parameters = data.parameters;
		LinkBrowser.addOnParams = data.addOnParams;
		LinkBrowser.linkAttributeFields = data.linkAttributeFields;

		$('#targetPreselect').on('change', LinkBrowser.loadTarget);
	});

	/**
	 * Global jumpTo function
	 *
	 * Used by tree implementation
	 *
	 * @param {string} URL
	 * @param {string} anchor
	 * @returns {boolean}
	 */
	window.jumpToUrl = function(URL, anchor) {
		if (URL.charAt(0) === '?') {
			URL = LinkBrowser.thisScriptUrl + URL.substring(1);
		}
		var urlParameters = LinkBrowser.encodeGetParameters(LinkBrowser.urlParameters, '', URL);
		var parameters = LinkBrowser.encodeGetParameters(LinkBrowser.getLinkAttributeValues(), 'linkAttributes', '');

		window.location.href = URL + urlParameters + parameters + LinkBrowser.addOnParams + (typeof(anchor) === "string" ? anchor : '');
		return false;
	};

	return LinkBrowser;
});
