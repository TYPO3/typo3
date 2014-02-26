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
 * Main logic for resizing the view of the frame
 */
define(['jquery', 'jquery-ui/resizable'], function($) {
	"use strict";

	// fetch the storage from the outer frame
	var Storage = top.TYPO3.Storage;

	var ViewPage = {
		resizableContainerIdentifier: '#resizeable',
		widthSelectorIdentifier: '#width',
		storagePrefix: 'moduleData.web_view.States.'
	};

	ViewPage.initialize = function() {
		var me = this;
		me.$iframe = $('#tx_viewpage_iframe');
		me.$languageSelector = $('#language');
		me.$resizableContainer = $(me.resizableContainerIdentifier);
		me.$widthSelector = $(me.widthSelectorIdentifier);

		// Add event to width selector so the container is resized
		$(document).on('change', me.widthSelectorIdentifier, function() {
			var value = me.$widthSelector.val();
			if (value) {
				value = value.split('|');
				me.$resizableContainer.animate({
					width:  value[0],
					height: value[1] || '100%'
				});
				Storage.Persistent.set(me.storagePrefix + 'widthSelectorValue', value[0] + '|' + (value[1] || '100%'));
			}
		});

		// Restore custom selector
		var storedCustomWidth = Storage.Persistent.get(me.storagePrefix + 'widthSelectorCustomValue');
		// Check for the " symbol is done in order to avoid problems with the old (non-jQuery) syntax which might be stored inside
		// the UC from previous versions, can be removed with TYPO3 CMS9 again
		if (storedCustomWidth && storedCustomWidth.indexOf('"') === -1) {
			// add custom selector if stored value is not there
			if (me.$widthSelector.find('option[value="' + storedCustomWidth + '"]').length === 0) {
				me.addCustomWidthOption(storedCustomWidth);
			}
		}

		// Re-select stored value
		var storedWidth = Storage.Persistent.get(me.storagePrefix + 'widthSelectorValue');
		// Check for the " symbol is done in order to avoid problems with the old (non-jQuery) syntax which might be stored inside
		// the UC from previous versions, can be removed with TYPO3 CMS9 again
		if (storedWidth && storedWidth.indexOf('"') === -1) {
			me.$widthSelector.val(storedWidth).trigger('change');
		}

		// Initialize the jQuery UI Resizable plugin
		me.$resizableContainer.resizable({
			handles: 'e, se, s'
		});

		// Create and select custom option
		me.$resizableContainer.on('resizestart', function() {
			// Check custom option is there, if not, add it
			if (me.$widthSelector.find('#customOption').length === 0) {
				me.addCustomWidthOption('100%|100%');
			}
			// Select the custom option
			me.$widthSelector.find('#customOption').prop('selected', true);

			// Add iframe overlay to prevent loosing the mouse focus to the iframe while resizing fast
			$(this).append('<div id="iframeCover" style="z-index:99;position:absolute;width:100%;top:0;left:0;height:100%;"></div>');
		});

		me.$resizableContainer.on('resize', function(evt, ui) {
			// Update custom option
			var value = ui.size.width + '|' + ui.size.height;
			var label = me.getOptionLabel(value);
			me.$widthSelector.find('#customOption').text(label).val(value);
		});

		me.$resizableContainer.on('resizestop', function() {
			Storage.Persistent.set(me.storagePrefix + 'widthSelectorCustomValue', me.$widthSelector.val()).done(function() {
				Storage.Persistent.set(me.storagePrefix + 'widthSelectorValue', me.$widthSelector.val());
			});

			// Remove iframe overlay
			$('#iframeCover').remove();
		});

		// select stored language
		var storedLanguage = Storage.Persistent.get(me.storagePrefix + 'languageSelectorValue');
		if (storedLanguage) {
			// select it
			me.$languageSelector.val(storedLanguage);
		}

		// Add event to language selector
		me.$languageSelector.on('change',function() {
			var iframeUrl = me.$iframe.attr('src');
			var iframeParameters = ViewPage.getUrlVars(iframeUrl);
			// change language
			iframeParameters.L = me.$languageSelector.val();
			var newIframeUrl = iframeUrl.slice(0, iframeUrl.indexOf('?') + 1) + $.param(iframeParameters);
			// load new url into iframe
			me.$iframe.attr('src', newIframeUrl);
			Storage.Persistent.set(me.storagePrefix + 'languageSelectorValue', me.$languageSelector.val());
		});
	};

	ViewPage.addCustomWidthOption = function(value) {
		ViewPage.$widthSelector.prepend('<option id="customOption" value="' + value + '">' + ViewPage.getOptionLabel(value) + '</option>');
	};

	ViewPage.getOptionLabel = function(data) {
		data = data.split('|');
		return data[0] + 'px ' + (data[1] ? '× ' + data[1] + 'px ' : '') + TYPO3.lang['customWidth'];
	};

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

	$(document).ready(function() {
		ViewPage.initialize();
	});

	return ViewPage;
});
