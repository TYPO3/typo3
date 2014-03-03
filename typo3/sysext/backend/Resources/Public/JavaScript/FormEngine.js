/**
 * (c) 2013 Benjamin Mack
 * Released under the GPL v2+, part of TYPO3
 *
 * contains all JS functions related to TYPO3 TCEforms/FormEngine
 *
 * there are separate issues in this main object
 *   - functions, related to Element Browser ("Popup Window") and select fields
 *   - filling select fields (by wizard etc) from outside, formerly known via "setFormValueFromBrowseWin"
 *   - select fields: move selected items up and down via buttons, remove items etc
 *   -
 *
 */

// add legacy functions to be accessible in the global scope
var setFormValueOpenBrowser
	,setFormValueFromBrowseWin
	,setHiddenFromList
	,setFormValueManipulate
	,setFormValue_getFObj


define('TYPO3/CMS/Backend/FormEngine', ['jquery'], function ($) {

	// main options
	var FormEngine = {
		formName: TYPO3.settings.FormEngine.formName
		,backPath: TYPO3.settings.FormEngine.backPath
		,openedPopupWindow: null
		,legacyFieldChangedCb: function() { !$.isFunction(TYPO3.settings.FormEngine.legacyFieldChangedCb) || TYPO3.settings.FormEngine.legacyFieldChangedCb(); }
	};


	// functions to connect the db/file browser with this document and the formfields on it!

	/**
	 * opens a popup window with the element browser (browser.php)
	 *
	 * @param mode can be "db" or "file"
	 * @param params additional params for the browser window
	 */
	FormEngine.openPopupWindow = setFormValueOpenBrowser = function(mode, params) {
		var url = FormEngine.backPath + 'browser.php?mode=' + mode + '&bparams=' + params;
		FormEngine.openedPopupWindow = window.open(url, 'Typo3WinBrowser', 'height=650,width=' + (mode == 'db' ? 650 : 600) + ',status=0,menubar=0,resizable=1,scrollbars=1');
		FormEngine.openedPopupWindow.focus();
	};


	/**
	 * properly fills the select field from the popup window (element browser, link browser)
	 * or from a multi-select (two selects side-by-side)
	 * previously known as "setFormValueFromBrowseWin"
	 *
	 * @param fieldName formerly known as "fName" name of the field, like [tt_content][2387][header]
	 * @param value the value to fill in (could be an integer)
	 * @param label the visible name in the selector
	 * @param title the title when hovering over it
	 * @param exclusiveValues if the select field has exclusive options that are not combine-able
	 */
	FormEngine.setSelectOptionFromExternalSource = setFormValueFromBrowseWin = function(fieldName, value, label, title, exclusiveValues) {
		var $originalFieldEl = $fieldEl = FormEngine.getFieldElement(fieldName)
				,isMultiple = false
				,isList = false;

		if ($originalFieldEl.length == 0 || value === '--div--') {
			return;
		}

		// Check if the form object has a "_list" element
		// The "_list" element exists for multiple selection select types
		var $listFieldEl = FormEngine.getFieldElement(fieldName, '_list', true);
		if ($listFieldEl.length > 0) {
			$fieldEl = $listFieldEl;
			isMultiple = ($fieldEl.prop('multiple') && $fieldEl.prop('size') != '1');
			isList = true;
		}

		// clear field before adding value, if configured so (maxitems==1)
		// @todo: clean this code
		if (typeof TBE_EDITOR.clearBeforeSettingFormValueFromBrowseWin[fieldName] != 'undefined') {
			clearSettings = TBE_EDITOR.clearBeforeSettingFormValueFromBrowseWin[fieldName];
			$fieldEl.empty();

				// Clear the upload field
			var filesContainer = document.getElementById(clearSettings.itemFormElID_file);
			if (filesContainer) {
				filesContainer.innerHTML = filesContainer.innerHTML;
			}
		}

		if (isMultiple || isList) {

			// If multiple values are not allowed, clear anything that is in the control already
			if (!isMultiple) {
				$fieldEl.empty();
			}

			// Clear elements if exclusive values are found
			if (exclusiveValues) {
				var m = new RegExp('(^|,)' + value + '($|,)');
				// the new value is exclusive => remove all existing values
				if (exclusiveValues.match(m)) {
					$fieldEl.empty();

				// there is an old value and it was exclusive => it has to be removed
				} else if ($fieldEl.children('option').length == 1) {
					m = new RegExp("(^|,)" + $fieldEl.children('option').prop('value') + "($|,)");
					if (exclusiveValues.match(m)) {
						$fieldEl.empty();
					}
				}
			}

			// Inserting the new element
			var addNewValue = true;

			// check if there is a "_mul" field (a field on the right) and if the field was already added
			var $multipleFieldEl = FormEngine.getFieldElement(fieldName, '_mul', true);
			if ($multipleFieldEl.length == 0 || $multipleFieldEl.val() == 0) {
				$fieldEl.children('option').each(function(k, optionEl) {
					if ($(optionEl).prop('value') == value) {
						addNewValue = false;
						return false;
					}
				});
			}

			// element can be added
			if (addNewValue) {
				// finally add the option
				$fieldEl.append('<option value="' + value + '" title="' + title + '">' + decodeURI(label) + '</option>');

				// set the hidden field
				FormEngine.updateHiddenFieldValueFromSelect($fieldEl, $originalFieldEl);

				// execute the phpcode from $FormEngine->TBE_EDITOR_fieldChanged_func
				FormEngine.legacyFieldChangedCb();
			}

		} else {

			// The incoming value consists of the table name, an underscore and the uid
			// For a single selection field we need only the uid, so we extract it
			var pattern = /_(\\d+)$/
					,result = value.match(pattern);

			if (result != null) {
				value = result[1];
			}

			// Change the selected value
			$fieldEl.val(value);
		}
	};

	/**
	 * sets the value of the hidden field, from the select list, always executed after the select field was updated
	 * previously known as global function setHiddenFromList()
	 *
	 * @param selectFieldEl the select field
	 * @param originalFieldEl the hidden form field
	 */
	FormEngine.updateHiddenFieldValueFromSelect = setHiddenFromList = function(selectFieldEl, originalFieldEl) {
		var selectedValues = [];
		$(selectFieldEl).children('option').each(function() {
			selectedValues.push($(this).prop('value'));
		});

		// make a comma separated list, if it is a multi-select
		// set the values to the final hidden field
		$(originalFieldEl).val(selectedValues.join(','));
	};

	// legacy function, can be removed once this function is not in use anymore
	setFormValueManipulate = function(fName, type, maxLength) {
		var $formEl = FormEngine.getFormElement(fName);
		if ($formEl.length > 0) {
			var formObj = $formEl.get(0);
			var localArray_V = new Array();
			var localArray_L = new Array();
			var localArray_S = new Array();
			var localArray_T = new Array();
			var fObjSel = formObj[fName + '_list'];
			var l = fObjSel.length;
			var c = 0;

			if (type == 'RemoveFirstIfFull') {
				if (maxLength == 1) {
					for (a = 1; a < l; a++) {
						if (fObjSel.options[a].selected != 1) {
							localArray_V[c] = fObjSel.options[a].value;
							localArray_L[c] = fObjSel.options[a].text;
							localArray_S[c] = 0;
							localArray_T[c] = fObjSel.options[a].title;
							c++;
						}
					}
				} else {
					return;
				}
			}

			if ((type=="Remove" && fObjSel.size > 1) || type=="Top" || type=="Bottom") {
				if (type=="Top") {
					for (a=0;a<l;a++) {
						if (fObjSel.options[a].selected==1) {
							localArray_V[c]=fObjSel.options[a].value;
							localArray_L[c]=fObjSel.options[a].text;
							localArray_S[c]=1;
							localArray_T[c] = fObjSel.options[a].title;
							c++;
						}
					}
				}
				for (a=0;a<l;a++) {
					if (fObjSel.options[a].selected!=1) {
						localArray_V[c]=fObjSel.options[a].value;
						localArray_L[c]=fObjSel.options[a].text;
						localArray_S[c]=0;
						localArray_T[c] = fObjSel.options[a].title;
						c++;
					}
				}
				if (type=="Bottom") {
					for (a=0;a<l;a++) {
						if (fObjSel.options[a].selected==1) {
							localArray_V[c]=fObjSel.options[a].value;
							localArray_L[c]=fObjSel.options[a].text;
							localArray_S[c]=1;
							localArray_T[c] = fObjSel.options[a].title;
							c++;
						}
					}
				}
			}
			if (type=="Down") {
				var tC = 0;
				var tA = new Array();

				for (a=0;a<l;a++) {
					if (fObjSel.options[a].selected!=1) {
							// Add non-selected element:
						localArray_V[c]=fObjSel.options[a].value;
						localArray_L[c]=fObjSel.options[a].text;
						localArray_S[c]=0;
						localArray_T[c] = fObjSel.options[a].title;
						c++;

							// Transfer any accumulated and reset:
						if (tA.length > 0) {
							for (aa=0;aa<tA.length;aa++) {
								localArray_V[c]=fObjSel.options[tA[aa]].value;
								localArray_L[c]=fObjSel.options[tA[aa]].text;
								localArray_S[c]=1;
								localArray_T[c] = fObjSel.options[tA[aa]].title;
								c++;
							}

							var tC = 0;
							var tA = new Array();
						}
					} else {
						tA[tC] = a;
						tC++;
					}
				}
					// Transfer any remaining:
				if (tA.length > 0) {
					for (aa=0;aa<tA.length;aa++) {
						localArray_V[c]=fObjSel.options[tA[aa]].value;
						localArray_L[c]=fObjSel.options[tA[aa]].text;
						localArray_S[c]=1;
						localArray_T[c] = fObjSel.options[tA[aa]].title;
						c++;
					}
				}
			}
			if (type=="Up") {
				var tC = 0;
				var tA = new Array();
				var c = l-1;

				for (a=l-1;a>=0;a--) {
					if (fObjSel.options[a].selected!=1) {

							// Add non-selected element:
						localArray_V[c]=fObjSel.options[a].value;
						localArray_L[c]=fObjSel.options[a].text;
						localArray_S[c]=0;
						localArray_T[c] = fObjSel.options[a].title;
						c--;

							// Transfer any accumulated and reset:
						if (tA.length > 0) {
							for (aa=0;aa<tA.length;aa++) {
								localArray_V[c]=fObjSel.options[tA[aa]].value;
								localArray_L[c]=fObjSel.options[tA[aa]].text;
								localArray_S[c]=1;
								localArray_T[c] = fObjSel.options[tA[aa]].title;
								c--;
							}

							var tC = 0;
							var tA = new Array();
						}
					} else {
						tA[tC] = a;
						tC++;
					}
				}
					// Transfer any remaining:
				if (tA.length > 0) {
					for (aa=0;aa<tA.length;aa++) {
						localArray_V[c]=fObjSel.options[tA[aa]].value;
						localArray_L[c]=fObjSel.options[tA[aa]].text;
						localArray_S[c]=1;
						localArray_T[c] = fObjSel.options[tA[aa]].title;
						c--;
					}
				}
				c=l;	// Restore length value in "c"
			}

				// Transfer items in temporary storage to list object:
			fObjSel.length = c;
			for (a = 0; a < c; a++) {
				fObjSel.options[a].value = localArray_V[a];
				fObjSel.options[a].text = localArray_L[a];
				fObjSel.options[a].selected = localArray_S[a];
				fObjSel.options[a].title = localArray_T[a];
			}
			FormEngine.updateHiddenFieldValueFromSelect(fObjSel, formObj[fName]);

			FormEngine.legacyFieldChangedCb();
		}
	};


	/**
	 * legacy function
	 * returns the DOM object for the given form name of the current form,
	 * but only if the given field name is valid, legacy function, use "getFormElement" instead
	 *
	 * @param fieldName the name of the field name
	 * @returns {*|DOMElement}
	 */
	setFormValue_getFObj = function(fieldName) {
		var $formEl = FormEngine.getFormElement(fieldName);
		if ($formEl.length > 0) {
			// return the DOM element of the form object
			return $formEl.get(0);
		} else {
			return null;
		}
	};

	/**
	 * returns a jQuery object for the given form name of the current form,
	 * if the parameter "fieldName" is given, then the form element is only returned if the field name is available
	 * the latter behaviour mirrors the one of the function "setFormValue_getFObj"
	 *
	 * @param fieldName the field name to check for, optional
	 * @returns {*|HTMLElement}
	 */
	FormEngine.getFormElement = function(fieldName) {
		var $formEl = $('form[name="' + FormEngine.formName + '"]:first');
		if (fieldName) {
			var $fieldEl = FormEngine.getFieldElement(fieldName)
					,$listFieldEl = FormEngine.getFieldElement(fieldName, '_list');

			// Take the form object if it is either of type select-one or of type-multiple and it has a "_list" element
			if ($fieldEl.length > 0 &&
				(
					($fieldEl.prop('type') == 'select-one') ||
					($listFieldEl.length > 0 && $listFieldEl.prop('type').match(/select-(one|multiple)/))
				)
			) {
				return $formEl;
			} else {
				console.error('Form fields missing: form: ' + FormEngine.formName + ', field name: ' + fieldName);
				alert('Form field is invalid');
			}
		} else {
			return $formEl;
		}
	};


	/**
	 * returns a jQuery object of the field DOM element of the current form, can also be used to
	 * request an alternative field like "_hr", "_list" or "_mul"
	 *
	 * @param fieldName the name of the field (<input name="fieldName">)
	 * @param appendix optional
	 * @param noFallback if set, then the appendix value is returned no matter if it exists or not
	 * @returns {*|HTMLElement}
	 */
	FormEngine.getFieldElement = function(fieldName, appendix, noFallback) {
		var $formEl = FormEngine.getFormElement();

		// if an appendix is set, return the field with the appendix (like _mul or _list)
		if (appendix) {
			var $fieldEl = $(':input[name="' + fieldName + appendix + '"]', $formEl);
			if ($fieldEl.length > 0 || noFallback === true) {
				return $fieldEl;
			}
		}

		return $(':input[name="' + fieldName + '"]', $formEl);
	};



	/**************************************************
	 * manipulate existing options in a select field
	 **************************************************/

	/**
	 * moves currently selected options from a select field to the very top,
	 * can be multiple entries as well
	 *
	 * @param $fieldEl a jQuery object, containing the select field
	 */
	FormEngine.moveOptionToTop = function($fieldEl) {
		// remove the selected options
		var selectedOptions = $fieldEl.find(':selected').detach();
		// and add them on first position again
		$fieldEl.prepend(selectedOptions);
	};


	/**
	 * moves currently selected options from a select field up by one position,
	 * can be multiple entries as well
	 *
	 * @param $fieldEl a jQuery object, containing the select field
	 */
	FormEngine.moveOptionUp = function($fieldEl) {
		// remove the selected options and add it before the previous sibling
		$.each($fieldEl.find(':selected'), function(k, optionEl) {
			var $optionEl = $(optionEl)
					,$optionBefore = $optionEl.prev();

			// stop if first option to move is already the first one
			if (k == 0 && $optionBefore.length === 0) {
				return false;
			}

			$optionBefore.before($optionEl.detach());
		});
	};


	/**
	 * moves currently selected options from a select field down one position,
	 * can be multiple entries as well
	 *
	 * @param $fieldEl a jQuery object, containing the select field
	 */
	FormEngine.moveOptionDown = function($fieldEl) {
		// remove the selected options and add it after the next sibling
		// however, this time, we need to go from the last to the first
		var selectedOptions = $fieldEl.find(':selected');
		selectedOptions = $.makeArray(selectedOptions);
		selectedOptions.reverse();
		$.each(selectedOptions, function(k, optionEl) {
			var $optionEl = $(optionEl)
					,$optionAfter = $optionEl.next();

			// stop if first option to move is already the last one
			if (k == 0 && $optionAfter.length === 0) {
				return false;
			}

			$optionAfter.after($optionEl.detach());
		});
	};


	/**
	 * moves currently selected options from a select field as the very last entries
	 *
	 * @param $fieldEl a jQuery object, containing the select field
	 */
	FormEngine.moveOptionToBottom = function($fieldEl) {
		// remove the selected options
		var selectedOptions = $fieldEl.find(':selected').detach();
		// and add them on last position again
		$fieldEl.append(selectedOptions);
	};

	/**
	 * removes currently selected options from a select field
	 *
	 * @param $fieldEl a jQuery object, containing the select field
	 */
	FormEngine.removeOption = function($fieldEl) {
		// remove the selected options
		$fieldEl.find(':selected').remove();
	};


	/**
	 * initialize events for all form engine relevant tasks
	 */
	FormEngine.initializeEvents = function() {

		// track the arrows "Up", "Down", "Clear" etc in multi-select boxes
		$(document).on('click', '.t3-btn-moveoption-top, .t3-btn-moveoption-up, .t3-btn-moveoption-down, .t3-btn-moveoption-bottom, .t3-btn-removeoption', function(evt) {
			var $el = $(this)
					,fieldName = $el.data('fieldname')
					,$listFieldEl = FormEngine.getFieldElement(fieldName, '_list');

			if ($listFieldEl.length > 0) {

				if ($el.hasClass('t3-btn-moveoption-top')) {
					FormEngine.moveOptionToTop($listFieldEl);
				} else if ($el.hasClass('t3-btn-moveoption-up')) {
					FormEngine.moveOptionUp($listFieldEl);
				} else if ($el.hasClass('t3-btn-moveoption-down')) {
					FormEngine.moveOptionDown($listFieldEl);
				} else if ($el.hasClass('t3-btn-moveoption-bottom')) {
					FormEngine.moveOptionToBottom($listFieldEl);
				} else if ($el.hasClass('t3-btn-removeoption')) {
					FormEngine.removeOption($listFieldEl);
				}

				// make sure to update the hidden field value when modifying the select value
				FormEngine.updateHiddenFieldValueFromSelect($listFieldEl, FormEngine.getFieldElement(fieldName));
				FormEngine.legacyFieldChangedCb();
			}
		});

		// in multi-select environments with two (e.g. "Access"), on click the item from the right should go to the left
		$(document).on('click', '.t3-form-select-itemstoselect', function(evt) {
			var $el = $(this)
					,fieldName = $el.data('relatedfieldname')
					,exclusiveValues = $el.data('exclusivevalues');

			if (fieldName) {
				// try to add each selected field to the "left" select field
				$el.find(':selected').each(function() {
					var $optionEl = $(this);
					FormEngine.setSelectOptionFromExternalSource(fieldName, $optionEl.prop('value'), $optionEl.text(), $optionEl.prop('title'), exclusiveValues);
				});
			}
		});
	};



	// initialize function, always require possible post-render hooks return the main object
	var initializeModule = function(options) {

		FormEngine.initializeEvents();

		// load required modules to hook in the post initialize function
		if (undefined !== TYPO3.settings.RequireJS && undefined !== TYPO3.settings.RequireJS.PostInitializationModules['TYPO3/CMS/Backend/FormEngine']) {
			$.each(TYPO3.settings.RequireJS.PostInitializationModules['TYPO3/CMS/Backend/FormEngine'], function(pos, moduleName) {
				require([moduleName]);
			});
		}

		// make the form engine object publically visible for other objects in the TYPO3 namespace
		TYPO3.FormEngine = FormEngine;

		// return the object in the global space
		return FormEngine;
	};

	// call the main initialize function and execute the hooks
	return initializeModule();
});
