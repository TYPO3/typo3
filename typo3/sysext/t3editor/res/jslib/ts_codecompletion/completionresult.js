/***************************************************************
* Copyright notice
*
* (c) 2008-2010 Stephan Petzl <spetzl@gmx.at> and Christian Kartnig <office@hahnepeter.de>
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
* A copy is found in the textfile GPL.txt and important notices to the license
* from the author is found in LICENSE.txt distributed with these scripts.
*
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * @fileoverview contains the CompletionResult class
 */

/**
 * @class this class post-processes the result from the codecompletion, so that it can be
 * displayed in the next step.
 * @constructor
 * @param tsRef the TsRef Tree
 * @param tsTreeNode the current Node in the codetree built by the parser
 * @return a new CompletionResult instance
 */
var CompletionResult = function(tsRef,tsTreeNode) {
	var currentTsTreeNode = tsTreeNode;
	var tsRef = tsRef;

	/**
	 * returns the type of the currentTsTreeNode
	 */
	this.getType = function() {
		var val = currentTsTreeNode.getValue();
		if (tsRef.isType(val)) {
			return tsRef.getType(val);
		} else {
			return null;
		}
	}

	/**
	 * returns a list of possible path completions (proposals), which is:
	 * a list of the children of the current TsTreeNode (= userdefined properties)
	 * and a list of properties allowed for the current object in the TsRef
	 * remove all words from list that don't start with the string in filter
	 * @param {String} filter beginning of the words contained in the proposal list
	 * @returns an Array of Proposals
	 */
	this.getFilteredProposals = function(filter) {

		var defined = new Array();
		var propArr = new Array();
		var childNodes = currentTsTreeNode.getChildNodes();
		var value = currentTsTreeNode.getValue();
		// first get the childNodes of the Node (=properties defined by the user)
		for (key in childNodes) {
			if (typeof(childNodes[key].value) != "undefined" && childNodes[key].value != null) {
				propObj = new Object();
				propObj.word = key;
				if(tsRef.typeHasProperty(value,childNodes[key].name)){
					propObj.cssClass = 'definedTSREFProperty';
					propObj.type = childNodes[key].value;
				} else {
					propObj.cssClass = 'userProperty';
					if (tsRef.isType(childNodes[key].value)) {
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
		var props = tsRef.getPropertiesFromTypeId(currentTsTreeNode.getValue());
		for (key in props) {
			// show just the TSREF properties - no properties of the array-prototype and no properties which have been defined by the user
			if (props[key].value != null && defined[key]!=true) {
				propObj = new Object();
				propObj.word = key;
				propObj.cssClass = 'undefinedTSREFProperty';
				propObj.type = props[key].value;
				propArr.push(propObj);
			}
		}

		var result = [];
		var wordBeginning = "";
		for (var i=0; i < propArr.length;i++) {
			wordBeginning = propArr[i].word.substring(0, filter.length);
			if (filter == "" || filter == null || wordBeginning.toLowerCase() == filter.toLowerCase()) {
				result.push(propArr[i]);
			}
		}
		return result;
	}
}
