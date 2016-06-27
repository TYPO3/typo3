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
 * Module: TYPO3/CMS/Rtehtmlarea/RteLinkBrowser
 * LinkBrowser communication with parent window
 */
define(['jquery', 'TYPO3/CMS/Recordlist/LinkBrowser'], function($, LinkBrowser) {
	'use strict';

	/**
	 *
	 * @type {{plugin: null, HTMLArea: null, siteUrl: string, defaultLinkTarget: string}}
	 * @exports TYPO3/CMS/Rtehtmlarea/RteLinkBrowser
	 */
	var RteLinkBrowser = {
		plugin: null,
		HTMLArea: null,
		siteUrl: '',
		defaultLinkTarget: ''
	};

	RteLinkBrowser.changeClassSelector = function() {
		if (document.lclassform.lclass) {
			document.lclassform.lclass.value = document.lclassform.lclass.options[document.lclassform.lclass.selectedIndex].value;
			if (document.lclassform.lclass.value && RteLinkBrowser.HTMLArea.classesAnchorSetup) {
				for (var i = RteLinkBrowser.HTMLArea.classesAnchorSetup.length; --i >= 0;) {
					var anchorClass = RteLinkBrowser.HTMLArea.classesAnchorSetup[i];
					if (anchorClass['name'] === document.lclassform.lclass.value) {
						if (anchorClass['titleText'] && document.ltitleform.ltitle) {
							document.ltitleform.ltitle.value = anchorClass['titleText'];
							document.getElementById('rtehtmlarea-browse-links-title-readonly').innerHTML = anchorClass['titleText'];
						}
						if (typeof anchorClass['target'] !== 'undefined') {
							if (document.ltargetform.ltarget) {
								document.ltargetform.ltarget.value = anchorClass['target'];
							}
						} else if (document.ltargetform.ltarget && document.getElementById('ltargetrow').style.display === 'none') {
							// Reset target to default if field is not displayed and class has no configured target
							document.ltargetform.ltarget.value = RteLinkBrowser.defaultLinkTarget;
						}
						break;
					}

				}
			}
		}
	};

	RteLinkBrowser.handleRelAttrib = function() {
		// @todo The rel field can be handled as a normal link attribute, at least lets check for that.
		LinkBrowser.setAdditionalLinkAttribute('rel', '');
	};

	/**
	 *
	 */
	RteLinkBrowser.initialize = function() {
		RteLinkBrowser.plugin = window.parent.RTEarea[LinkBrowser.urlParameters.editorNo].editor.getPlugin("TYPO3Link");
		RteLinkBrowser.HTMLArea = window.parent.HTMLArea;

		$.extend(RteLinkBrowser, $('body').data());

		$('.t3js-removeCurrentLink').on('click', function(event) {
			event.preventDefault();
			RteLinkBrowser.plugin.unLink();
		});

		$('.t3js-class-selector').on('change', RteLinkBrowser.changeClassSelector);
	};

	LinkBrowser.finalizeFunction = function(input) {
		var attributes = LinkBrowser.getLinkAttributeValues();
		var curTitle = attributes.title ? attributes.title : '';
		var curClass = attributes.class ? attributes.class : '';
		var curTarget = attributes.target ? attributes.target : '';
		var curParams = attributes.params ? attributes.params : '';
		delete attributes.title;
		delete attributes.class;
		delete attributes.target;
		delete attributes.params;

		// replace page: prefix
		if (input.indexOf('page:') === 0) {
			input = 'id=' + input.substr(5);
		}

		// if there is no handler keyword (mailto:, record:, etc) or an external link, we always prepend the siteUrl
		if (!/^\w+:/.test(input) && !attributes['data-htmlarea-external']) {
			input = RteLinkBrowser.siteUrl + '?' + input;
		}

        RteLinkBrowser.plugin.createLink(
			input + curParams,
			curTarget,
			curClass,
			curTitle,
			attributes
		);
	};

	$(RteLinkBrowser.initialize);

	return RteLinkBrowser;
});
