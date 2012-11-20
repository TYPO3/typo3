/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Contains JavaScript for TYPO3 Core Form generator - AKA "TCEforms"
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @coauthor	Oliver Hader <oh@inpublica.de>
 */


var TBE_EDITOR = {
	/* Example:
		elements: {
			'data-parentPid-table-uid': {
				'field': {
					'range':		[0, 100],
					'rangeImg':		'',
					'required':		true,
					'requiredImg':	''
				}
			}
		},
	*/

	elements: {},
	nested: {'field':{}, 'level':{}},
	ignoreElements: [],
	recentUpdatedElements: {},
	actionChecks: { submit:	[] },

	formname: '',
	formnameUENC: '',
	loadTime: 0,
	isChanged: 0,
	auth_timeout_field: 0,

	backPath: '',
	prependFormFieldNames: 'data',
	prependFormFieldNamesUENC: 'data',
	prependFormFieldNamesCnt: 0,

	isPalettedoc: null,
	doSaveFieldName: 0,

	labels: {},
	images: {
		req: new Image(),
		cm: new Image(),
		sel: new Image(),
		clear: new Image()
	},

	clearBeforeSettingFormValueFromBrowseWin: [],

	// Handling of data structures:
	addElements: function(elements) {
		TBE_EDITOR.recentUpdatedElements = elements;
		TBE_EDITOR.elements = $H(TBE_EDITOR.elements).merge(elements).toObject();
	},
	addNested: function(elements) {
		// Merge data structures:
		if (elements) {
			$H(elements).each(function(element) {
				var levelMax, i, currentLevel, subLevel;
				var nested = element.value;
				if (nested.level && nested.level.length) {
						// If the first level is of type 'inline', it could be created by a AJAX request to IRRE.
						// So, try to get the upper levels this dynamic level is nested in:
					if (typeof inline!='undefined' && nested.level[0][0]=='inline') {
						nested.level = inline.findContinuedNestedLevel(nested.level, nested.level[0][1]);
					}
					levelMax = nested.level.length-1;
					for (i=0; i<=levelMax; i++) {
						currentLevel = TBE_EDITOR.getNestedLevelIdent(nested.level[i]);
						if (typeof TBE_EDITOR.nested.level[currentLevel] == 'undefined') {
							TBE_EDITOR.nested.level[currentLevel] = { 'clean': true, 'item': {}, 'sub': {} };
						}
							// Add next sub level to the current level:
						if (i<levelMax) {
							subLevel = TBE_EDITOR.getNestedLevelIdent(nested.level[i+1]);
							TBE_EDITOR.nested.level[currentLevel].sub[subLevel] = true;
							// Add the current item to the last level in nesting:
						} else {
							TBE_EDITOR.nested.level[currentLevel].item[element.key] = nested.parts;
						}
					}
				}
			});
				// Merge the nested fields:
			TBE_EDITOR.nested.field = $H(TBE_EDITOR.nested.field).merge(elements).toObject();
		}
	},
	removeElement: function(record) {
		if (TBE_EDITOR.elements && TBE_EDITOR.elements[record]) {
				// Inform envolved levels the this record is removed and the missing requirements are resolved:
			$H(TBE_EDITOR.elements[record]).each(
				function(pair) {
					TBE_EDITOR.notifyNested(record+'['+pair.key+']', true);
				}
			);
			delete(TBE_EDITOR.elements[record]);
		}
	},
	removeElementArray: function(removeStack) {
		if (removeStack && removeStack.length) {
			TBE_EDITOR.ignoreElements = removeStack;
			for (var i=removeStack.length; i>=0; i--) {
				TBE_EDITOR.removeElement(removeStack[i]);
			}
			TBE_EDITOR.ignoreElements = [];
		}
	},
	getElement: function(record, field, type) {
		var result = null;
		var element;

		if (TBE_EDITOR.elements && TBE_EDITOR.elements[record] && TBE_EDITOR.elements[record][field]) {
			element = TBE_EDITOR.elements[record][field];
			if (type) {
				if (element[type]) result = element;
			} else {
				result = element;
			}
		}

		return result;
	},
	checkElements: function(type, recentUpdated, record, field) {
		var result = 1;
		var elementName, elementData, elementRecord, elementField;
		var source = (recentUpdated ? TBE_EDITOR.recentUpdatedElements : TBE_EDITOR.elements);

		if (TBE_EDITOR.ignoreElements.length && TBE_EDITOR.ignoreElements.indexOf(record)!=-1) {
			return result;
		}

		if (type) {
			if (record && field) {
				elementName = record+'['+field+']';
				elementData = TBE_EDITOR.getElement(record, field, type);
				if (elementData) {
					if (!TBE_EDITOR.checkElementByType(type, elementName, elementData, recentUpdated)) {
						result = 0;
					}
				}

			} else {
				var elementFieldList, elRecIndex, elRecCnt, elFldIndex, elFldCnt;
				var elementRecordList = $H(source).keys();
				for (elRecIndex=0, elRecCnt=elementRecordList.length; elRecIndex<elRecCnt; elRecIndex++) {
					elementRecord = elementRecordList[elRecIndex];
					elementFieldList = $H(source[elementRecord]).keys();
					for (elFldIndex=0, elFldCnt=elementFieldList.length; elFldIndex<elFldCnt; elFldIndex++) {
						elementField = elementFieldList[elFldIndex];
						elementData = TBE_EDITOR.getElement(elementRecord, elementField, type);
						if (elementData) {
							elementName = elementRecord+'['+elementField+']';
							if (!TBE_EDITOR.checkElementByType(type, elementName, elementData, recentUpdated)) {
								result = 0;
							}
						}
					}
				}
			}
		}

		return result;
	},
	checkElementByType: function(type, elementName, elementData, autoNotify) {
		var form, result = 1;

		if (type) {
			if (type == 'required') {
				form = document[TBE_EDITOR.formname][elementName];
				if (form) {
						// Check if we are within a deleted inline element
					var testNode = $(form.parentNode);
					while(testNode) {
						if (testNode.hasClassName && testNode.hasClassName('inlineIsDeletedRecord')) {
							return result;
						}
						testNode = $(testNode.parentNode);
					}

					var value = form.value;
					if (!value || elementData.additional && elementData.additional.isPositiveNumber && (isNaN(value) || Number(value) <= 0)) {
						result = 0;
						if (autoNotify) {
							TBE_EDITOR.setImage('req_'+elementData.requiredImg, TBE_EDITOR.images.req);
							TBE_EDITOR.notifyNested(elementName, false);
						}
					}
				}
			} else if (type == 'range' && elementData.range) {
				var numberOfElements = 0;
				form = document[TBE_EDITOR.formname][elementName+'_list'];
				if (!form) {
						// special treatment for IRRE fields:
					var tempObj = document[TBE_EDITOR.formname][elementName];
					if (tempObj && (Element.hasClassName(tempObj, 'inlineRecord') || Element.hasClassName(tempObj, 'treeRecord'))) {
						form = tempObj.value ? tempObj.value.split(',') : [];
						numberOfElements = form.length;
					}

				} else {
						// special treatment for file uploads
					var tempObj = document[TBE_EDITOR.formname][elementName.replace(/^data/, 'data_files')];
					numberOfElements = form.length;

					if (tempObj && tempObj.type == 'file' && tempObj.value) {
						numberOfElements++; // Add new uploaded file to the number of elements
					}
				}

				if (!TBE_EDITOR.checkRange(numberOfElements, elementData.range[0], elementData.range[1])) {
					result = 0;
					if (autoNotify) {
						TBE_EDITOR.setImage('req_'+elementData.rangeImg, TBE_EDITOR.images.req);
						TBE_EDITOR.notifyNested(elementName, false);
					}
				}
			}
		}

		return result;
	},
	// Notify tabs and inline levels with nested requiredFields/requiredElements:
	notifyNested: function(elementName, resolved) {
		if (TBE_EDITOR.nested.field[elementName]) {
			var i, nested, element, fieldLevels, fieldLevelIdent, nestedLevelType, nestedLevelName;
			fieldLevels = TBE_EDITOR.nested.field[elementName].level;
			TBE_EDITOR.nestedCache = {};

			for (i=fieldLevels.length-1; i>=0; i--) {
				nestedLevelType = fieldLevels[i][0];
				nestedLevelName = fieldLevels[i][1];
				fieldLevelIdent = TBE_EDITOR.getNestedLevelIdent(fieldLevels[i]);
					// Construct the CSS id strings of the image/icon tags showing the notification:
				if (nestedLevelType == 'tab') {
					element = nestedLevelName+'-REQ';
				} else if (nestedLevelType == 'inline') {
					element = nestedLevelName+'_req';
				} else {
					continue;
				}
					// Set the icons:
				if (resolved) {
					if (TBE_EDITOR.checkNested(fieldLevelIdent)) {
						TBE_EDITOR.setImage(element, TBE_EDITOR.images.clear);
					} else {
						break;
					}
				} else {
					if (TBE_EDITOR.nested.level && TBE_EDITOR.nested.level[fieldLevelIdent]) {
						TBE_EDITOR.nested.level[fieldLevelIdent].clean = false;
					}
					TBE_EDITOR.setImage(element, TBE_EDITOR.images.req);
				}
			}
		}
	},
	// Check all the input fields on a given level of nesting - if only on is unfilled, the whole level is marked as required:
	checkNested: function(nestedLevelIdent) {
		var nestedLevel, isClean;
		if (nestedLevelIdent && TBE_EDITOR.nested.level && TBE_EDITOR.nested.level[nestedLevelIdent]) {
			nestedLevel = TBE_EDITOR.nested.level[nestedLevelIdent];
			if (!nestedLevel.clean) {
				if (typeof nestedLevel.item == 'object') {
					$H(nestedLevel.item).each(
						function(pair) {
							if (isClean || typeof isClean == 'undefined') {
								isClean = (
									TBE_EDITOR.checkElements('required', false, pair.value[0], pair.value[1]) &&
									TBE_EDITOR.checkElements('range', false, pair.value[0], pair.value[1])
								);
							}
						}
					);
					if (typeof isClean != 'undefined' && !isClean) {
						return false;
					}
				}
				if (typeof nestedLevel.sub == 'object') {
					$H(nestedLevel.sub).each(
						function(pair) {
							if (isClean || typeof isClean == 'undefined') {
								isClean = TBE_EDITOR.checkNested(pair.key);
							}
						}
					);
					if (typeof isClean != 'undefined' && !isClean) {
						return false;
					}
				}
					// Store the result, that this level (the fields on this and the sub levels) are clean:
				nestedLevel.clean = true;
			}
		}
		return true;
	},
	getNestedLevelIdent: function(level) {
		return level.join('::');
	},
	addActionChecks: function(type, checks) {
		TBE_EDITOR.actionChecks[type].push(checks);
	},

	// Regular TCEforms JSbottom scripts:
	loginRefreshed: function() {
		var date = new Date();
		TBE_EDITOR.loadTime = Math.floor(date.getTime()/1000);
		if (top.busy && top.busy.loginRefreshed) { top.busy.loginRefreshed(); }
	},
	checkLoginTimeout: function() {
		var date = new Date();
		var theTime = Math.floor(date.getTime()/1000);
		if (theTime > TBE_EDITOR.loadTime+TBE_EDITOR.auth_timeout_field-10) {
			return true;
		}
	},
	fieldChanged_fName: function(fName,el) {
		var idx=2+TBE_EDITOR.prependFormFieldNamesCnt;
		var table = TBE_EDITOR.split(fName, "[", idx);
		var uid = TBE_EDITOR.split(fName, "[", idx+1);
		var field = TBE_EDITOR.split(fName, "[", idx+2);

		table = table.substr(0,table.length-1);
		uid = uid.substr(0,uid.length-1);
		field = field.substr(0,field.length-1);
		TBE_EDITOR.fieldChanged(table,uid,field,el);
	},
	fieldChanged: function(table,uid,field,el) {
		var theField = TBE_EDITOR.prependFormFieldNames+'['+table+']['+uid+']['+field+']';
		var theRecord = TBE_EDITOR.prependFormFieldNames+'['+table+']['+uid+']';
		TBE_EDITOR.isChanged = 1;

			// Set change image:
		var imgObjName = "cm_"+table+"_"+uid+"_"+field;
		TBE_EDITOR.setImage(imgObjName,TBE_EDITOR.images.cm);

			// Set change image
		if (document[TBE_EDITOR.formname][theField] && document[TBE_EDITOR.formname][theField].type=="select-one" && document[TBE_EDITOR.formname][theField+"_selIconVal"]) {
			var imgObjName = "selIcon_"+table+"_"+uid+"_"+field+"_";
			TBE_EDITOR.setImage(imgObjName+document[TBE_EDITOR.formname][theField+"_selIconVal"].value,TBE_EDITOR.images.clear);
			document[TBE_EDITOR.formname][theField+"_selIconVal"].value = document[TBE_EDITOR.formname][theField].selectedIndex;
			TBE_EDITOR.setImage(imgObjName+document[TBE_EDITOR.formname][theField+"_selIconVal"].value,TBE_EDITOR.images.sel);
		}

			// Set required flag:
		var imgReqObjName = "req_"+table+"_"+uid+"_"+field;
		if (TBE_EDITOR.getElement(theRecord,field,'required') && document[TBE_EDITOR.formname][theField]) {
			if (TBE_EDITOR.checkElements('required', false, theRecord, field)) {
				TBE_EDITOR.setImage(imgReqObjName,TBE_EDITOR.images.clear);
				TBE_EDITOR.notifyNested(theField, true);
			} else {
				TBE_EDITOR.setImage(imgReqObjName,TBE_EDITOR.images.req);
				TBE_EDITOR.notifyNested(theField, false);
			}
		}
		if (TBE_EDITOR.getElement(theRecord,field,'range') && document[TBE_EDITOR.formname][theField]) {
			if (TBE_EDITOR.checkElements('range', false, theRecord, field)) {
				TBE_EDITOR.setImage(imgReqObjName,TBE_EDITOR.images.clear);
				TBE_EDITOR.notifyNested(theField, true);
			} else {
				TBE_EDITOR.setImage(imgReqObjName,TBE_EDITOR.images.req);
				TBE_EDITOR.notifyNested(theField, false);
			}
		}

		if (TBE_EDITOR.isPalettedoc) { TBE_EDITOR.setOriginalFormFieldValue(theField) };
	},
	setOriginalFormFieldValue: function(theField) {
		if (TBE_EDITOR.isPalettedoc && (TBE_EDITOR.isPalettedoc).document[TBE_EDITOR.formname] && (TBE_EDITOR.isPalettedoc).document[TBE_EDITOR.formname][theField]) {
			(TBE_EDITOR.isPalettedoc).document[TBE_EDITOR.formname][theField].value = document[TBE_EDITOR.formname][theField].value;
		}
	},
	isFormChanged: function(noAlert) {
		if (TBE_EDITOR.isChanged && !noAlert && confirm(TBE_EDITOR.labels.fieldsChanged)) {
			return 0;
		}
		return TBE_EDITOR.isChanged;
	},
	checkAndDoSubmit: function(sendAlert) {
		if (TBE_EDITOR.checkSubmit(sendAlert)) { TBE_EDITOR.submitForm(); }
	},
	/**
	 * Checks if the form can be submitted according to any possible restrains like required values, item numbers etc.
	 * Returns true if the form can be submitted, otherwise false (and might issue an alert message, if "sendAlert" is 1)
	 * If "sendAlert" is false, no error message will be shown upon false return value (if "1" then it will).
	 * If "sendAlert" is "-1" then the function will ALWAYS return true regardless of constraints (except if login has expired) - this is used in the case where a form field change requests a form update and where it is accepted that constraints are not observed (form layout might change so other fields are shown...)
	 */
	checkSubmit: function(sendAlert) {
		var funcIndex, funcMax, funcRes;
		var OK=1;

		// $this->additionalJS_submit:
		if (TBE_EDITOR.actionChecks && TBE_EDITOR.actionChecks.submit) {
			for (funcIndex=0, funcMax=TBE_EDITOR.actionChecks.submit.length; funcIndex<funcMax; funcIndex++) {
				try {
					eval(TBE_EDITOR.actionChecks.submit[funcIndex]);
				} catch(error) {}
			}
		}

		if(!OK) {
			if (!confirm(unescape("SYSTEM ERROR: One or more Rich Text Editors on the page could not be contacted. This IS an error, although it should not be regular.\nYou can save the form now by pressing OK, but you will loose the Rich Text Editor content if you do.\n\nPlease report the error to your administrator if it persists."))) {
				return false;
			} else {
				OK = 1;
			}
		}
		// $reqLinesCheck
		if (!TBE_EDITOR.checkElements('required', false)) { OK = 0; }
		// $reqRangeCheck
		if (!TBE_EDITOR.checkElements('range', false)) { OK = 0; }

		if (OK || sendAlert==-1) {
			return true;
		} else {
			if(sendAlert) alert(TBE_EDITOR.labels.fieldsMissing);
			return false;
		}
	},
	checkRange: function(numberOfElements, lower, upper) {
			// for backwards compatibility, check if we're dealing with an element as first parameter
		if(typeof numberOfElements == 'object') {
			numberOfElements = numberOfElements.length;
		}

		if (numberOfElements >= lower && numberOfElements <= upper) {
			return true;
		} else {
			return false;
		}
	},
	initRequired: function() {
		// $reqLinesCheck
		TBE_EDITOR.checkElements('required', true);

		// $reqRangeCheck
		TBE_EDITOR.checkElements('range', true);
	},
	setImage: function(name,image) {
		var object;
		if (document[name]) {
			object = document[name];
		} else if (document.getElementById(name)) {
			object = document.getElementById(name);
		}
		if (object) {
			if (typeof image == 'object') {
				document[name].src = image.src;
			} else {
				document[name].src = eval(image+'.src');
			}
		}
	},
	submitForm: function() {
		if (TBE_EDITOR.doSaveFieldName) {
			document[TBE_EDITOR.formname][TBE_EDITOR.doSaveFieldName].value=1;
		}
		document[TBE_EDITOR.formname].submit();
	},
	split: function(theStr1, delim, index) {
		var theStr = ""+theStr1;
		var lengthOfDelim = delim.length;
		sPos = -lengthOfDelim;
		if (index<1) {index=1;}
		for (var a=1; a<index; a++) {
			sPos = theStr.indexOf(delim, sPos+lengthOfDelim);
			if (sPos==-1) { return null; }
		}
		ePos = theStr.indexOf(delim, sPos+lengthOfDelim);
		if(ePos == -1) { ePos = theStr.length; }
		return (theStr.substring(sPos+lengthOfDelim,ePos));
	},
	curSelected: function(theField) {
		var fObjSel = document[TBE_EDITOR.formname][theField];
		var retVal="";
		if (fObjSel) {
			if (fObjSel.type=='select-multiple' || fObjSel.type=='select-one') {
				var l=fObjSel.length;
				for (a=0;a<l;a++) {
					if (fObjSel.options[a].selected==1) {
						retVal+=fObjSel.options[a].value+",";
					}
				}
			}
		}
		return retVal;
	},
	rawurlencode: function(str,maxlen) {
		var output = str;
		if (maxlen)	output = output.substr(0,200);
		output = encodeURIComponent(output);
		return output;
	},
	str_replace: function(match,replace,string) {
		var input = ''+string;
		var matchStr = ''+match;
		if (!matchStr) { return string; }
		var output = '';
		var pointer=0;
		var pos = input.indexOf(matchStr);
		while (pos!=-1) {
			output+=''+input.substr(pointer, pos-pointer)+replace;
			pointer=pos+matchStr.length;
			pos = input.indexOf(match,pos+1);
		}
		output+=''+input.substr(pointer);
		return output;
	},
	toggle_display_states: function(id, state_1, state_2) {
		var node = document.getElementById(id);
		if (node) {
			switch (node.style.display) {
				case state_1:
					node.style.display = state_2;
					break;
				case state_2:
					node.style.display = state_1;
					break;
			}
		}
		return false;
	},

	/**
	 * Determines backend path to be used for e.g. ajax.php
	 * @return string
	 */
	getBackendPath: function() {
		var backendPath = '';
		if (TYPO3) {
			if (TYPO3.configuration && TYPO3.configuration.PATH_typo3) {
				backendPath = TYPO3.configuration.PATH_typo3;
			} else if (TYPO3.settings && TYPO3.settings.PATH_typo3) {
				backendPath = TYPO3.settings.PATH_typo3;
			}
		}
		return backendPath;
	}
};

function typoSetup	() {
	this.passwordDummy = '********';
	this.decimalSign = '.';
}
var TS = new typoSetup();
var evalFunc = new evalFunc();

// backwards compatibility for extensions
var TBE_EDITOR_loginRefreshed = TBE_EDITOR.loginRefreshed;
var TBE_EDITOR_checkLoginTimeout = TBE_EDITOR.checkLoginTimeout;
var TBE_EDITOR_setHiddenContent = TBE_EDITOR.setHiddenContent;
var TBE_EDITOR_isChanged = TBE_EDITOR.isChanged;
var TBE_EDITOR_fieldChanged_fName = TBE_EDITOR.fieldChanged_fName;
var TBE_EDITOR_fieldChanged = TBE_EDITOR.fieldChanged;
var TBE_EDITOR_setOriginalFormFieldValue = TBE_EDITOR.setOriginalFormFieldValue;
var TBE_EDITOR_isFormChanged = TBE_EDITOR.isFormChanged;
var TBE_EDITOR_checkAndDoSubmit = TBE_EDITOR.checkAndDoSubmit;
var TBE_EDITOR_checkSubmit = TBE_EDITOR.checkSubmit;
var TBE_EDITOR_checkRange = TBE_EDITOR.checkRange;
var TBE_EDITOR_initRequired = TBE_EDITOR.initRequired;
var TBE_EDITOR_setImage = TBE_EDITOR.setImage;
var TBE_EDITOR_submitForm = TBE_EDITOR.submitForm;
var TBE_EDITOR_split = TBE_EDITOR.split;
var TBE_EDITOR_curSelected = TBE_EDITOR.curSelected;
var TBE_EDITOR_rawurlencode = TBE_EDITOR.rawurlencode;
var TBE_EDITOR_str_replace = TBE_EDITOR.str_replace;


var typo3form = {
	fieldSetNull: function(fieldName, isNull) {
		if (document[TBE_EDITOR.formname][fieldName]) {
			var formFieldItemWrapper = Element.up(document[TBE_EDITOR.formname][fieldName], '.t3-form-field-item');

			if (isNull) {
				formFieldItemWrapper.addClassName('disabled');
			} else {
				formFieldItemWrapper.removeClassName('disabled');
			}
		}
	},
	fieldSet: function(theField, evallist, is_in, checkbox, checkboxValue) {
		var i;

		if (document[TBE_EDITOR.formname][theField]) {
			var theFObj = new evalFunc_dummy (evallist,is_in, checkbox, checkboxValue);
			var theValue = document[TBE_EDITOR.formname][theField].value;
			if (checkbox && theValue==checkboxValue) {
				document[TBE_EDITOR.formname][theField+"_hr"].value="";
				if (document[TBE_EDITOR.formname][theField+"_cb"])	document[TBE_EDITOR.formname][theField+"_cb"].checked = "";
			} else {
				document[TBE_EDITOR.formname][theField+"_hr"].value = evalFunc.outputObjValue(theFObj, theValue);
				if (document[TBE_EDITOR.formname][theField+"_cb"])	document[TBE_EDITOR.formname][theField+"_cb"].checked = "on";
			}
		}
	},
	fieldGet: function(theField, evallist, is_in, checkbox, checkboxValue, checkbox_off, checkSetValue) {
		if (document[TBE_EDITOR.formname][theField]) {
			var theFObj = new evalFunc_dummy (evallist,is_in, checkbox, checkboxValue);
			if (checkbox_off) {
				if (document[TBE_EDITOR.formname][theField+"_cb"].checked) {
					var split = evallist.split(',');
					for (var i = 0; split.length > i; i++) {
						var el = split[i].replace(/ /g, '');
						if (el == 'datetime' || el == 'date') {
							var now = new Date();
							checkSetValue = Date.parse(now)/1000 - now.getTimezoneOffset()*60;
							break;
						} else if (el == 'time' || el == 'timesec') {
							checkSetValue = evalFunc_getTimeSecs(new Date());
							break;
						}
					}
					document[TBE_EDITOR.formname][theField].value=checkSetValue;
				} else {
					document[TBE_EDITOR.formname][theField].value=checkboxValue;
				}
			}else{
				document[TBE_EDITOR.formname][theField].value = evalFunc.evalObjValue(theFObj, document[TBE_EDITOR.formname][theField+"_hr"].value);
			}
			typo3form.fieldSet(theField, evallist, is_in, checkbox, checkboxValue);
		}
	}
};

// backwards compatibility for extensions
var typo3FormFieldSet = typo3form.fieldSet;
var typo3FormFieldGet = typo3form.fieldGet;
