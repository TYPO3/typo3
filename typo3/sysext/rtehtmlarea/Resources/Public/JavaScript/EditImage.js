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
 * Module: TYPO3/CMS/Rtehtmlarea/EditImage
 */
define(['jquery', 'TYPO3/CMS/Rtehtmlarea/SelectImage'], function($, SelectImage) {
	'use strict';

	/**
	 * @type {{classesImage: bool, initializeForm: Function, updateImage: Function}}
	 * @exports TYPO3/CMS/Rtehtmlarea/EditImage
	 */
	var EditImage = {
		classesImage: false,

		/**
		 * Actions on the current image
		 */
		updateImage: function (e) {
			e.preventDefault();

			var selectedImageRef = SelectImage.getCurrentImage();
			if (!selectedImageRef) {
				return;
			}

			var $element;
			$.each({ width: 'iWidth', height: 'iHeight'}, function(index, id) {
				$element = $('#t3js-' + id);
				if ($element) {
					var value = $element.val();
					if (value) {
						var intValue = parseInt(value);
						if (intValue) {
							selectedImageRef.style[index] = "";
							$(selectedImageRef).attr(index, intValue);
						}
					}
				}
			});

			$.each({
					paddingTop: 'iPaddingTop',
					paddingRight: 'iPaddingRight',
					paddingBottom: 'iPaddingBottom',
					paddingLeft: 'iPaddingLeft'
				},
				function(index, id) {
					$element = $('#t3js-' + id);
					if ($element) {
						var value = $element.val();
						if (value) {
							var intValue = parseInt(value);
							if (value !== "" && !isNaN(intValue)) {
								selectedImageRef.style[index] = intValue + "px";
							} else {
								selectedImageRef.style[index] = "";
							}
						}
					}
				}
			);

			$.each({ title: 'iTitle', alt: 'iAlt' }, function(index, id) {
				$element = $('#t3js-' + id);
				if ($element) {
					$(selectedImageRef).attr(index, $element.val());
				}
			});

			$element = $('#t3js-iBorder');
			if ($element) {
				selectedImageRef.style.borderStyle = "";
				selectedImageRef.style.borderWidth = "";
				selectedImageRef.style.border = "";
				selectedImageRef.style.borderTopStyle = "";
				selectedImageRef.style.borderRightStyle = "";
				selectedImageRef.style.borderBottomStyle = "";
				selectedImageRef.style.borderLeftStyle = "";
				selectedImageRef.style.borderTopWidth = "";
				selectedImageRef.style.borderRightWidth = "";
				selectedImageRef.style.borderBottomWidth = "";
				selectedImageRef.style.borderLeftWidth = "";
				if ($element.prop('checked')) {
					selectedImageRef.style.borderStyle = "solid";
					selectedImageRef.style.borderWidth = "thin";
				}
				selectedImageRef.removeAttribute("border");
			}

			$element = $('#t3js-iFloat');
			if ($element) {
				var value = $element.val();
				selectedImageRef.style.cssFloat = value ? value : "";
			}

			if (EditImage.classesImage) {
				$element = $('#t3js-iClass');
				if ($element) {
					var iClass;
					if ($element.find('option').length > 0) {
						iClass = $element.val();
					}
					if (iClass || $(selectedImageRef).attr('class')) {
						selectedImageRef.className = iClass;
					} else {
						selectedImageRef.className = "";
					}
				}
			}

			var languageObject = SelectImage.plugin.editor.getPlugin("Language");
			$element = $('#t3js-iLang');
			if ($element && languageObject) {
				var iLang = $element.val();
				if (iLang || languageObject.getLanguageAttribute(selectedImageRef)) {
					languageObject.setLanguageAttributes(selectedImageRef, iLang);
				} else {
					languageObject.setLanguageAttributes(selectedImageRef, "none");
				}
			}

			$element = $('#t3js-iClickEnlarge');
			if ($element) {
				if ($element.prop('checked')) {
					selectedImageRef.setAttribute("data-htmlarea-clickenlarge","1");
				} else {
					selectedImageRef.removeAttribute("data-htmlarea-clickenlarge");
					selectedImageRef.removeAttribute("clickenlarge");
				}
			}

			SelectImage.plugin.close();
		},

		/**
		 * Actions on the form
		 */
		initializeForm: function() {

			var plugin = SelectImage.plugin;
			var selectedImageRef = SelectImage.getCurrentImage();

			var languageButton = plugin.getButton('Language');
			var $languageElement = $('#t3js-languageSetting');
			if (languageButton && $languageElement) {
				var languageSelector = '';
				var options = languageButton.getOptions();
				for (var i = 0, n = options.length; i < n; i++) {
					languageSelector += '<option value="' + options[i].value + '">' + options[i].innerHTML + '</option>';
				}
				languageSelector += '';

				$languageElement.find('label').text(plugin.getPluginInstance('Language').localize('Language-Tooltip') + ': ');
				$('#t3js-iLang').html(languageSelector);
			} else if ($languageElement) {
				$languageElement.remove();
			}

			var $element;
			$.each({ width: 'iWidth', height: 'iHeight'}, function(index, id) {
				$element = $('#t3js-' + id);
				if ($element) {
					var value = selectedImageRef.style[index] ? selectedImageRef.style[index] : $(selectedImageRef).attr(index);
					value = parseInt(value);
					if (!isNaN(value) && value !== 0) {
						$element.val(value);
					}
				}
			});

			$.each({
					paddingTop: ['iPaddingTop', 'vspace'],
					paddingRight: ['iPaddingRight', 'hspace'],
					paddingBottom: ['iPaddingBottom', 'vspace'],
					paddingLeft: ['iPaddingLeft', 'hspace']
				},
				function(index, obj) {
					$element = $('#t3js-' + obj[0]);
					if ($element) {
						var padding = selectedImageRef.style[obj[0]] ? selectedImageRef.style[obj[0]] : $(selectedImageRef).attr(obj[1]);
						padding = parseInt(padding);
						if (isNaN(padding) || padding <= 0) {
							padding = "";
						}
						$element.val(padding);
					}
				}
			);

			$.each({ title: 'iTitle', alt: 'iAlt' }, function(index, id) {
				$element = $('#t3js-' + id);
				if ($element) {
					$element.val($(selectedImageRef).attr(index));
				}
			});

			$element = $('#t3js-iBorder');
			if ($element) {
				$element.prop('checked', $(selectedImageRef).attr('border')
					|| selectedImageRef.style.borderStyle && selectedImageRef.style.borderStyle !== "none"
					&& selectedImageRef.style.borderStyle !== "none none none none");
			}

			$element = $('#t3js-iFloat');
			if ($element) {
				$element.val(selectedImageRef.style.cssFloat ? selectedImageRef.style.cssFloat : selectedImageRef.style.styleFloat);
			}

			$element = $('#t3js-iClass');
			if ($element && EditImage.classesImage) {
				$element.val(selectedImageRef.className);
			}

			var languagePlugin = SelectImage.plugin.editor.getPlugin("Language");
			$element = $('#t3js-iLang');
			if ($element && languagePlugin) {
				$element.val(languagePlugin.getLanguageAttribute(selectedImageRef));
				if ($element.val()) {
					$element.find('option')[0].text(languagePlugin.localize("Remove language mark"));
				}
			}

			$element = $('#t3js-iClickEnlarge');
			if ($element) {
				$element.prop('checked', selectedImageRef.getAttribute("data-htmlarea-clickenlarge") === "1" || selectedImageRef.getAttribute("clickenlarge") === "1");
			}
		}
	};

	$(function () {
		$.extend(EditImage, $('body').data());

		$('.t3js-editForm').on('submit', EditImage.updateImage);

		EditImage.initializeForm();
	});

	return EditImage;
});
