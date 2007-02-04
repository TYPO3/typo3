/***************************************************************
*  Copyright notice
*
*  (c) 1999-2006 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @coauthor	Oliver Hader <oh@inpublica.de>
 */


var TBE_EDITOR = {
	/* Example:
		elements: {
			'data[table][uid]': {
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

	// Handling of data structures:
	addElements: function(elements) {
		TBE_EDITOR.recentUpdatedElements = elements;
		TBE_EDITOR.elements = $H(TBE_EDITOR.elements).merge(elements);
	},
	removeElement: function(record) {
		if (TBE_EDITOR.elements && TBE_EDITOR.elements[record]) {
			delete(TBE_EDITOR.elements[record]);
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
		var source = recentUpdated ? TBE_EDITOR.recentUpdatedElements : TBE_EDITOR.elements;

		if (type) {
			if (record && field) {
				elementName = record+'['+field+']';
				elementData = TBE_EDITOR.getElement(record, field, type);
				if (elementData) {
					if (!TBE_EDITOR.checkElementByType(type, elementName, elementData)) result = 0;
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
							if (!TBE_EDITOR.checkElementByType(type, elementName, elementData)) result = 0;
						}
					}
				}
			}
		}
		
		return result;
	},
	checkElementByType: function(type, elementName, elementData) {
		var result = 1;
		
		if (type) {
			if (type == 'required') {
				if (!document[TBE_EDITOR.formname][elementName].value) {
					result = 0;
					TBE_EDITOR.setImage('req_'+elementData.requiredImg, TBE_EDITOR.images.req);
				}
			} else if (type == 'range' && elementData.range) {
				var formObj = document[TBE_EDITOR.formname][elementName+'_list'];
				if (!formObj) {
						// special treatment for IRRE fields:
					var tempObj = document[TBE_EDITOR.formname][elementName];
					if (tempObj && Element.hasClassName(tempObj, 'inlineRecord')) {
						formObj = tempObj.value ? tempObj.value.split(',') : [];
					}
				}
				if (!TBE_EDITOR.checkRange(formObj, elementData.range[0], elementData.range[1])) {
					result = 0;
					TBE_EDITOR.setImage('req_'+elementData.rangeImg, TBE_EDITOR.images.req);
				}
			}
		}
		
		return result;
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
	setHiddenContent: function(RTEcontent,theField) {
		document[TBE_EDITOR.formname][theField].value = RTEcontent;
		alert(document[TBE_EDITOR.formname][theField].value);
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
		if (document[TBE_EDITOR.formname][theField] && document[TBE_EDITOR.formname][theField].type=="select-one" && document[TBE_EDITOR.formname][theField+"_selIconVal"])	{
			var imgObjName = "selIcon_"+table+"_"+uid+"_"+field+"_";
			TBE_EDITOR.setImage(imgObjName+document[TBE_EDITOR.formname][theField+"_selIconVal"].value,TBE_EDITOR.images.clear);
			document[TBE_EDITOR.formname][theField+"_selIconVal"].value = document[TBE_EDITOR.formname][theField].selectedIndex;
			TBE_EDITOR.setImage(imgObjName+document[TBE_EDITOR.formname][theField+"_selIconVal"].value,TBE_EDITOR.images.sel);
		}

			// Set required flag:
		var imgReqObjName = "req_"+table+"_"+uid+"_"+field;
		if (TBE_EDITOR.getElement(theRecord,field,'required') && document[TBE_EDITOR.formname][theField])	{
			if (TBE_EDITOR.checkElements('required', false, theRecord, field)) {
				TBE_EDITOR.setImage(imgReqObjName,TBE_EDITOR.images.clear);
			} else {
				TBE_EDITOR.setImage(imgReqObjName,TBE_EDITOR.images.req);
			}
		}
		if (TBE_EDITOR.getElement(theRecord,field,'range') && document[TBE_EDITOR.formname][theField]) {
			if (TBE_EDITOR.checkElements('range', false, theRecord, field))	{
				TBE_EDITOR.setImage(imgReqObjName,TBE_EDITOR.images.clear);
			} else {
				TBE_EDITOR.setImage(imgReqObjName,TBE_EDITOR.images.req);
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
		if (TBE_EDITOR.checkLoginTimeout() && confirm(TBE_EDITOR.labels.refresh_login)) {
			vHWin=window.open(TBE_EDITOR.backPath+'login_frameset.php?','relogin','height=300,width=400,status=0,menubar=0');
			vHWin.focus();
			return false;
		}
		var OK=1;

		// $this->additionalJS_submit:
		if (TBE_EDITOR.actionChecks && TBE_EDITOR.actionChecks.submit) {
			for (funcIndex=0, funcMax=TBE_EDITOR.actionChecks.submit.length; funcIndex<funcMax; funcIndex++) {
				eval(TBE_EDITOR.actionChecks.submit[funcIndex]);
			}
		}

		if(!OK)	{
			if (!confirm(unescape("SYSTEM ERROR: One or more Rich Text Editors on the page could not be contacted. This IS an error, although it should not be regular.\nYou can save the form now by pressing OK, but you will loose the Rich Text Editor content if you do.\n\nPlease report the error to your administrator if it persists.")))	{
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
	checkRange: function(el,lower,upper) {
		if (el && el.length>=lower && el.length<=upper) {
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
		if (document[name]) {
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
		for (var a=1; a<index; a++)	{
			sPos = theStr.indexOf(delim, sPos+lengthOfDelim);
			if (sPos==-1) { return null; }
		}
		ePos = theStr.indexOf(delim, sPos+lengthOfDelim);
		if(ePos == -1) { ePos = theStr.length; }
		return (theStr.substring(sPos+lengthOfDelim,ePos));
	},
	palUrl: function(inData,fieldList,fieldNum,table,uid,isOnFocus) {
		var url = TBE_EDITOR.backPath+'alt_palette.php?inData='+inData+'&formName='+TBE_EDITOR.formnameUENC+'&prependFormFieldNames='+TBE_EDITOR.prependFormFieldNamesUENC;
		var field = "";
		var theField="";
		for (var a=0; a<fieldNum;a++)	{
			field = TBE_EDITOR.split(fieldList, ",", a+1);
			theField = TBE_EDITOR.prependFormFieldNames+'['+table+']['+uid+']['+field+']';
			if (document[TBE_EDITOR.formname][theField]) url+="&rec["+field+"]="+TBE_EDITOR.rawurlencode(document[TBE_EDITOR.formname][theField].value);
		}
		if (top.topmenuFrame)	{
			top.topmenuFrame.location.href = url+"&backRef="+(top.content.list_frame ? (top.content.list_frame.view_frame ? "top.content.list_frame.view_frame" : "top.content.list_frame") : "top.content");
		} else if (!isOnFocus) {
			var vHWin=window.open(url,"palette","height=300,width=200,status=0,menubar=0,scrollbars=1");
			vHWin.focus();
		}
	},
	curSelected: function(theField)	{
		var fObjSel = document[TBE_EDITOR.formname][theField];
		var retVal="";
		if (fObjSel)	{
			if (fObjSel.type=='select-multiple' || fObjSel.type=='select-one')	{
				var l=fObjSel.length;
				for (a=0;a<l;a++)	{
					if (fObjSel.options[a].selected==1)	{
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
		output = escape(output);
		output = TBE_EDITOR.str_replace("*","%2A", output);
		output = TBE_EDITOR.str_replace("+","%2B", output);
		output = TBE_EDITOR.str_replace("/","%2F", output);
		output = TBE_EDITOR.str_replace("@","%40", output);
		return output;
	},
	str_replace: function(match,replace,string) {
		var input = ''+string;
		var matchStr = ''+match;
		if (!matchStr) { return string; }
		var output = '';
		var pointer=0;
		var pos = input.indexOf(matchStr);
		while (pos!=-1)	{
			output+=''+input.substr(pointer, pos-pointer)+replace;
			pointer=pos+matchStr.length;
			pos = input.indexOf(match,pos+1);
		}
		output+=''+input.substr(pointer);
		return output;
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
var TBE_EDITOR_palUrl = TBE_EDITOR.palUrl;
var TBE_EDITOR_curSelected = TBE_EDITOR.curSelected;
var TBE_EDITOR_rawurlencode = TBE_EDITOR.rawurlencode;
var TBE_EDITOR_str_replace = TBE_EDITOR.str_replace;


var typo3form = {
	fieldSet: function(theField, evallist, is_in, checkbox, checkboxValue) {
		if (document[TBE_EDITOR.formname][theField])	{
			var theFObj = new evalFunc_dummy (evallist,is_in, checkbox, checkboxValue);
			var theValue = document[TBE_EDITOR.formname][theField].value;
			if (checkbox && theValue==checkboxValue)	{
				document[TBE_EDITOR.formname][theField+"_hr"].value="";
				if (document[TBE_EDITOR.formname][theField+"_cb"])	document[TBE_EDITOR.formname][theField+"_cb"].checked = "";
			} else {
				document[TBE_EDITOR.formname][theField+"_hr"].value = evalFunc.outputObjValue(theFObj, theValue);
				if (document[TBE_EDITOR.formname][theField+"_cb"])	document[TBE_EDITOR.formname][theField+"_cb"].checked = "on";
			}
		}
	},
	fieldGet: function(theField, evallist, is_in, checkbox, checkboxValue, checkbox_off, checkSetValue) {
		if (document[TBE_EDITOR.formname][theField])	{
			var theFObj = new evalFunc_dummy (evallist,is_in, checkbox, checkboxValue);
			if (checkbox_off)	{
				if (document[TBE_EDITOR.formname][theField+"_cb"].checked)	{
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
