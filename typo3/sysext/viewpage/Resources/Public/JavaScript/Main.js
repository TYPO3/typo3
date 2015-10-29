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
 * Module: TYPO3/CMS/Viewpage/Main
 * Main logic for resizing the view of the frame
 */
define(['jquery', 'TYPO3/CMS/Backend/Storage', 'jquery-ui/resizable'], function($, Storage) {
	'use strict';

	/**
	 *
	 * @type {{resizableContainerIdentifier: string, widthSelectorIdentifier: string, moduleBodySelector: string, storagePrefix: string, $iframe: null, $languageSelector: null, $resizableContainer: null, $widthSelector: null}}
	 * @exports TYPO3/CMS/Viewpage/Main
	 */
	var ViewPage = {
		resizableContainerIdentifier: '#resizeable',
		widthSelectorIdentifier: '#width',
		moduleBodySelector: '.t3js-module-body',
		storagePrefix: 'moduleData.web_view.States.',
		$iframe: null,
		$languageSelector: null,
		$resizableContainer: null,
		$widthSelector: null
	};

	/**
	 *
	 */
	ViewPage.initialize = function() {
		ViewPage.$iframe = $('#tx_viewpage_iframe');
		ViewPage.$languageSelector = $('#language');
		ViewPage.$resizableContainer = $(ViewPage.resizableContainerIdentifier);
		ViewPage.$widthSelector = $(ViewPage.widthSelectorIdentifier);

		// Add event to width selector so the container is resized
		$(document).on('change', ViewPage.widthSelectorIdentifier, function() {
			var value = ViewPage.$widthSelector.val();
			if (value) {
				value = value.split('|');
				var height = value[1] || '100%';
				if (height === '100%') {
					height = ViewPage.calculateContainerMaxHeight();
				}
				ViewPage.$resizableContainer.animate({
					width:  value[0],
					height: height
				});
				Storage.Persistent.set(ViewPage.storagePrefix + 'widthSelectorValue', value[0] + '|' + (value[1] || '100%'));
			}
		});

		// Restore custom selector
		var storedCustomWidth = Storage.Persistent.get(ViewPage.storagePrefix + 'widthSelectorCustomValue');
		// Check for the " symbol is done in order to avoid problems with the old (non-jQuery) syntax which might be stored inside
		// the UC from previous versions, can be removed with TYPO3 CMS9 again
		if (storedCustomWidth && storedCustomWidth.indexOf('"') === -1) {
			// add custom selector if stored value is not there
			if (ViewPage.$widthSelector.find('option[value="' + storedCustomWidth + '"]').length === 0) {
				ViewPage.addCustomWidthOption(storedCustomWidth);
			}
		}

		// Re-select stored value
		var storedWidth = Storage.Persistent.get(ViewPage.storagePrefix + 'widthSelectorValue');
		// Check for the " symbol is done in order to avoid problems with the old (non-jQuery) syntax which might be stored inside
		// the UC from previous versions, can be removed with TYPO3 CMS9 again
		if (storedWidth && storedWidth.indexOf('"') === -1) {
			ViewPage.$widthSelector.val(storedWidth).trigger('change');
		}

		// Initialize the jQuery UI Resizable plugin
		ViewPage.$resizableContainer.resizable({
			handles: 'e, se, s'
		});

		// Create and select custom option
		ViewPage.$resizableContainer.on('resizestart', function() {
			// Check custom option is there, if not, add it
			if (ViewPage.$widthSelector.find('#customOption').length === 0) {
				ViewPage.addCustomWidthOption('100%|100%');
			}
			// Select the custom option
			ViewPage.$widthSelector.find('#customOption').prop('selected', true);

			// Add iframe overlay to prevent loosing the mouse focus to the iframe while resizing fast
			$(this).append('<div id="iframeCover" style="z-index:99;position:absolute;width:100%;top:0;left:0;height:100%;"></div>');
		});

		ViewPage.$resizableContainer.on('resize', function(evt, ui) {
			// Update custom option
			var value = ui.size.width + '|' + ui.size.height;
			var label = ViewPage.getOptionLabel(value);
			ViewPage.$widthSelector.find('#customOption').text(label).val(value);
		});

		ViewPage.$resizableContainer.on('resizestop', function() {
			Storage.Persistent.set(ViewPage.storagePrefix + 'widthSelectorCustomValue', ViewPage.$widthSelector.val()).done(function() {
				Storage.Persistent.set(ViewPage.storagePrefix + 'widthSelectorValue', ViewPage.$widthSelector.val());
			});

			// Remove iframe overlay
			$('#iframeCover').remove();
		});

		// select stored language
		var storedLanguage = Storage.Persistent.get(ViewPage.storagePrefix + 'languageSelectorValue');
		if (storedLanguage) {
			// select it
			ViewPage.$languageSelector.val(storedLanguage);
		}

		// Add event to language selector
		ViewPage.$languageSelector.on('change',function() {
			var iframeUrl = ViewPage.$iframe.attr('src');
			var iframeParameters = ViewPage.getUrlVars(iframeUrl);
			// change language
			iframeParameters.L = ViewPage.$languageSelector.val();
			var newIframeUrl = iframeUrl.slice(0, iframeUrl.indexOf('?') + 1) + $.param(iframeParameters);
			// load new url into iframe
			ViewPage.$iframe.attr('src', newIframeUrl);
			Storage.Persistent.set(ViewPage.storagePrefix + 'languageSelectorValue', ViewPage.$languageSelector.val());
		});
	};

	/**
	 *
	 * @returns {Number}
	 */
	ViewPage.calculateContainerMaxHeight = function() {
		ViewPage.$resizableContainer.hide();
		var $moduleBody = $(ViewPage.moduleBodySelector);
		var padding = $moduleBody.outerHeight() - $moduleBody.height(),
			controlsHeight = ViewPage.$widthSelector.parents('form:first').height(),
			documentHeight = $(document).height();
		ViewPage.$resizableContainer.show();
		return documentHeight - (controlsHeight + padding);
	};

	/**
	 *
	 * @param {String} value
	 */
	ViewPage.addCustomWidthOption = function(value) {
		ViewPage.$widthSelector.prepend('<option id="customOption" value="' + value + '">' + ViewPage.getOptionLabel(value) + '</option>');
	};

	/**
	 *
	 * @param {String} data
	 * @returns {String}
	 */
	ViewPage.getOptionLabel = function(data) {
		data = data.split('|');
		return data[0] + 'px ' + (data[1] ? '× ' + data[1] + 'px ' : '') + TYPO3.lang['customWidth'];
	};

	/**
	 *
	 * @param {String} url
	 * @returns {{}}
	 */
	ViewPage.getUrlVars = function(url) {
		var vars = {};
		var hash;
		var hashes = url.slice(url.indexOf('?') + 1).split('&');
		for (var i = 0; i < hashes.length; i ++) {
			hash = hashes[i].split('=');
			vars[hash[0]] = hash[1];
		}
		return vars;
	};

	$(ViewPage.initialize);

	return ViewPage;
});
