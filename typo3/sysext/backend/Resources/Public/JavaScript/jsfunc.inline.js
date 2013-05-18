/*<![CDATA[*/

/***************************************************************
*  Inline-Relational-Record Editing
*
*
*
*  Copyright notice
*
*  (c) 2006-2011 Oliver Hader <oh@inpublica.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

var inline = {
	classVisible: 't3-form-field-container-inline-visible',
	classCollapsed: 't3-form-field-container-inline-collapsed',
	structureSeparator: '-',
	flexFormSeparator: '---',
	flexFormSubstitute: ':',
	prependFormFieldNames: 'data',
	noTitleString: '[No title]',
	lockedAjaxMethod: {},
	sourcesLoaded: {},
	data: {},
	isLoading: false,

	addToDataArray: function(object) {
		$H(object).each(function(pair) {
			inline.data[pair.key] = $H(inline.data[pair.key]).merge(pair.value).toObject();
		});
	},
	setPrependFormFieldNames: function(value) {
		this.prependFormFieldNames = value;
	},
	setNoTitleString: function(value) {
		this.noTitleString = value;
	},
	toggleEvent: function(event) {
		var triggerElement = TYPO3.jQuery(event.target);
		if (triggerElement.parents('.t3-form-field-header-inline-ctrl').length == 1) {
			return;
		}

		var recordHeader = TYPO3.jQuery(this);
		inline.expandCollapseRecord(
			recordHeader.attr('id').replace('_header', ''),
			recordHeader.attr('data-expandSingle'),
			recordHeader.attr('data-returnURL')
		);
	},
	expandCollapseRecord: function(objectId, expandSingle, returnURL) {
		var currentUid = this.parseObjectId('none', objectId, 1);
		var objectPrefix = this.parseObjectId('full', objectId, 0, 1);
		var escapedObjectId = this.escapeObjectId(objectId);

		var currentObject = TYPO3.jQuery('#' + escapedObjectId + '_div');
			// if content is not loaded yet, get it now from server
		if((TYPO3.jQuery('#' + escapedObjectId + '_fields') && $("irre-loading-indicator" + objectId)) || inline.isLoading) {
			return false;
		} else if ($(objectId + '_fields') && $(objectId + '_fields').innerHTML.substr(0,16) == '<!--notloaded-->') {
			inline.isLoading = true;
				// add loading-indicator
			if (TYPO3.jQuery('#' + escapedObjectId + '_icon')) {
				TYPO3.jQuery('#' + escapedObjectId + '_icon').hide();
				TYPO3.jQuery('#' + escapedObjectId + '_iconcontainer').addClass('loading-indicator');
			}
			return this.getRecordDetails(objectId, returnURL);
		}

		var isCollapsed = currentObject.hasClass(this.classCollapsed);
		var collapse = new Array();
		var expand = new Array();

			// if only a single record should be visibly for that set of records
			// and the record clicked itself is no visible, collapse all others
		if (expandSingle && currentObject.hasClass(this.classCollapsed)) {
			collapse = this.collapseAllRecords(objectId, objectPrefix, currentUid);
		}

		inline.toggleElement(objectId);

		if (this.isNewRecord(objectId)) {
			this.updateExpandedCollapsedStateLocally(objectId, isCollapsed);
		} else if (isCollapsed) {
			expand.push(currentUid);
		} else if (!isCollapsed) {
			collapse.push(currentUid);
		}

		this.setExpandedCollapsedState(objectId, expand.join(','), collapse.join(','));

		return false;
	},

	toggleElement: function(objectId) {
		var escapedObjectId = this.escapeObjectId(objectId);
		var jQueryObject = TYPO3.jQuery('#' + escapedObjectId + '_div');

		if (jQueryObject.hasClass(this.classCollapsed)) {
			jQueryObject.removeClass(this.classCollapsed).addClass(this.classVisible);
			jQueryObject.find('#' + escapedObjectId + '_header .t3-icon-irre-collapsed').removeClass('t3-icon-irre-collapsed').addClass('t3-icon-irre-expanded');
		} else {
			jQueryObject.removeClass(this.classVisible).addClass(this.classCollapsed);
			jQueryObject.find('#' + escapedObjectId + '_header .t3-icon-irre-expanded').addClass('t3-icon-irre-collapsed').removeClass('t3-icon-irre-expanded');
		}
	},
	collapseAllRecords: function(objectId, objectPrefix, callingUid) {
			// get the form field, where all records are stored
		var objectName = this.prependFormFieldNames+this.parseObjectId('parts', objectId, 3, 2, true);
		var formObj = document.getElementsByName(objectName);
		var collapse = [];

		if (formObj.length) {
				// the uid of the calling object (last part in objectId)
			var recObjectId = '', escapedRecordObjectId;

			var records = formObj[0].value.split(',');
			for (var i=0; i<records.length; i++) {
				recObjectId = objectPrefix + this.structureSeparator + records[i];
				escapedRecordObjectId = this.escapeObjectId(recObjectId);

				var recordEntry = TYPO3.jQuery('#' + escapedRecordObjectId);
				if (records[i] != callingUid && recordEntry.hasClass(this.classVisible)) {
					TYPO3.jQuery('#' + escapedRecordObjectId + '_div').removeClass(this.classVisible).addClass(this.classCollapsed);
					if (this.isNewRecord(recObjectId)) {
						this.updateExpandedCollapsedStateLocally(recObjectId, 0);
					} else {
						collapse.push(records[i]);
					}
				}
			}
		}

		return collapse;
	},

	updateExpandedCollapsedStateLocally: function(objectId, value) {
		var ucName = 'uc[inlineView]'+this.parseObjectId('parts', objectId, 3, 2, true);
		var ucFormObj = document.getElementsByName(ucName);
		if (ucFormObj.length) {
			ucFormObj[0].value = value;
		}
	},

	getRecordDetails: function(objectId, returnURL) {
		var context = this.getContext(this.parseObjectId('full', objectId, 0, 1));
		inline.makeAjaxCall('getRecordDetails', [inline.getNumberOfRTE(), objectId, returnURL], true, context);
		return false;
	},

	createNewRecord: function(objectId, recordUid) {
		if (this.isBelowMax(objectId)) {
			var context = this.getContext(objectId);
			if (recordUid) {
				objectId += this.structureSeparator + recordUid;
			}
			this.makeAjaxCall('createNewRecord', [this.getNumberOfRTE(), objectId], true, context);
		} else {
			alert('There are no more relations possible at this moment!');
		}
		return false;
	},

	synchronizeLocalizeRecords: function(objectId, type) {
		var context = this.getContext(objectId);
		var parameters = [this.getNumberOfRTE(), objectId, type];
		this.makeAjaxCall('synchronizeLocalizeRecords', parameters, true, context);
	},

	setExpandedCollapsedState: function(objectId, expand, collapse) {
		var context = this.getContext(objectId);
		this.makeAjaxCall('setExpandedCollapsedState', [objectId, expand, collapse], false, context);
	},

	makeAjaxCall: function(method, params, lock, context) {
		var max, url='', urlParams='', options={};
		if (method && params && params.length && this.lockAjaxMethod(method, lock)) {
			url = TBE_EDITOR.getBackendPath() + 'ajax.php';
			urlParams = '&ajaxID=t3lib_TCEforms_inline::'+method;
			for (var i=0, max=params.length; i<max; i++) {
				urlParams += '&ajax['+i+']='+params[i];
			}
			if (context) {
				urlParams += '&ajax[context]=' + Object.toJSON(context);
			}
			options = {
				method:		'post',
				parameters:	urlParams,
				onSuccess:	function(xhr) { inline.isLoading = false; inline.processAjaxResponse(method, xhr); },
				onFailure:	function(xhr) { inline.isLoading = false; inline.showAjaxFailure(method, xhr); }
			};

			new Ajax.Request(url, options);
		}
	},

	lockAjaxMethod: function(method, lock) {
		if (!lock || !inline.lockedAjaxMethod[method]) {
			inline.lockedAjaxMethod[method] = true;
			return true;
		} else {
			return false;
		}
	},

	unlockAjaxMethod: function(method) {
		inline.lockedAjaxMethod[method] = false;
	},

	processAjaxResponse: function(method, xhr, json) {
		var addTag=null, restart=false, processedCount=0, element=null, errorCatch=[], sourcesWaiting=[];
		if (!json && xhr) {
			json = xhr.responseJSON;
		}
			// If there are elements the should be added to the <HEAD> tag (e.g. for RTEhtmlarea):
		if (json.headData) {
			var head = inline.getDomHeadTag();
			var headTags = inline.getDomHeadChildren(head);
			$A(json.headData).each(function(addTag) {
				if (!restart) {
					if (addTag && (addTag.innerHTML || !inline.searchInDomTags(headTags, addTag))) {
						if (addTag.name=='SCRIPT' && addTag.innerHTML && processedCount) {
							restart = true;
							return false;
						} else {
							if (addTag.name=='SCRIPT' && addTag.innerHTML) {
								try {
									eval(addTag.innerHTML);
								} catch(e) {
									errorCatch.push(e);
								}
							} else {
								element = inline.createNewDomElement(addTag);
									// Set onload handler for external JS scripts:
								if (addTag.name=='SCRIPT' && element.src) {
									element.onload = inline.sourceLoadedHandler(element);
									sourcesWaiting.push(element.src);
								}
								head.appendChild(element);
								processedCount++;
							}
							json.headData.shift();
						}
					}
				}
			});
		}
		if (restart || processedCount) {
			window.setTimeout(function() { inline.reprocessAjaxResponse(method, json, sourcesWaiting); }, 40);
		} else {
			if (method) {
				inline.unlockAjaxMethod(method);
			}
			if (json.scriptCall && json.scriptCall.length) {
				$A(json.scriptCall).each(function(value) { eval(value); });
			}
			TYPO3.TCEFORMS.convertDateFieldsToDatePicker();
		}
	},

		// Check if dynamically added scripts are loaded and restart inline.processAjaxResponse():
	reprocessAjaxResponse: function (method, json, sourcesWaiting) {
		var sourcesLoaded = true;
		if (sourcesWaiting && sourcesWaiting.length) {
			$A(sourcesWaiting).each(function(source) {
				if (!inline.sourcesLoaded[source]) {
					sourcesLoaded = false;
					return false;
				}
			});
		}
		if (sourcesLoaded) {
			$A(sourcesWaiting).each(function(source) {
				delete(inline.sourcesLoaded[source]);
			});
			window.setTimeout(function() { inline.processAjaxResponse(method, null, json); }, 80);
		} else {
			window.setTimeout(function() { inline.reprocessAjaxResponse(method, json, sourcesWaiting); }, 40);
		}
	},

	sourceLoadedHandler: function(element) {
		if (element && element.src) {
			inline.sourcesLoaded[element.src] = true;
		}
	},

	showAjaxFailure: function(method, xhr) {
		inline.unlockAjaxMethod(method);
		alert('Error: '+xhr.status+"\n"+xhr.statusText);
	},

		// foreign_selector: used by selector box (type='select')
	importNewRecord: function(objectId) {
		var selector = $(objectId+'_selector');
		if (selector.selectedIndex != -1) {
			var context = this.getContext(objectId);
			var selectedValue = selector.options[selector.selectedIndex].value;
			if (!this.data.unique || !this.data.unique[objectId]) {
				selector.options[selector.selectedIndex].selected = false;
			}
			this.makeAjaxCall('createNewRecord', [this.getNumberOfRTE(), objectId, selectedValue], true, context);
		}
		return false;
	},

		// foreign_selector: used by element browser (type='group/db')
	importElement: function(objectId, table, uid, type) {
		var context = this.getContext(objectId);
		inline.makeAjaxCall('createNewRecord', [inline.getNumberOfRTE(), objectId, uid], true, context);
	},

	importElementMultiple: function(objectId, table, uidArray, type) {
		uidArray.each(function(uid) {
			inline.delayedImportElement(objectId, table, uid, type);
		});
	},
	delayedImportElement: function(objectId, table, uid, type) {
		if (inline.lockedAjaxMethod['createNewRecord'] == true) {
			window.setTimeout("inline.delayedImportElement('" + objectId + "','" + table + "'," +  uid + ", null );", 300);
		} else {
			inline.importElement(objectId, table, uid, type);
		}
	},
		// Check uniqueness for element browser:
	checkUniqueElement: function(objectId, table, uid, type) {
		if (this.checkUniqueUsed(objectId, uid, table)) {
			return {passed: false,message: 'There is already a relation to the selected element!'};
		} else {
			return {passed: true};
		}
	},

		// Checks if a record was used and should be unique:
	checkUniqueUsed: function(objectId, uid, table) {
		if (this.data.unique && this.data.unique[objectId]) {
			var unique = this.data.unique[objectId];
			var values = $H(unique.used).values();

				// for select: only the uid is stored
			if (unique['type'] == 'select') {
				if (values.indexOf(uid) != -1) {
					return true;
				}

				// for group/db: table and uid is stored in a assoc array
			} else if (unique.type == 'groupdb') {
				for (var i=values.length-1; i>=0; i--) {
						// if the pair table:uid is already used:
					if (values[i].table==table && values[i].uid==uid) {
						return true;
					}
				}
			}
		}
		return false;
	},

	setUniqueElement: function(objectId, table, uid, type, elName) {
		var recordUid = this.parseFormElementName('none', elName, 1, 1);
		// alert(objectId+'/'+table+'/'+uid+'/'+recordUid);
		this.setUnique(objectId, recordUid, uid);
	},
		// Remove all select items already used
		// from a newly retrieved/expanded record
	removeUsed: function(objectId, recordUid) {
		if (this.data.unique && this.data.unique[objectId]) {
			var unique = this.data.unique[objectId];
			if (unique.type == 'select') {
				var formName = this.prependFormFieldNames+this.parseObjectId('parts', objectId, 3, 1, true);
				var formObj = document.getElementsByName(formName);
				var recordObj = document.getElementsByName(this.prependFormFieldNames+'['+unique.table+']['+recordUid+']['+unique.field+']');
				var values = $H(unique.used).values();
				if (recordObj.length) {
					var selectedValue = recordObj[0].options[recordObj[0].selectedIndex].value;
					for (var i=0; i<values.length; i++) {
						if (values[i] != selectedValue) {
							this.removeSelectOption(recordObj[0], values[i]);
						}
					}
				}
			}
		}
	},
		// this function is applied to a newly inserted record by AJAX
		// it removes the used select items, that should be unique
	setUnique: function(objectId, recordUid, selectedValue) {
		if (this.data.unique && this.data.unique[objectId]) {
			var unique = this.data.unique[objectId];
			if (unique.type == 'select') {
				if (!(unique.selector && unique.max == -1)) {
					var formName = this.prependFormFieldNames+this.parseObjectId('parts', objectId, 3, 1, true);
					var formObj = document.getElementsByName(formName);
					var recordObj = document.getElementsByName(this.prependFormFieldNames+'['+unique.table+']['+recordUid+']['+unique.field+']');
					var values = $H(unique.used).values();
					var selector = $(objectId+'_selector');
					if (selector.length) {
							// remove all items from the new select-item which are already used in other children
						if (recordObj.length) {
							for (var i=0; i<values.length; i++) {
								this.removeSelectOption(recordObj[0], values[i]);
							}
								// set the selected item automatically to the first of the remaining items if no selector is used
							if (!unique.selector) {
								selectedValue = recordObj[0].options[0].value;
								recordObj[0].options[0].selected = true;
								this.updateUnique(recordObj[0], objectId, formName, recordUid);
								this.handleChangedField(recordObj[0], objectId+'['+recordUid+']');
							}
						}
						for (var i=0; i<values.length; i++) {
							this.removeSelectOption(selector, values[i]);
						}
						if (typeof this.data.unique[objectId].used.length != 'undefined') {
							this.data.unique[objectId].used = {};
						}
						this.data.unique[objectId].used[recordUid] = selectedValue;
					}
						// remove the newly used item from each select-field of the child records
					if (formObj.length && selectedValue) {
						var records = formObj[0].value.split(',');
						for (var i=0; i<records.length; i++) {
							recordObj = document.getElementsByName(this.prependFormFieldNames+'['+unique.table+']['+records[i]+']['+unique.field+']');
							if (recordObj.length && records[i] != recordUid) {
								this.removeSelectOption(recordObj[0], selectedValue);
							}
						}
					}
				}
			} else if (unique.type == 'groupdb') {
					// add the new record to the used items:
				this.data.unique[objectId].used[recordUid] = {'table':unique.elTable, 'uid':selectedValue};
			}

				// remove used items from a selector-box
			if (unique.selector == 'select' && selectedValue) {
				var selector = $(objectId+'_selector');
				this.removeSelectOption(selector, selectedValue);
				this.data.unique[objectId]['used'][recordUid] = selectedValue;
			}
		}
	},

	domAddNewRecord: function(method, insertObject, objectPrefix, htmlData) {
		if (this.isBelowMax(objectPrefix)) {
			if (method == 'bottom') {
				new Insertion.Bottom(insertObject, htmlData);
			} else if (method == 'after') {
				new Insertion.After(insertObject, htmlData);
			}
		}
	},
	domAddRecordDetails: function(objectId, objectPrefix, expandSingle, htmlData) {
		var hiddenValue, formObj, valueObj;
		var objectDiv = $(objectId + '_fields');
		if (!objectDiv || objectDiv.innerHTML.substr(0,16) != '<!--notloaded-->') {
			return;
		}

		var elName = this.parseObjectId('full', objectId, 2, 0, true);

		formObj = $$('[name="' + elName + '[hidden]_0"]');
		valueObj = $$('[name="' + elName + '[hidden]"]');

			// It might be the case that a child record
			// cannot be hidden at all (no hidden field)
		if (formObj.length && valueObj.length) {
			hiddenValue = formObj[0].checked;
			formObj[0].remove();
			valueObj[0].remove();
		}

			// Update DOM
		objectDiv.update(htmlData);

		formObj = document.getElementsByName(elName + '[hidden]_0');
		valueObj = document.getElementsByName(elName + '[hidden]');

			// Set the hidden value again
		if (formObj.length && valueObj.length) {
			valueObj[0].value = hiddenValue ? 1 : 0;
			formObj[0].checked = hiddenValue;
		}

			// remove loading-indicator
		if ($(objectId + '_icon')) {
			$(objectId + '_iconcontainer').removeClassName('loading-indicator');
			$(objectId + '_icon').show();
		}

			// now that the content is loaded, set the expandState
		this.expandCollapseRecord(objectId, expandSingle);
	},

		// Get script and link elements from head tag:
	getDomHeadChildren: function(head) {
		var headTags = [];
		$$('head script', 'head link').each(function(tag) {
			headTags.push(tag);
		});
		return headTags;
	},

	getDomHeadTag: function() {
		if (document && document.head) {
			return document.head;
		} else {
			var head = $$('head');
			if (head.length) {
				return head[0];
			}
		}
		return false;
	},

		// Search whether elements exist in a given haystack:
	searchInDomTags: function(haystack, needle) {
		var result = false;
		$A(haystack).each(function(element) {
			if (element.nodeName.toUpperCase()==needle.name) {
				var attributesCount = $H(needle.attributes).keys().length;
				var attributesFound = 0;
				$H(needle.attributes).each(function(attribute) {
					if (element.getAttribute && element.getAttribute(attribute.key)==attribute.value) {
						attributesFound++;
					}
				});
				if (attributesFound==attributesCount) {
					result = true;
					return true;
				}
			}
		});
		return result;
	},

		// Create a new DOM element:
	createNewDomElement: function(addTag) {
		var element = document.createElement(addTag.name);
		if (addTag.attributes) {
			$H(addTag.attributes).each(function(attribute) {
				element[attribute.key] = attribute.value;
			});
		}
		return element;
	},

	changeSorting: function(objectId, direction) {
		var objectName = this.prependFormFieldNames+this.parseObjectId('parts', objectId, 3, 2, true);
		var objectPrefix = this.parseObjectId('full', objectId, 0, 1);
		var formObj = document.getElementsByName(objectName);

		if (formObj.length) {
				// the uid of the calling object (last part in objectId)
			var callingUid = this.parseObjectId('none', objectId, 1);
			var records = formObj[0].value.split(',');
			var current = records.indexOf(callingUid);
			var changed = false;

				// move up
			if (direction > 0 && current > 0) {
				records[current] = records[current-1];
				records[current-1] = callingUid;
				changed = true;

				// move down
			} else if (direction < 0 && current < records.length-1) {
				records[current] = records[current+1];
				records[current+1] = callingUid;
				changed = true;
			}

			if (changed) {
				formObj[0].value = records.join(',');
				var cAdj = direction > 0 ? 1 : 0; // adjustment
				$(objectId+'_div').parentNode.insertBefore(
					$(objectPrefix + this.structureSeparator + records[current-cAdj] + '_div'),
					$(objectPrefix + this.structureSeparator + records[current+1-cAdj] + '_div')
				);
				this.redrawSortingButtons(objectPrefix, records);
			}
		}

		return false;
	},

	dragAndDropSorting: function(element) {
		var objectId = element.getAttribute('id').replace(/_records$/, '');
		var objectName = inline.prependFormFieldNames+inline.parseObjectId('parts', objectId, 3, 0, true);
		var formObj = document.getElementsByName(objectName);

		if (formObj.length) {
			var checked = new Array();
			var order = Sortable.sequence(element);
			var records = formObj[0].value.split(',');

				// check if ordered uid is really part of the records
				// virtually deleted items might still be there but ordering shouldn't saved at all on them
			for (var i=0; i<order.length; i++) {
				if (records.indexOf(order[i]) != -1) {
					checked.push(order[i]);
				}
			}

			formObj[0].value = checked.join(',');

			if (inline.data.config && inline.data.config[objectId]) {
				var table = inline.data.config[objectId].table;
				inline.redrawSortingButtons(objectId + inline.structureSeparator + table, checked);
			}
		}
	},

	createDragAndDropSorting: function(objectId) {
		Position.includeScrollOffsets = true;
		Sortable.create(
			objectId,
			{
				format: /^[^_\-](?:[A-Za-z0-9\-\_\.]*)-(.*)_div$/,
				onUpdate: inline.dragAndDropSorting,
				tag: 'div',
				handle: 'sortableHandle',
				overlap: 'vertical',
				constraint: 'vertical'
			}
		);
	},

	destroyDragAndDropSorting: function(objectId) {
		Sortable.destroy(objectId);
	},

	redrawSortingButtons: function(objectPrefix, records) {
		var i;
		var headerObj;
		var sortingObj = new Array();

			// if no records were passed, fetch them from form field
		if (typeof records == 'undefined') {
			records = new Array();
			var objectName = this.prependFormFieldNames+this.parseObjectId('parts', objectPrefix, 3, 1, true);
			var formObj = document.getElementsByName(objectName);
			if (formObj.length) {
				records = formObj[0].value.split(',');
			}
		}

		for (i=0; i<records.length; i++) {
			if (!records[i].length) {
				continue;
			}

			headerObj = $(objectPrefix + this.structureSeparator + records[i] + '_header');
			sortingObj[0] = Element.select(headerObj, '.sortingUp');
			sortingObj[1] = Element.select(headerObj, '.sortingDown');

			if (sortingObj[0].length) {
				sortingObj[0][0].style.visibility = (i == 0 ? 'hidden' : 'visible');
			}
			if (sortingObj[1].length) {
				sortingObj[1][0].style.visibility = (i == records.length-1 ? 'hidden' : 'visible');
			}
		}
	},

	memorizeAddRecord: function(objectPrefix, newUid, afterUid, selectedValue) {
		if (this.isBelowMax(objectPrefix)) {
			var objectName = this.prependFormFieldNames+this.parseObjectId('parts', objectPrefix, 3, 1, true);
			var formObj = document.getElementsByName(objectName);

			if (formObj.length) {
				var records = new Array();
				if (formObj[0].value.length) records = formObj[0].value.split(',');

				if (afterUid) {
					var newRecords = new Array();
					for (var i=0; i<records.length; i++) {
						if (records[i].length) {
							newRecords.push(records[i]);
						}
						if (afterUid == records[i]) {
							newRecords.push(newUid);
						}
					}
					records = newRecords;
				} else {
					records.push(newUid);
				}
				formObj[0].value = records.join(',');
			}

			this.redrawSortingButtons(objectPrefix, records);

			if (this.data.unique && this.data.unique[objectPrefix]) {
				var unique = this.data.unique[objectPrefix];
				this.setUnique(objectPrefix, newUid, selectedValue);
			}
		}

			// if we reached the maximum off possible records after this action, hide the new buttons
		if (!this.isBelowMax(objectPrefix)) {
			var objectParent = this.parseObjectId('full', objectPrefix, 0 , 1);
			var md5 = this.getObjectMD5(objectParent);
			this.hideElementsWithClassName('.inlineNewButton'+(md5 ? '.'+md5 : ''), objectParent);
		}

		if (TBE_EDITOR) {
			TBE_EDITOR.fieldChanged_fName(objectName, formObj);
		}
	},

	memorizeRemoveRecord: function(objectName, removeUid) {
		var formObj = document.getElementsByName(objectName);
		if (formObj.length) {
			var parts = new Array();
			if (formObj[0].value.length) {
				parts = formObj[0].value.split(',');
				parts = parts.without(removeUid);
				formObj[0].value = parts.join(',');
				if (TBE_EDITOR) {
					TBE_EDITOR.fieldChanged_fName(objectName, formObj);
				}
				return parts.length;
			}
		}
		return false;
	},

	updateUnique: function(srcElement, objectPrefix, formName, recordUid) {
		if (this.data.unique && this.data.unique[objectPrefix]) {
			var unique = this.data.unique[objectPrefix];
			var oldValue = unique.used[recordUid];

			if (unique.selector == 'select') {
				var selector = $(objectPrefix+'_selector');
				this.removeSelectOption(selector, srcElement.value);
				if (typeof oldValue != 'undefined') {
					this.readdSelectOption(selector, oldValue, unique);
				}
			}

			if (!(unique.selector && unique.max == -1)) {
				var formObj = document.getElementsByName(formName);
				if (unique && formObj.length) {
					var records = formObj[0].value.split(',');
					var recordObj;
					for (var i=0; i<records.length; i++) {
						recordObj = document.getElementsByName(this.prependFormFieldNames+'['+unique.table+']['+records[i]+']['+unique.field+']');
						if (recordObj.length && recordObj[0] != srcElement) {
							this.removeSelectOption(recordObj[0], srcElement.value);
							if (typeof oldValue != 'undefined') {
								this.readdSelectOption(recordObj[0], oldValue, unique);
							}
						}
					}
					this.data.unique[objectPrefix].used[recordUid] = srcElement.value;
				}
			}
		}
	},

	revertUnique: function(objectPrefix, elName, recordUid) {
		if (this.data.unique && this.data.unique[objectPrefix]) {
			var unique = this.data.unique[objectPrefix];
			var fieldObj = elName ? document.getElementsByName(elName+'['+unique.field+']') : null;

			if (unique.type == 'select') {
				if (fieldObj && fieldObj.length) {
					delete(this.data.unique[objectPrefix].used[recordUid])

					if (unique.selector == 'select') {
						if (!isNaN(fieldObj[0].value)) {
							var selector = $(objectPrefix+'_selector');
							this.readdSelectOption(selector, fieldObj[0].value, unique);
						}
					}

					if (!(unique.selector && unique.max == -1)) {
						var formName = this.prependFormFieldNames+this.parseObjectId('parts', objectPrefix, 3, 1, true);
						var formObj = document.getElementsByName(formName);
						if (formObj.length) {
							var records = formObj[0].value.split(',');
							var recordObj;
								// walk through all inline records on that level and get the select field
							for (var i=0; i<records.length; i++) {
								recordObj = document.getElementsByName(this.prependFormFieldNames+'['+unique.table+']['+records[i]+']['+unique.field+']');
								if (recordObj.length) {
									this.readdSelectOption(recordObj[0], fieldObj[0].value, unique);
								}
							}
						}
					}
				}
			} else if (unique.type == 'groupdb') {
				// alert(objectPrefix+'/'+recordUid);
				delete(this.data.unique[objectPrefix].used[recordUid])
			}
		}
	},

	enableDisableRecord: function(objectId) {
		var elName = this.parseObjectId('full', objectId, 2, 0, true) + '[hidden]';
		var formObj = document.getElementsByName(elName + '_0');
		var valueObj = document.getElementsByName(elName);
		var icon = $(objectId + '_disabled');

		var $container = TYPO3.jQuery('#' + objectId + '_div');

			// It might be the case that there's no hidden field
		if (formObj.length && valueObj.length) {
			formObj[0].click();
			valueObj[0].value = formObj[0].checked ? 1 : 0;
			TBE_EDITOR.fieldChanged_fName(elName, elName);
		}

		if (icon) {
			if (icon.hasClassName('t3-icon-edit-hide')) {
				icon.removeClassName ('t3-icon-edit-hide');
				icon.addClassName('t3-icon-edit-unhide');
				$container.addClass('t3-form-field-container-inline-hidden');
			} else {
				icon.removeClassName ('t3-icon-edit-unhide');
				icon.addClassName('t3-icon-edit-hide');
				$container.removeClass('t3-form-field-container-inline-hidden');
			}
		}

		return false;
	},

	deleteRecord: function(objectId, options) {
		var i, j, inlineRecords, records, childObjectId, childTable;
		var objectPrefix = this.parseObjectId('full', objectId, 0 , 1);
		var elName = this.parseObjectId('full', objectId, 2, 0, true);
		var shortName = this.parseObjectId('parts', objectId, 2, 0, true);
		var recordUid = this.parseObjectId('none', objectId, 1);
		var beforeDeleteIsBelowMax = this.isBelowMax(objectPrefix);

			// revert the unique settings if available
		this.revertUnique(objectPrefix, elName, recordUid);

			// Remove from TBE_EDITOR (required fields, required range, etc.):
		if (TBE_EDITOR && TBE_EDITOR.removeElement) {
			var removeStack = [];
			// Iterate over all child records:
			inlineRecords = Element.select(objectId+'_div', '.inlineRecord');
				// Remove nested child records from TBE_EDITOR required/range checks:
			for (i=inlineRecords.length-1; i>=0; i--) {
				if (inlineRecords[i].value.length) {
					records = inlineRecords[i].value.split(',');
					childObjectId = this.data.map[inlineRecords[i].name];
					childTable = this.data.config[childObjectId].table;
					for (j=records.length-1; j>=0; j--) {
						removeStack.push(this.prependFormFieldNames+'['+childTable+']['+records[j]+']');
					}
				}
			}
			removeStack.push(this.prependFormFieldNames+shortName);
			TBE_EDITOR.removeElementArray(removeStack);
		}

			// Mark this container as deleted
		var deletedRecordContainer = $(objectId + '_div');
		if (deletedRecordContainer) {
			deletedRecordContainer.addClassName('inlineIsDeletedRecord');
		}

			// If the record is new and was never saved before, just remove it from DOM:
		if (this.isNewRecord(objectId) || options && options.forceDirectRemoval) {
			this.fadeAndRemove(objectId+'_div');
			// If the record already exists in storage, mark it to be deleted on clicking the save button:
		} else {
			document.getElementsByName('cmd'+shortName+'[delete]')[0].disabled = false;
			new Effect.Fade(objectId+'_div');
		}

		var recordCount = this.memorizeRemoveRecord(
			this.prependFormFieldNames+this.parseObjectId('parts', objectId, 3, 2, true),
			recordUid
		);

		if (recordCount <= 1) {
			this.destroyDragAndDropSorting(this.parseObjectId('full', objectId, 0 , 2)+'_records');
		}
		this.redrawSortingButtons(objectPrefix);

			// if the NEW-button was hidden and now we can add again new children, show the button
		if (!beforeDeleteIsBelowMax && this.isBelowMax(objectPrefix)) {
			var objectParent = this.parseObjectId('full', objectPrefix, 0 , 1);
			var md5 = this.getObjectMD5(objectParent);
			this.showElementsWithClassName('.inlineNewButton'+(md5 ? '.'+md5 : ''), objectParent);
		}
		return false;
	},

	parsePath: function(path) {
		var backSlash = path.lastIndexOf('\\');
		var normalSlash = path.lastIndexOf('/');

		if (backSlash > 0) {
			path = path.substring(0,backSlash+1);
		} else if (normalSlash > 0) {
			path = path.substring(0,normalSlash+1);
		} else {
			path = '';
		}

		return path;
	},

	parseFormElementName: function(wrap, formElementName, rightCount, skipRight) {
		var idParts = this.splitFormElementName(formElementName);

		if (!wrap) {
			wrap = 'full';
		}
		if (!skipRight) {
			skipRight = 0;
		}

		var elParts = new Array();
		for (var i=0; i<skipRight; i++) {
			idParts.pop();
		}

		if (rightCount > 0) {
			for (var i=0; i<rightCount; i++) {
				elParts.unshift(idParts.pop());
			}
		} else {
			for (var i=0; i<-rightCount; i++) {
				idParts.shift();
			}
			elParts = idParts;
		}

		var elReturn = this.constructFormElementName(wrap, elParts);

		return elReturn;
	},

	splitFormElementName: function(formElementName) {
		// remove left and right side "data[...|...]" -> '...|...'
		formElementName = formElementName.substr(0, formElementName.lastIndexOf(']')).substr(formElementName.indexOf('[')+1);
		var parts = objectId.split('][');

		return parts;
	},

	splitObjectId: function(objectId) {
		objectId = objectId.substr(objectId.indexOf(this.structureSeparator)+1);
		objectId = objectId.split(this.flexFormSeparator).join(this.flexFormSubstitute);
		var parts = objectId.split(this.structureSeparator);

		return parts;
	},

	constructFormElementName: function(wrap, parts) {
		var elReturn;

		if (wrap == 'full') {
			elReturn = this.prependFormFieldNames+'['+parts.join('][')+']';
			elReturn = elReturn.split(this.flexFormSubstitute).join('][');
		} else if (wrap == 'parts') {
			elReturn = '['+parts.join('][')+']';
			elReturn = elReturn.split(this.flexFormSubstitute).join('][');
		} else if (wrap == 'none') {
			elReturn = parts.length > 1 ? parts : parts.join('');
		}

		return elReturn;
	},

	constructObjectId: function(wrap, parts) {
		var elReturn;

		if (wrap == 'full') {
			elReturn = this.prependFormFieldNames+this.structureSeparator+parts.join(this.structureSeparator);
			elReturn = elReturn.split(this.flexFormSubstitute).join(this.flexFormSeparator);
		} else if (wrap == 'parts') {
			elReturn = this.structureSeparator+parts.join(this.structureSeparator);
			elReturn = elReturn.split(this.flexFormSubstitute).join(this.flexFormSeparator);
		} else if (wrap == 'none') {
			elReturn = parts.length > 1 ? parts : parts.join('');
		}

		return elReturn;
	},

	parseObjectId: function(wrap, objectId, rightCount, skipRight, returnAsFormElementName) {
		var idParts = this.splitObjectId(objectId);

		if (!wrap) {
			wrap = 'full';
		}
		if (!skipRight) {
			skipRight = 0;
		}

		var elParts = new Array();
		for (var i=0; i<skipRight; i++) {
			idParts.pop();
		}

		if (rightCount > 0) {
			for (var i=0; i<rightCount; i++) {
				elParts.unshift(idParts.pop());
			}
		} else {
			for (var i=0; i<-rightCount; i++) {
				idParts.shift();
			}
			elParts = idParts;
		}

		if (returnAsFormElementName) {
			var elReturn = this.constructFormElementName(wrap, elParts);
		} else {
			var elReturn = this.constructObjectId(wrap, elParts);
		}

		return elReturn;
	},

	handleChangedField: function(formField, objectId) {
		var formObj;
		if (typeof formField == 'object') {
			formObj = formField;
		} else {
			formObj = document.getElementsByName(formField);
			if (formObj.length) {
				formObj = formObj[0];
			}
		}

		if (formObj != undefined) {
			var value;
			if (formObj.nodeName == 'SELECT') {
				value = formObj.options[formObj.selectedIndex].text;
			} else {
				value = formObj.value;
			}
			$(objectId+'_label').innerHTML = value.length ? value : this.noTitleString;
		}
		return true;
	},

	arrayAssocCount: function(object) {
		var count = 0;
		if (typeof object.length != 'undefined') {
			count = object.length;
		} else {
			for (var i in object) {
				count++;
			}
		}
		return count;
	},

	isBelowMax: function(objectPrefix) {
		var isBelowMax = true;
		var objectName = this.prependFormFieldNames+this.parseObjectId('parts', objectPrefix, 3, 1, true);
		var formObj = document.getElementsByName(objectName);

		if (this.data.config && this.data.config[objectPrefix] && formObj.length) {
			var recordCount = formObj[0].value ? formObj[0].value.split(',').length : 0;
			if (recordCount >= this.data.config[objectPrefix].max) {
				isBelowMax = false;
			}
		}
		if (isBelowMax && this.data.unique && this.data.unique[objectPrefix]) {
			var unique = this.data.unique[objectPrefix];
			if (this.arrayAssocCount(unique.used) >= unique.max && unique.max >= 0) {
				isBelowMax = false;
			}
		}
		return isBelowMax;
	},

	getOptionsHash: function(selectObj) {
		var optionsHash = {};
		for (var i=0; i<selectObj.options.length; i++) {
			optionsHash[selectObj.options[i].value] = i;
		}
		return optionsHash;
	},

	removeSelectOption: function(selectObj, value) {
		var optionsHash = this.getOptionsHash(selectObj);
		if (optionsHash[value] != undefined) {
			selectObj.options[optionsHash[value]] = null;
		}
	},

	readdSelectOption: function(selectObj, value, unique) {
		var index = null;
		var optionsHash = this.getOptionsHash(selectObj);
		var possibleValues = $H(unique.possible).keys();

		for (var possibleValue in unique.possible) {
			if (possibleValue == value) {
				break;
			}
			if (optionsHash[possibleValue] != undefined) {
				index = optionsHash[possibleValue];
			}
		}

		if (index == null) {
			index = 0;
		} else if (index < selectObj.options.length) {
			index++;
		}
			// recreate the <option> tag
		var readdOption = document.createElement('option');
		readdOption.text = unique.possible[value];
		readdOption.value = value;
			// add the <option> at the right position
		selectObj.add(readdOption, document.all ? index : selectObj.options[index]);
	},

	hideElementsWithClassName: function(selector, parentElement) {
		this.setVisibilityOfElementsWithClassName('hide', selector, parentElement);
	},

	showElementsWithClassName: function(selector, parentElement) {
		this.setVisibilityOfElementsWithClassName('show', selector, parentElement);
	},

	setVisibilityOfElementsWithClassName: function(action, selector, parentElement) {
		var domObjects = Selector.findChildElements($(parentElement), [selector]);
		if (action == 'hide') {
			$A(domObjects).each(function(domObject) { new Effect.Fade(domObject); });
		} else if (action == 'show') {
			$A(domObjects).each(function(domObject) { new Effect.Appear(domObject); });
		}
	},

	fadeOutFadeIn: function(objectId) {
		var optIn = { duration:0.5, transition:Effect.Transitions.linear, from:0.50, to:1.00 };
		var optOut = { duration:0.5, transition:Effect.Transitions.linear, from:1.00, to:0.50 };
		optOut.afterFinish = function() {
			new Effect.Opacity(objectId, optIn);
		};
		new Effect.Opacity(objectId, optOut);
	},

	isNewRecord: function(objectId) {
		return $(objectId+'_div') && $(objectId+'_div').hasClassName('inlineIsNewRecord')
			? true
			: false;
	},

		// Find and fix nested of inline and tab levels if a new element was created dynamically (it doesn't know about its nesting):
	findContinuedNestedLevel: function(nested, objectId) {
		if (this.data.nested && this.data.nested[objectId]) {
				// Remove the first element from the new nested stack, it's just a hint:
			nested.shift();
			nested = this.data.nested[objectId].concat(nested);
			return nested;
		} else {
			return nested;
		}
	},

	getNumberOfRTE: function() {
		var number = 0;
		if (typeof RTEarea != 'undefined' && RTEarea.length > 0) {
			number = RTEarea.length-1;
		}
		return number;
  	},

  	getObjectMD5: function(objectPrefix) {
  		var md5 = false;
  		if (this.data.config && this.data.config[objectPrefix] && this.data.config[objectPrefix].md5) {
  			md5 = this.data.config[objectPrefix].md5;
  		}
  		return md5
  	},

  	fadeAndRemove: function(element) {
  		if ($(element)) {
			new Effect.Fade(element, { afterFinish: function() { Element.remove(element); }	});
		}
  	},

	getContext: function(objectId) {
		var result = null;

		if (objectId !== '' && typeof this.data.config[objectId] !== 'undefined' && typeof this.data.config[objectId].context !== 'undefined') {
			result = this.data.config[objectId].context;
		}

		return result;
	},

	/**
	 * Escapes object identifiers to be used in jQuery.
	 *
	 * @param string objectId
	 * @return string
	 */
	escapeObjectId: function(objectId) {
		var escapedObjectId;
		escapedObjectId = objectId.replace(/:/g, '\\:');
		escapedObjectId = objectId.replace(/\./g, '\\.');
		return escapedObjectId;
	}
}

Object.extend(Array.prototype, {
	diff: function(current) {
		var diff = new Array();
		if (this.length == current.length) {
			for (var i=0; i<this.length; i++) {
				if (this[i] !== current[i]) diff.push(i);
			}
		}
		return diff;
	}
});

/*]]>*/
(function($) {
	$(function() {
		$(document).delegate('div.t3-form-field-header-inline', 'click', inline.toggleEvent);
	});
})(TYPO3.jQuery);