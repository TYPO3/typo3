/**
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
 * This module is used by the RTE SelectImage module
 */
define('TYPO3/CMS/Rtehtmlarea/Modules/SelectImage', function () {
	'use strict';

	var SelectImage = {
		// The id of the current editor
		editorNo: '',
		// The current action
		act: '',
		// The uid of the language of the content element
		sys_language_content: '',
		// The RTE config parameters
		RTEtsConfigParams: '',
		// The browser parameters
		bparams: '',
		// Whether a class selector should be rendered for the image
		classesImage: false,
		// Some labels localized on the server side
		labels: {},

		/**
		 * Initialize an event handler for dropping an image in WebKit browsers
		 *
		 * @return void
         * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
		 */
		initEventListeners: function() {
			if (typeof console !== 'undefined') {
				console.log('SelectImage.initEventListeners() is deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8');
			}
			require(
				['TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent', 'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event'],
				function (UserAgent, Event) {
					if (UserAgent.isWebKit) {
						Event.one(window.document.body, 'dragend.TYPO3Image', function (event) { SelectImage.Plugin.get().onDrop(event); });
					}
				}
			);
		},

		/**
		 * Jump to the specified url after adding some parameters specific to the RTE context
		 *
		 * @return bool
		 */
		jumpToUrl: function(URL, anchor) {
			var selectedImageRef = SelectImage.CurrentImage.get();
			var add_act = URL.indexOf('act=') === -1 ? '&act=' + this.act : '';
			var add_editorNo = URL.indexOf('editorNo=') === -1 ? '&editorNo=' + this.editorNo : '';
			var add_sys_language_content = URL.indexOf('sys_language_content=') === -1 ? '&sys_language_content=' + this.sys_language_content : '';
			var RTEtsConfigParams = '&RTEtsConfigParams=' + this.RTEtsConfigParams;
			var bparams = URL.indexOf('bparams=') === -1 ? '&bparams=' + this.bparams : '';

			var cur_width = selectedImageRef ? '&cWidth=' + selectedImageRef.style.width : '';
			var cur_height = selectedImageRef ? '&cHeight=' + selectedImageRef.style.height : '';
			var addModifyTab = selectedImageRef ? '&addModifyTab=1' : '';

			window.location.href = URL + add_act + add_editorNo + add_sys_language_content + RTEtsConfigParams + bparams + addModifyTab + cur_width + cur_height + (typeof anchor === 'string' ? anchor : '');
			return false;
		}
	};

	SelectImage.Plugin = {
		/**
		 * Get a reference to the TYPO3Image plugin instance
		 *
		 * @returns {Object} a reference to the plugin instance
		 */
		get: function() {
			return window.parent.RTEarea[SelectImage.editorNo].editor.getPlugin('TYPO3Image');
		}
	};

	/**
	 * Actions on the current image
	 */
	SelectImage.CurrentImage = {

		/**
		 * Get a reference to the current image as established by the plugin
		 *
		 * @return {Object|null} a reference to the current image
		 */
		get: function() {
			var plugin = SelectImage.Plugin.get();
			if (plugin.image) {
				return plugin.image;
			}
			return null;
		},

		/**
		 * Set the properties of the current image based on the data collected in the form
		 *
		 * @return void
		 */
		setProperties: function () {
			var selectedImageRef = this.get();
			if (!selectedImageRef) {
				return;
			}
			var imageData = document.imageData;
			if (imageData.iWidth) {
				if (imageData.iWidth.value && parseInt(imageData.iWidth.value)) {
					selectedImageRef.style.width = "";
					selectedImageRef.width = parseInt(imageData.iWidth.value);
				}
			}
			if (imageData.iHeight) {
				if (imageData.iHeight.value && parseInt(imageData.iHeight.value)) {
					selectedImageRef.style.height = "";
					selectedImageRef.height = parseInt(imageData.iHeight.value);
				}
			}
			if (imageData.iPaddingTop) {
				if (imageData.iPaddingTop.value !== "" && !isNaN(parseInt(imageData.iPaddingTop.value))) {
					selectedImageRef.style.paddingTop = parseInt(imageData.iPaddingTop.value) + "px";
				} else {
					selectedImageRef.style.paddingTop = "";
				}
			}
			if (imageData.iPaddingRight) {
				if (imageData.iPaddingRight.value !== "" && !isNaN(parseInt(imageData.iPaddingRight.value))) {
					selectedImageRef.style.paddingRight = parseInt(imageData.iPaddingRight.value) + "px";
				} else {
					selectedImageRef.style.paddingRight = "";
				}
			}
			if (imageData.iPaddingBottom) {
				if (imageData.iPaddingBottom.value !== "" && !isNaN(parseInt(imageData.iPaddingBottom.value))) {
					selectedImageRef.style.paddingBottom = parseInt(imageData.iPaddingBottom.value) + "px";
				} else {
					selectedImageRef.style.paddingBottom = "";
				}
			}
			if (imageData.iPaddingLeft) {
				if (imageData.iPaddingLeft.value !== "" && !isNaN(parseInt(imageData.iPaddingLeft.value))) {
					selectedImageRef.style.paddingLeft = parseInt(imageData.iPaddingLeft.value) + "px";
				} else {
					selectedImageRef.style.paddingLeft = "";
				}
			}
			if (imageData.iTitle) {
				selectedImageRef.title = imageData.iTitle.value;
			}
			if (imageData.iAlt) {
				selectedImageRef.alt = imageData.iAlt.value;
			}
			if (imageData.iBorder) {
				selectedImageRef.style.borderStyle = "";
				selectedImageRef.style.borderWidth = "";
				selectedImageRef.style.border = "";  // this statement ignored by Mozilla 1.3.1
				selectedImageRef.style.borderTopStyle = "";
				selectedImageRef.style.borderRightStyle = "";
				selectedImageRef.style.borderBottomStyle = "";
				selectedImageRef.style.borderLeftStyle = "";
				selectedImageRef.style.borderTopWidth = "";
				selectedImageRef.style.borderRightWidth = "";
				selectedImageRef.style.borderBottomWidth = "";
				selectedImageRef.style.borderLeftWidth = "";
				if (imageData.iBorder.checked) {
					selectedImageRef.style.borderStyle = "solid";
					selectedImageRef.style.borderWidth = "thin";
				}
				selectedImageRef.removeAttribute("border");
			}
			if (imageData.iFloat) {
				var iFloat = imageData.iFloat.options[imageData.iFloat.selectedIndex].value;
				selectedImageRef.style.cssFloat = iFloat ? iFloat : "";
			}
			if (SelectImage.classesImage && imageData.iClass) {
				var iClass;
				if (imageData.iClass.options.length > 0) {
					iClass = imageData.iClass.options[imageData.iClass.selectedIndex].value;
				}
				if (iClass || selectedImageRef.attributes["class"] && selectedImageRef.attributes["class"].value) {
					selectedImageRef.className = iClass;
				} else {
					selectedImageRef.className = "";
				}
			}
			if (imageData.iLang) {
				var iLang = imageData.iLang.options[imageData.iLang.selectedIndex].value;
				var languageObject = SelectImage.Plugin.get().editor.getPlugin("Language");
				if (iLang || languageObject.getLanguageAttribute(selectedImageRef)) {
					languageObject.setLanguageAttributes(selectedImageRef, iLang);
				} else {
					languageObject.setLanguageAttributes(selectedImageRef, "none");
				}
			}
			if (imageData.iClickEnlarge) {
				if (imageData.iClickEnlarge.checked) {
					selectedImageRef.setAttribute("data-htmlarea-clickenlarge","1");
				} else {
					selectedImageRef.removeAttribute("data-htmlarea-clickenlarge");
					selectedImageRef.removeAttribute("clickenlarge");
				}
			}
			SelectImage.Plugin.get().close();
		}
	};

	/**
	 * Actions on the form
	 */
	SelectImage.Form = {

		/**
		 * Build the form and append it to the body of the document
		 *
		 * @param {string} classesImageJSOptions options of the class selector
		 * @param {array} removedProperties array of properties configured to be rmoved
		 * @param {bool} lockPlainWidth true if the plain image width is locked
		 * @param {bool} lockPlainHeight true if the plain image height is locked
		 * @return void
		 */
		build: function(classesImageJSOptions, removedProperties, lockPlainWidth, lockPlainHeight) {
			var plugin = SelectImage.Plugin.get();
			var selectedImageRef = SelectImage.CurrentImage.get();
			var styleSelector = '';
			if (SelectImage.classesImage) {
				styleSelector = '<select id="iClass" name="iClass" style="width:140px;">' + classesImageJSOptions + '</select>';
			}
			var floatSelector = '<select id="iFloat" name="iFloat">'
				+ '<option value="">' + SelectImage.labels['notSet'] + '</option>'
				+ '<option value="none">' + SelectImage.labels['nonFloating'] + '</option>'
				+ '<option value="left">' + SelectImage.labels['left'] + '</option>'
				+ '<option value="right">' + SelectImage.labels['right'] + '</option>'
				+ '</select>';
			var languageSelector = '';
			if (plugin.getButton('Language')) {
				languageSelector = '<select id="iLang" name="iLang">';
				var options = plugin.getButton('Language').getOptions();
				for (var i = 0, n = options.length; i < n; i++) {
					languageSelector += '<option value="' + options[i].value + '">' + options[i].innerHTML + '</option>';
				}
				languageSelector += '</select>';
			}
			var sz = '';
			sz += '<form name="imageData"><table class="htmlarea-window-table">';
			if (removedProperties.indexOf('class') === -1 && SelectImage.classesImage) {
				sz += '<tr><td><label for="iClass">' + SelectImage.labels['class'] + ': </label></td><td>' + styleSelector + '</td></tr>';
			}
			if (removedProperties.indexOf('width') === -1 && !(selectedImageRef && selectedImageRef.src.indexOf('RTEmagic') === -1 && lockPlainWidth)) {
				sz += '<tr><td><label for="iWidth">' + SelectImage.labels['width'] + ': </label></td><td><input type="text" id="iWidth" name="iWidth" value="" style="width: 39px;" maxlength="4" /></td></tr>';
			}
			if (removedProperties.indexOf('height') === -1 && !(selectedImageRef && selectedImageRef.src.indexOf('RTEmagic') === -1 && lockPlainHeight)) {
				sz += '<tr><td><label for="iHeight">' + SelectImage.labels['height'] + ': </label></td><td><input type="text" id="iHeight" name="iHeight" value="" style="width: 39px;" maxlength="4" /></td></tr>';
			}
			if (removedProperties.indexOf('border') === -1) {
				sz += '<tr><td><label for="iBorder">' + SelectImage.labels['border'] + ': </label></td><td><input type="checkbox" id="iBorder" name="iBorder" value="1" /></td></tr>';
			}
			if (removedProperties.indexOf('float') === -1) {
				sz += '<tr><td><label for="iFloat">' + SelectImage.labels['float'] + ': </label></td><td>' + floatSelector + '</td></tr>';
			}
			if (removedProperties.indexOf('paddingTop') === -1) {
				sz += '<tr><td><label for="iPaddingTop">' + SelectImage.labels['padding_top'] + ': </label></td><td><input type="text" id="iPaddingTop" name="iPaddingTop" value="" style="width: 39px;" maxlength="4" /></td></tr>';
			}
			if (removedProperties.indexOf('paddingRight') === -1) {
				sz += '<tr><td><label for="iPaddingRight">' + SelectImage.labels['padding_right'] + ': </label></td><td><input type="text" id="iPaddingRight" name="iPaddingRight" value="" style="width: 39px;" maxlength="4" /></td></tr>';
			}
			if (removedProperties.indexOf('paddingBottom') === -1) {
				sz += '<tr><td><label for="iPaddingBottom">' + SelectImage.labels['padding_bottom'] + ': </label></td><td><input type="text" id="iPaddingBottom" name="iPaddingBottom" value="" style="width: 39px;" maxlength="4" /></td></tr>';
			}
			if (removedProperties.indexOf('paddingLeft') === -1) {
				sz += '<tr><td><label for="iPaddingLeft">' + SelectImage.labels['padding_left'] + ': </label></td><td><input type="text" id="iPaddingLeft" name="iPaddingLeft" value="" style="width: 39px;" maxlength="4" /></td></tr>';
			}
			if (removedProperties.indexOf('title') === -1) {
				sz += '<tr><td><label for="iTitle">' + SelectImage.labels['title'] + ': </label></td><td><input type="text" id="iTitle" name="iTitle" style="width:192px;" maxlength="256" /></td></tr>';
			}
			if (removedProperties.indexOf('alt') === -1) {
				sz += '<tr><td><label for="iAlt">' + SelectImage.labels['alt'] + ': </label></td><td><input type="text" id="iAlt" name="iAlt" style="width:192px;" maxlength="256" /></td></tr>';
			}
			if (removedProperties.indexOf('lang') === -1 && plugin.getButton('Language')) {
				sz += '<tr><td><label for="iLang">' + plugin.getPluginInstance('Language').localize('Language-Tooltip') + ': </label></td><td>' + languageSelector + '</td></tr>';
			}
			if (removedProperties.indexOf('data-htmlarea-clickenlarge') === -1 && removedProperties.indexOf('clickenlarge') === -1 ) {
				sz += '<tr><td><label for="iClickEnlarge">' + SelectImage.labels['image_zoom'] + ' </label></td><td><input type="checkbox" name="iClickEnlarge" id="iClickEnlarge" value="0" /></td></tr>';
			}
			sz += '<tr><td></td><td><input class="btn btn-default" type="submit" value="' + SelectImage.labels['update'] + '" onclick="SelectImage.CurrentImage.setProperties(SelectImage.classesImage)"></td></tr>';
			sz += '</table></form>';

			var div = document.createElement('div');
			div.innerHTML = sz;
			document.body.appendChild(div);
		},

		/**
		 * Insert current image properties into the fields of the form
		 * @return void
		 */
		insertImageProperties: function () {
			var plugin = SelectImage.Plugin.get();
			var selectedImageRef = SelectImage.CurrentImage.get();
			if (selectedImageRef) {
				var styleWidth, styleHeight, padding;
				if (document.imageData.iWidth) {
					styleWidth = selectedImageRef.style.width ? selectedImageRef.style.width : selectedImageRef.width;
					styleWidth = parseInt(styleWidth);
					if (!isNaN(styleWidth) && styleWidth !== 0) {
						document.imageData.iWidth.value = styleWidth;
					}
				}
				if (document.imageData.iHeight) {
					styleHeight = selectedImageRef.style.height ? selectedImageRef.style.height : selectedImageRef.height;
					styleHeight = parseInt(styleHeight);
					if (!isNaN(styleHeight) && styleHeight !== 0) {
						document.imageData.iHeight.value = styleHeight;
					}
				}
				if (document.imageData.iPaddingTop) {
					padding = selectedImageRef.style.paddingTop ? selectedImageRef.style.paddingTop : selectedImageRef.vspace;
					padding = parseInt(padding);
					if (isNaN(padding) || padding <= 0) { padding = ""; }
					document.imageData.iPaddingTop.value = padding;
				}
				if (document.imageData.iPaddingRight) {
					padding = selectedImageRef.style.paddingRight ? selectedImageRef.style.paddingRight : selectedImageRef.hspace;
					padding = parseInt(padding);
					if (isNaN(padding) || padding <= 0) { padding = ""; }
					document.imageData.iPaddingRight.value = padding;
				}
				if (document.imageData.iPaddingBottom) {
					padding = selectedImageRef.style.paddingBottom ? selectedImageRef.style.paddingBottom : selectedImageRef.vspace;
					padding = parseInt(padding);
					if (isNaN(padding) || padding <= 0) { padding = ""; }
					document.imageData.iPaddingBottom.value = padding;
				}
				if (document.imageData.iPaddingLeft) {
					padding = selectedImageRef.style.paddingLeft ? selectedImageRef.style.paddingLeft : selectedImageRef.hspace;
					padding = parseInt(padding);
					if (isNaN(padding) || padding <= 0) { padding = ""; }
					document.imageData.iPaddingLeft.value = padding;
				}
				if (document.imageData.iTitle) {
					document.imageData.iTitle.value = selectedImageRef.title;
				}
				if (document.imageData.iAlt) {
					document.imageData.iAlt.value = selectedImageRef.alt;
				}
				if (document.imageData.iBorder) {
					if((selectedImageRef.style.borderStyle && selectedImageRef.style.borderStyle != "none" && selectedImageRef.style.borderStyle != "none none none none") || selectedImageRef.border) {
						document.imageData.iBorder.checked = 1;
					}
				}
				var fObj, value, a;
				if (document.imageData.iFloat) {
					fObj = document.imageData.iFloat;
					value = selectedImageRef.style.cssFloat ? selectedImageRef.style.cssFloat : selectedImageRef.style.styleFloat;
					for (a = 0; a < fObj.length; a++) {
						if (fObj.options[a].value == value) {
							fObj.selectedIndex = a;
						}
					}
				}
				if (SelectImage.classesImage && document.imageData.iClass) {
					fObj = document.imageData.iClass;
					value = selectedImageRef.className;
					for (a = 0; a < fObj.length; a++) {
						if (fObj.options[a].value == value) {
							fObj.selectedIndex = a;
						}
					}
				}
				if (document.imageData.iLang) {
					fObj = document.imageData.iLang;
					value = plugin.editor.getPlugin("Language").getLanguageAttribute(selectedImageRef);
					for (var i = 0, n = fObj.length; i < n; i++) {
						if (fObj.options[i].value == value) {
							fObj.selectedIndex = i;
							if (i) {
								fObj.options[0].text = plugin.editor.getPlugin("Language").localize("Remove language mark");
							}
						}
					}
				}
				if (document.imageData.iClickEnlarge) {
					document.imageData.iClickEnlarge.checked = selectedImageRef.getAttribute("data-htmlarea-clickenlarge") === "1" || selectedImageRef.getAttribute("clickenlarge") === "1";
				}
			}
		}
	};

	// public usage
	window.SelectImage = SelectImage;

	return SelectImage;
});
