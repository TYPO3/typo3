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
 * contains all JS functions related to TYPO3 Flexforms
 * available under the latest jQuery version
 * can be used by $('myflexform').t3FormEngineFlexFormElement({options});, all .t3-flex-form containers will be called on load
 *
 * currently TYPO3.FormEngine.FlexFormElement represents one Flexform element
 * which can contain one ore more sections
 */

define('TYPO3/CMS/Backend/FormEngineFlexForm', ['jquery', 'TYPO3/CMS/Backend/FormEngine'], function ($) {

	TYPO3.FormEngine.FlexFormElement = function(el, options) {
		var me = this;	// avoid scope issues
		var opts;	// shorthand options notation

		// initialization function; private
		me.initialize = function() {

			// store DOM element and jQuery object for later use
			me.el = el;
			me.$el = $(el);

			// remove any existing backups
			var old_me = me.$el.data('TYPO3.FormEngine.FlexFormElement');
			if (old_me !== undefined) {
				me.$el.removeData('TYPO3.FormEngine.FlexFormElement');
			}

			// add a reverse reference to the DOM element
			me.$el.data('TYPO3.FormEngine.FlexFormElement', me);

			if (!options) {
				options = {};
			}

			// set some values from existing properties
			options.allowRestructure = me.$el.data('t3-flex-allow-restructure');
			options.flexformId = me.$el.attr('id');

			// store options and merge with default options
			opts = me.options = $.extend({}, TYPO3.FormEngine.FlexFormElement.defaults, options);

			// initialize events
			me.initializeEvents();

			// generate the preview text if a section is hidden on load
			me.$el.find(opts.sectionSelector).each(function() {
				me.generateSectionPreview($(this));
			});

			return me;
		};

		// init all events related to the flexform
		me.initializeEvents = function() {

			// Toggling all sections on/off by clicking all toggle buttons of each section
			me.$el.prev(opts.flexFormToggleAllSectionsSelector).on('click', function() {
				me.$el.find(opts.sectionToggleButtonSelector).trigger('click');
			});

			if (opts.allowRestructure) {

				// create a sortable when dragging on the header of a section
				me.createSortable();

				// allow delete of a single section
				me.$el.on('click', opts.deleteIconSelector, function(evt) {
					evt.preventDefault();

					// @todo: make this text localizable
					if (window.confirm('Are you sure?')) {
						$(this).closest(opts.sectionSelector).hide().addClass(opts.sectionDeletedClass);
						me.setActionStatus();
					}
				});

				// allow the toggle open/close of the main selection
				me.$el.on('click', opts.sectionToggleButtonSelector, function(evt) {
					evt.preventDefault();
					var $sectionEl = $(this).closest(opts.sectionSelector);
					me.toggleSection($sectionEl);
				});
			}

			return me;
		};

		// initialize ourself
		me.initialize();
	};

	// setting some default values
	TYPO3.FormEngine.FlexFormElement.defaults = {
		'deleteIconSelector': '.t3-delete',
		'sectionSelector': '.t3-flex-section',
		'sectionContentSelector': '.t3-flex-section-content',
		'sectionHeaderSelector': '.t3-flex-section-header',
		'sectionHeaderPreviewSelector': '.t3-flex-section-header-preview',
		'sectionActionInputFieldSelector': '.t3-flex-control-action',
		'sectionToggleInputFieldSelector': '.t3-flex-control-toggle',
		'sectionToggleIconOpenSelector': '.t3-flex-control-toggle-icon-open',
		'sectionToggleIconCloseSelector': '.t3-flex-control-toggle-icon-close',
		'sectionToggleButtonSelector': '.t3-flex-control-toggle-button',
		'flexFormToggleAllSectionsSelector': '.t3-form-field-toggle-flexsection',
		'sectionDeletedClass': 't3-flex-section-deleted',
		'allowRestructure': 0,	// whether the form can be modified
		'flexformId': false
	};


	/**
	 * Allow flexform sections to be sorted
	 */
	TYPO3.FormEngine.FlexFormElement.prototype.createSortable = function() {
		var me = this;

		require(['jquery-ui/sortable'], function () {
			me.$el.sortable({
				containment: 'parent',
				handle: '.t3-js-sortable-handle',
				axis: 'y',
				tolerance: 'pointer',
				stop: function () {
					me.setActionStatus();
				}
			});
		});
	};

	// Updates the "action"-status for a section. This is used to move and delete elements.
	TYPO3.FormEngine.FlexFormElement.prototype.setActionStatus = function() {
		var me = this;

		// Traverse and find how many sections are open or closed, and save the value accordingly
		me.$el.find(me.options.sectionActionInputFieldSelector).each(function(index) {
			var actionValue = ($(this).parents(me.options.sectionSelector).hasClass(me.options.sectionDeletedClass) ? 'DELETE' : index);
			$(this).val(actionValue);
		});
	};

	// Toggling flexform elements on/off
	// hides the flexform section and shows a preview text
	// or shows the form parts
	TYPO3.FormEngine.FlexFormElement.prototype.toggleSection = function($sectionEl) {

		var $contentEl = $sectionEl.find(this.options.sectionContentSelector);

		// display/hide the content of this flexform section
		$contentEl.toggle();

		if ($contentEl.is(':visible')) {

			// show the open icon, and set the hidden field for toggling to "hidden"
			$sectionEl.find(this.options.sectionToggleIconOpenSelector).show();
			$sectionEl.find(this.options.sectionToggleIconCloseSelector).hide();
			$sectionEl.find(this.options.sectionToggleInputFieldSelector).val(0);
		} else {

			// show the close icon, and set the hidden field for toggling to "1"
			$sectionEl.find(this.options.sectionToggleIconOpenSelector).hide();
			$sectionEl.find(this.options.sectionToggleIconCloseSelector).show();
			$sectionEl.find(this.options.sectionToggleInputFieldSelector).val(1);
		}

		// see if the preview content needs to be generated
		this.generateSectionPreview($sectionEl);
	};

	// function to generate the section preview in the header
	// if the section content is hidden
	// called on load and when toggling an icon
	TYPO3.FormEngine.FlexFormElement.prototype.generateSectionPreview = function($sectionEl) {
		var $contentEl = $sectionEl.find(this.options.sectionContentSelector);
		var previewContent = '';

		if (!$contentEl.is(':visible')) {
			$contentEl.find('input[type=text], textarea').each(function() {
				previewContent += (previewContent ? ' / ' : '') + $(this).val();
			});
		}

		// create a preview container span element
		if ($sectionEl.find(this.options.sectionHeaderPreviewSelector).length == 0) {
			$sectionEl.find(this.options.sectionHeaderSelector).children(':first').append('<span class="' + this.options.sectionHeaderPreviewSelector.replace(/\./, '') + '"></span>');
		}

		$sectionEl.find(this.options.sectionHeaderPreviewSelector).text(previewContent);
	};

	// register the flex functions as jQuery Plugin
	$.fn.t3FormEngineFlexFormElement = function(options) {
		// apply all util functions to ourself (for use in templates, etc.)
		return this.each(function() {
			(new TYPO3.FormEngine.FlexFormElement(this, options));
		});
	};

	// Initialization Code
	$(document).ready(function() {
		// run the flexform functions on all containers (which contains one or more sections)
		$('.t3-flex-container').t3FormEngineFlexFormElement();
	});
});