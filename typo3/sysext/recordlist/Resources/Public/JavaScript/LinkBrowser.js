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
 * Module: TYPO3/CMS/Recordlist/LinkBrowser
 * LinkBrowser communication with parent window
 */
define(['jquery'], function($) {
	'use strict';

	/**
	 *
	 * @type {{thisScriptUrl: string, urlParameters: {}, parameters: {}, addOnParams: string, linkAttributeFields: Array, additionalLinkAttributes: {}, finalizeFunction: null}}
	 * @exports TYPO3/CMS/Recordlist/LinkBrowser
	 */
	var LinkBrowser = {
		thisScriptUrl: '',
		urlParameters: {},
		parameters: {},
		addOnParams: '',
		linkAttributeFields: [],
		additionalLinkAttributes: {},
		finalizeFunction: null
	};

	/**
	 * Collect the link attributes values as object
	 *
	 * @returns {Object}
	 */
	LinkBrowser.getLinkAttributeValues = function() {
		var attributeValues = {};
		$.each(LinkBrowser.linkAttributeFields, function(index, fieldName) {
			var val = $('[name="l' + fieldName + '"]').val();
			if (val) {
				attributeValues[fieldName] = val;
			}
		});
		$.extend(attributeValues, LinkBrowser.additionalLinkAttributes);
		return attributeValues;
	};

	/**
	 *
	 */
	LinkBrowser.loadTarget = function() {
		$('.t3js-linkTarget').val($(this).val());
		this.selectedIndex = 0;
	};

	/**
	 * Encode objects to GET parameter arrays in PHP notation
	 *
	 * @param {Object} obj
	 * @param {String} prefix
	 * @param {String} url
	 * @returns {String}
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

	/**
	 * Set an additional attribute for the link
	 *
	 * @param {String} name
	 * @param value
	 */
	LinkBrowser.setAdditionalLinkAttribute = function(name, value) {
		LinkBrowser.additionalLinkAttributes[name] = value;
	};

	/**
	 * Stores the final link
	 *
	 * This method MUST be overridden in the actual implementation of the link browser.
	 * The function is responsible for encoding the link (and possible link attributes) and
	 * returning it to the caller (e.g. FormEngine, RTE, etc)
	 *
	 * @param {String} link The select element or anything else which identifies the link (e.g. "page:<pageUid>" or "file:<uid>")
	 */
	LinkBrowser.finalizeFunction = function(link) {
		throw 'The link browser requires the finalizeFunction to be set. Seems like you discovered a major bug.';
	};

	$(function() {
		var data = $('body').data();

		LinkBrowser.thisScriptUrl = data.thisScriptUrl;
		LinkBrowser.urlParameters = data.urlParameters;
		LinkBrowser.parameters = data.parameters;
		LinkBrowser.addOnParams = data.addOnParams;
		LinkBrowser.linkAttributeFields = data.linkAttributeFields;

		$('.t3js-targetPreselect').on('change', LinkBrowser.loadTarget);
		$('form.t3js-dummyform').on('submit', function(evt) { evt.preventDefault(); });
	});

	/**
	 * Global jumpTo function
	 *
	 * Used by tree implementation
	 *
	 * @param {String} URL
	 * @param {String} anchor
	 * @returns {Boolean}
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
