/**
 * (c) 2012 Benjamin Mack
 * Released under the GPL v2+, part of TYPO3
 *
 * contains all JS functions related to TYPO3 Flexforms
 * available under the latest jQuery version
 * can be used by $('myflexform').t3FormFlex({options});, all .t3-flex-form containers will be called on load
 */
(function($, window, document, undefined) {
	$(function() {

		if (!TYPO3.Form) {
			TYPO3.Form = {};
		}

			// currently TYPO3.Form.Flex represents one Flexform element,
			// which can contain one ore more sections
		TYPO3.Form.Flex = function(el, options) {
			var me = this;	// avoid scope issues
			var opts;	// shorthand options notation

				// initialization function; private
			me.init = function() {

					// store DOM element and jQuery object for later use
				me.el = el;
				me.$el = $(el);

					// remove any existing backups panel data
				var old_me = me.$el.data('TYPO3.Form.Flex');
				if (old_me !== undefined) {
					me.$el.removeData('TYPO3.Form.Flex');
				}

					// add a reverse reference to the DOM element
				me.$el.data('TYPO3.Form.Flex', me);

				if (!options) {
					options = {};
				}
					// set some values from existing properties
				options.allowRestructure = me.$el.data('t3-flex-allow-restructure');
				options.flexformId = me.$el.attr('id');

					// store options and merge with default options
				opts = me.options = $.extend({}, TYPO3.Form.Flex.defaults, options);

					// initialize events
				me.initEvents();

					// generate the preview text if a section is hidden on load
				me.$el.find(opts.sectionSelector).each(function() {
					me.generateSectionPreview($(this));
				});

				return me;
			};

				// init all events related to the flexform
			me.initEvents = function() {

					// Toggling all sections on/off by clicking all toggle buttons of each section
				me.$el.prev(opts.flexFormToggleAllSectionsSelector).on('click', function() {
					me.$el.find(opts.sectionToggleButtonSelector).click();
				});

				if (opts.allowRestructure) {

						// create a sortable when dragging on the header of a section
					me.createSortable();

						// allow delete of a single section
					me.$el.delegate(opts.deleteIconSelector, 'click', function(evt) {
						evt.preventDefault();

						// @todo: make this text localizable
						if (window.confirm('Are you sure?')) {
							$(this).parents(opts.sectionSelector + ':first').hide();
							me.setActionStatus();
						}
					});

						// allow the toggle open/close of the main selection
					me.$el.delegate(opts.sectionToggleButtonSelector, 'click', function(evt) {
						evt.preventDefault();
						var $sectionEl = $(this).parents(opts.sectionSelector + ':first');
						me.toggleSection($sectionEl);
					});
				}

				return me;
			};

				// initialize ourself
			me.init();
		};

			// setting some default values
		TYPO3.Form.Flex.defaults = {
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
			'allowRestructure': 0,	// whether the form can be modified
			'flexformId': false
		};


			// Create sortables for flexform sections
		TYPO3.Form.Flex.prototype.createSortable = function() {
			var me = this;

				// @todo: use something else than scriptaculous sortable
			Position.includeScrollOffsets = true;
			Sortable.create(me.options.flexformId, {
				tag: 'div',
				constraint: false,
				handle: me.options.sectionHeaderSelector.replace(/\./, ''),
				onChange: function() {
					me.setActionStatus();
				}
			});
		};

		// Updates the "action"-status for a section. This is used to move and delete elements.
		TYPO3.Form.Flex.prototype.setActionStatus = function() {
			var me = this;
				// Traverse and find how many sections are open or closed, and save the value accordingly
			me.$el.find(me.options.sectionActionInputFieldSelector).each(function(index) {
				var actionValue = ($(this).parents(me.options.sectionSelector).is(':visible') ? index : 'DELETE');
				$(this).val(actionValue);
			});
		};

			// Toggling flexform elements on/off
			// hides the flexform section and shows a preview text
			// or shows the form parts
		TYPO3.Form.Flex.prototype.toggleSection = function($sectionEl) {

			var $contentEl = $sectionEl.find(this.options.sectionContentSelector);

				// display/hide the content of this flexform section
			$contentEl.toggle();

			if ($contentEl.is(':visible')) {
					// show the open icon, and set the hidden field for toggling to "hidden"
				$sectionEl.find(this.options.sectionToggleIconOpenSelector).show();
				$sectionEl.find(this.options.sectionToggleIconCloseSelector).hide();
				$sectionEl.find(this.options.sectionToggleInputFieldSelector).val(0);
			} else {
					// show the close icon, and set the hidden field for toggling to "vla"
				$sectionEl.find(this.options.sectionToggleIconOpenSelector).hide();
				$sectionEl.find(this.options.sectionToggleIconCloseSelector).show();
				$sectionEl.find(this.options.sectionToggleInputFieldSelector).val(1);
			}
				// see if the preview content needs to be generated
			this.generateSectionPreview($sectionEl);
		}

			// function to generate the section preview in the header
			// if the section content is hidden
			// called on load and when toggling an icon
		TYPO3.Form.Flex.prototype.generateSectionPreview = function($sectionEl) {

			var $contentEl = $sectionEl.find(this.options.sectionContentSelector);
			var previewContent = '';

			if (!$contentEl.is(':visible')) {
				$contentEl.find('input[type=text], textarea').each(function() {
					previewContent += (previewContent ? ' / ' : '') + $(this).val();
				});
			}

			if ($sectionEl.find(this.options.sectionHeaderPreviewSelector).length == 0) {
				$sectionEl.find(this.options.sectionHeaderSelector).children(':first').append('<span class="' + this.options.sectionHeaderPreviewSelector.replace(/\./, '') + '"></span>');
			}

			$sectionEl.find(this.options.sectionHeaderPreviewSelector).text(previewContent);
		}


			// register the flex functions as jQuery Plugin
		$.fn.t3FormFlex = function(options) {
				// apply all util functions to ourself (for use in templates, etc.)
			return this.each(function() {
				(new TYPO3.Form.Flex(this, options));
			});
		};


		/** INITILIATION CODE **/
		$(document).ready(function() {
				// run the flexform functions on all containers (which contains one or more sections)
			$('.t3-flex-container').t3FormFlex({});
		});
	});
})(TYPO3.jQuery, window, document);