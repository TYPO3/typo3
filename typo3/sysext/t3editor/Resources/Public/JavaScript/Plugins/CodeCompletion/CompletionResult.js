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
 * Module: TYPO3/CMS/T3editor/CodeCompletion/CompletionResult
 * Contains the CompletionResult class
 */
define(['jquery'], function ($) {
	/**
	 *
	 * @type {{tsRef: null, tsTreeNode: null}}
	 * @exports TYPO3/CMS/T3editor/CodeCompletion/CompletionResult
	 */
	var CompletionResult = {
		tsRef: null,
		tsTreeNode: null
	};

	/**
	 *
	 * @param {Object} config
	 * @returns {{tsRef: null, tsTreeNode: null}}
	 */
	CompletionResult.init = function(config) {
		CompletionResult.tsRef = config.tsRef;
		CompletionResult.tsTreeNode = config.tsTreeNode;

		return CompletionResult;
	};

	/**
	 * returns the type of the currentTsTreeNode
	 *
	 * @returns {*}
	 */
	CompletionResult.getType = function() {
		var val = CompletionResult.tsTreeNode.getValue();
		if (CompletionResult.tsRef.isType(val)) {
			return CompletionResult.tsRef.getType(val);
		}
		return null;
	};

	/**
	 * returns a list of possible path completions (proposals), which is:
	 * a list of the children of the current TsTreeNode (= userdefined properties)
	 * and a list of properties allowed for the current object in the TsRef
	 * remove all words from list that don't start with the string in filter
	 *
	 * @param {String} filter beginning of the words contained in the proposal list
	 * @return {Array} an Array of Proposals
	 */
	CompletionResult.getFilteredProposals = function(filter) {
		var defined = [],
			propArr = [],
			childNodes = CompletionResult.tsTreeNode.getChildNodes(),
			value = CompletionResult.tsTreeNode.getValue();

		// first get the childNodes of the Node (=properties defined by the user)
		for (var key in childNodes) {
			if (typeof childNodes[key].value !== 'undefined' && childNodes[key].value !== null) {
				var propObj = {};
				propObj.word = key;
				if (CompletionResult.tsRef.typeHasProperty(value, childNodes[key].name)) {
					CompletionResult.tsRef.cssClass = 'definedTSREFProperty';
					propObj.type = childNodes[key].value;
				} else {
					propObj.cssClass = 'userProperty';
					if (CompletionResult.tsRef.isType(childNodes[key].value)) {
						propObj.type = childNodes[key].value;
					} else {
						propObj.type = '';
					}
				}
				propArr.push(propObj);
				defined[key] = true;
			}
		}

		// then get the tsref properties
		var props = CompletionResult.tsRef.getPropertiesFromTypeId(CompletionResult.tsTreeNode.getValue());
		for (var key in props) {
			// show just the TSREF properties - no properties of the array-prototype and no properties which have been defined by the user
			if (typeof props[key].value !== 'undefined' && defined[key] !== true) {
				var propObj = {};
				propObj.word = key;
				propObj.cssClass = 'undefinedTSREFProperty';
				propObj.type = props[key].value;
				propArr.push(propObj);
			}
		}

		var result = [],
			wordBeginning = '';

		for (var i = 0; i < propArr.length; i++) {
			if (filter.length === 0) {
				result.push(propArr[i]);
				continue;
			}
			wordBeginning = propArr[i].word.substring(0, filter.length);
			if (wordBeginning.toLowerCase() === filter.toLowerCase()) {
				result.push(propArr[i]);
			}
		}
		return result;
	};

	return CompletionResult;
});
