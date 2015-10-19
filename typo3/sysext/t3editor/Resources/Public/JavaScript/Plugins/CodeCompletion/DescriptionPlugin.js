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
 * Module: TYPO3/CMS/T3editor/CodeCompletion/DescriptionPlugin
 * Descriptionbox plugin for the t3editor-codecompletion which displays the datatype
 * and the desciption for each property displayed in the completionbox
 **/
define([
	'jquery',
	'TYPO3/CMS/T3editor/Plugins/CodeCompletion/TsRef',
	'TYPO3/CMS/T3editor/Plugins/CodeCompletion/TsParser'
], function ($, TsRef, TsParser) {
	/**
	 *
	 * @type {{codeCompleteBox: null, codemirror: null, $descriptionBox: (*|jQuery)}}
	 * @exports TYPO3/CMS/T3editor/CodeCompletion/DescriptionPlugin
	 */
	var DescriptionPlugin = {
		codeCompleteBox: null,
		codemirror: null,
		$descriptionBox: $('<div />', {class: 't3e_descriptionBox'}).hide()
	};

	/**
	 *
	 * @param pluginContext
	 */
	DescriptionPlugin.init = function(pluginContext) {
		DescriptionPlugin.codeCompleteBox = pluginContext.codeCompleteBox;
		DescriptionPlugin.codemirror = pluginContext.codemirror;

		if (DescriptionPlugin.codeCompleteBox.has(DescriptionPlugin.$descriptionBox).length === 0) {
			DescriptionPlugin.codeCompleteBox.append(DescriptionPlugin.$descriptionBox);
		}
	};

	/**
	 *
	 * @param {Object} currWordObj
	 * @param {Object} compResult
	 */
	DescriptionPlugin.afterMouseOver = function(currWordObj, compResult) {
		DescriptionPlugin.refreshBox(currWordObj, compResult);
	};

	/**
	 *
	 * @param {Object} currWordObj
	 * @param {Object} compResult
	 */
	DescriptionPlugin.afterKeyDown = function(currWordObj, compResult) {
		DescriptionPlugin.refreshBox(currWordObj, compResult);
	};

	/**
	 *
	 * @param {Object} currWordObj
	 * @param {Object} compResult
	 */
	DescriptionPlugin.afterKeyUp = function(currWordObj, compResult) {
		DescriptionPlugin.refreshBox(currWordObj, compResult);
	};

	/**
	 *
	 * @param {Object} currWordObj
	 * @param {Object} compResult
	 */
	DescriptionPlugin.afterCCRefresh = function(currWordObj, compResult) {
		DescriptionPlugin.refreshBox(currWordObj, compResult);
	};

	/**
	 *
	 * @param {String} desc
	 */
	DescriptionPlugin.descriptionLoaded = function(desc) {
		$('#TSREF_description').html(desc);
	};

	/**
	 *
	 */
	DescriptionPlugin.endCodeCompletion = function() {
		DescriptionPlugin.$descriptionBox.hide();
	};

	/**
	 *
	 * @param {Object} proposalObj
	 * @param {Object} compResult
	 */
	DescriptionPlugin.refreshBox = function(proposalObj, compResult) {
		var type = compResult.getType();

		if (type && type.properties[proposalObj.word]) {
			// first a container has to be built
			var html =
				'<div class="TSREF_type_label">Object-type: </div>'
				+ '<div class="TSREF_type">'+type.typeId+'</div>'
				+ '<div class="TSREF_type_label">Property-type: </div>'
				+ '<div class="TSREF_type">'+type.properties[proposalObj.word].value
				+ '</div><br />'
				+ '<div class="TSREF_description_label">TSREF-description:</div>'
				+ '<div id="TSREF_description"><span class="fa fa-spin fa-spinner" title="one moment please..."></span></div>';
			DescriptionPlugin.$descriptionBox.html(html);

			var prop = type.properties[proposalObj.word];

			// if there is another request for a description in the queue -> cancel it
			window.clearTimeout(this.lastTimeoutId);
			// add a request for a description onto the queue, but wait for 0.5 seconds
			// (look if user really wants to see the description of this property, if not -> don't load it)
			this.lastTimeoutId = setTimeout(function() {
				prop.getDescription(DescriptionPlugin.descriptionLoaded)
			}, 500);
			DescriptionPlugin.$descriptionBox.show();
		} else if (proposalObj.type) {
			var html =
				'<div class="TSREF_type_label">TSREF-type: </div>'
				+ '<div class="TSREF_type">'+proposalObj.type
				+ '</div><br />';
			DescriptionPlugin.$descriptionBox.html(html);
			DescriptionPlugin.$descriptionBox.show();
		} else {
			DescriptionPlugin.$descriptionBox.html('');
			DescriptionPlugin.$descriptionBox.hide();
		}

		DescriptionPlugin.$descriptionBox.scrollTop(0);
		DescriptionPlugin.$descriptionBox.css({
			overflowY: 'scroll'
		});
		DescriptionPlugin.$descriptionBox.addClass('descriptionBox');

		var addX = 18,
			leftOffset = parseInt(DescriptionPlugin.codeCompleteBox.width()) + addX;

		DescriptionPlugin.$descriptionBox.css({
			left: leftOffset + 'px'
		});

		DescriptionPlugin.$descriptionBox.show();
	};

	return DescriptionPlugin;
});
