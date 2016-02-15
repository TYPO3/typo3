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
 * Module: TYPO3/CMS/T3editor/T3editor
 */
define(['jquery',
		'TYPO3/CMS/Backend/SplitButtons',
		'TYPO3/CMS/Backend/Notification'
	   ], function ($, SplitButtons, Notification) {
	'use strict';

	/**
	 *
	 * @type {{instances: {}}}
	 * @exports TYPO3/CMS/T3editor/T3editor
	 */
	var T3editor = {
		instances: {}
	};

	/**
	 * Get and initialize editors
	 */
	T3editor.findAndInitializeEditors = function() {
		$('div.t3editor').each(function(i) {
			T3editor.initializeEditor($(this), i);
		});
	};

	/**
	 * Initialize an editor
	 *
	 * @param {Object} $editor
	 * @param {Number} index
	 */
	T3editor.initializeEditor = function($editor, index) {
		if ($editor.data('initialized')) {
			return;
		}
		var $textarea = $editor.find('textarea'),
			options = {
				labels: $textarea.data('labels'),
				height: $textarea.height() + 'px',
				width: $textarea.width() + 'px',
				content: $textarea.val(),
				parserfile: $textarea.data('parserfile'),
				stylesheet: $textarea.data('stylesheet'),
				path: $textarea.data('codemirror-path'),
				saveFunction: T3editor.saveFunction,
				autoMatchParens: true,
				lineNumbers: true,
				originalTextarea: $textarea,
				ajaxSaveType: $textarea.data('ajaxsavetype')
			};

		$editor.find('.t3e_statusbar_title').text($textarea.attr('alt'));
		$editor.find('.t3e_statusbar_status').text('');

		var codemirror = new CodeMirror($editor.find('.t3e_iframe_wrap')[0], options);
		T3editor.initializeEditorEvents(codemirror);
		T3editor.setAjaxSavetypeCallback(codemirror);

		$editor.find('.t3e_modalOverlay').fadeOut({
			complete: function() {
				T3editor.resize(codemirror, $textarea.width(), $textarea.height());
				$(document).trigger('t3editor:init', [codemirror, $editor.find('.t3e_wrap')]);
				T3editor.instances[index] = codemirror;
				$textarea.hide();
			}
		});
		$editor.data('initialized', true);
	};

	/**
	 * Initializes editor events
	 *
	 * @param {Object} codemirror
	 */
	T3editor.initializeEditorEvents = function(codemirror) {
		SplitButtons.addPreSubmitCallback(function() {
			codemirror.options.originalTextarea.val(codemirror.editor.getCode());
		});

		$(codemirror.win.document).on('keydown', function(e) {
			if ((e.ctrlKey || e.metaKey) && e.which === 122) { // 122 is F11
				e.preventDefault();
				T3editor.toggleFullscreen(codemirror);
			}
		});
	};

	/**
	 * Set the ajax save callback
	 *
	 * @param {Object} codemirror
	 */
	T3editor.setAjaxSavetypeCallback = function(codemirror) {
		if (codemirror.options.ajaxSaveType !== '') {
			$(document).on('t3editor:save', function(e, data) {
				var params = $.extend({
					t3editor_savetype: codemirror.options.ajaxSaveType,
					submit: true
				}, data.parameters);

				$.ajax({
					url: TYPO3.settings.ajaxUrls['t3editor_save'],
					data: params,
					method: 'POST',
					beforeSend: function() {
						codemirror.options.originalTextarea.parent().find('.t3e_modalOverlay').fadeIn();
					},
					complete: function(jqXHR) {
						var response = JSON.parse(jqXHR.responseText);
						var wasSuccessful = jqXHR.status === 200 && response.result === true;
						codemirror.options.originalTextarea.parent().find('.t3e_modalOverlay').fadeOut();
						T3editor.saveFunctionComplete(codemirror, wasSuccessful, response);
					}
				});
			});
		}
	};

	/**
	 * Save method called upon saving
	 *
	 * @param {Object} codemirror
	 */
	T3editor.saveFunction = function(codemirror) {
		if (!codemirror.options.ajaxSaveType || codemirror.options.ajaxSaveType === '') {
			return;
		}

		codemirror.options.originalTextarea.val(codemirror.getCode());
		var params = codemirror.options.originalTextarea.closest('form').serializeObject();
		params = $.extend({t3editor_disableEditor: 'false'}, params);

		$(document).trigger('t3editor:save', {parameters: params, t3editor: this});
	};

	/**
	 * Method invoked by saveFunction() on completion
	 *
	 * @param {Object} codemirror
	 * @param {Boolean} wasSuccessful
	 * @param {Object} returnedData
	 */
	T3editor.saveFunctionComplete = function(codemirror, wasSuccessful, returnedData) {
		if (wasSuccessful) {
			this.textModified = false;
		} else {
			if (typeof returnedData.exceptionMessage !== 'undefined') {
				Notification.error(codemirror.options.labels.errorWhileSaving[0]['target'], returnedData.exceptionMessage);
			} else {
				Notification.error(codemirror.options.labels.errorWhileSaving[0]['target'], '');
			}
		}
	};

	/**
	 * Updates the textarea
	 *
	 * @param {Object} codemirror
	 */
	T3editor.updateTextarea = function(codemirror) {
		codemirror.options.originalTextarea.val(codemirror.editor.getCode());
	};

	/**
	 * Resize the editor
	 *
	 * @param {Object} codemirror
	 * @param {Number} w
	 * @param {Number} h
	 */
	T3editor.resize = function(codemirror, w, h) {
		var width = (w + 11),
			$outerDiv = codemirror.options.originalTextarea.prev('.t3e_wrap'),
			$mirrorWrap = codemirror.options.originalTextarea.parents('div.t3editor').find('.t3e_iframe_wrap');

		$outerDiv.height(h + 20).width(width);
		$outerDiv.find('.t3e_modalOverlay').height(h).width(width);
		$mirrorWrap.children().first().height(h).width(w - 13);
	};

	/**
	 * Toggle fullscreen mode of editor
	 *
	 * @param {Object} codemirror
	 */
	T3editor.toggleFullscreen = function(codemirror) {
		var $outerDiv = codemirror.options.originalTextarea.prev('.t3e_wrap'),
			$parent = $outerDiv.offsetParent(),
			parentEl = $parent.get(0),
			w, h;

		if ($outerDiv.hasClass('t3e_fullscreen')) {
			$outerDiv.removeClass('t3e_fullscreen');
			w = parseInt(codemirror.options.width);
			h = parseInt(codemirror.options.height);
			$parent.css({overflow: ''});
		} else {
			$outerDiv.addClass('t3e_fullscreen');
			w = parentEl.clientWidth;
			h = parentEl.clientHeight;
			$parent.css({overflow: 'hidden'}).scrollTop(0);
		}

		T3editor.resize(codemirror, w, h);
	};

	/**
	 * Convert all textareas to enable tab
	 */
	T3editor.convertTextareasEnableTab = function() {
		var $elements = $('.enable-tab');
		if ($elements.length) {
			require(['taboverride'], function(taboverride) {
				taboverride.set($elements);
			});
		}
	};

	/**
	 * Serialize a form to a JavaScript object
	 *
	 * @see http://stackoverflow.com/a/1186309/4828813
	 * @returns {Object}
	 */
	$.fn.serializeObject = function() {
		var o = {};
		var a = this.serializeArray();
		$.each(a, function() {
			if (typeof o[this.name] !== 'undefined') {
				if (!o[this.name].push) {
					o[this.name] = [o[this.name]];
				}
				o[this.name].push(this.value || '');
			} else {
				o[this.name] = this.value || '';
			}
		});
		return o;
	};

	/**
	 * Initialize and return the T3editor object
	 */
	$(function() {
		T3editor.findAndInitializeEditors();
		T3editor.convertTextareasEnableTab();
	});

	return T3editor;
});
