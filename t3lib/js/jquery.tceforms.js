/**
 * (c) 2012 Benjamin Mack
 * Released under the GPL v2+, part of TYPO3
 *
 * contains all JS functions related to TYPO3 TCEforms
 * available under the latest jQuery version
 * can be used by $('.tceforms').t3Form({options});, all .typo3-TCEforms containers will be called on load
 */
(function($, window, document, undefined) {
	$(function() {

			// TYPO3.Form represents one form element
		TYPO3.Form = (function(parent) {
			var constructor = (function(el, options) {

				var me = this; // avoid scope issues
				var opts;	// shorthand options notation

					// initialization function; private
				me.init = function() {

						// store DOM element and jQuery object for later use
					me.el = el;
					me.$el = $(el);

						// remove any existing backups panel data
					var old_me = me.$el.data('TYPO3.Form');
					if (old_me !== undefined) {
						me.$el.removeData('TYPO3.Form');
					}

						// add a reverse reference to the DOM element
					me.$el.data('TYPO3.Form', me);

						// store options and merge with default options
					opts = me.options = $.extend({}, TYPO3.Form.defaults, options);

						// initialize events
					me.initEvents();

					return me;
				};

					// init all events related to the form
				me.initEvents = function() {

						// call this.updateField when a field is changed instead of the old one => like a "hook"
					var existingFuncFieldChanged = TBE_EDITOR.fieldChanged;
					TBE_EDITOR.fieldChanged = (function(table, uid, field, el) {
						$formField = $('[name="' + el + '"]');
						me.updateField($formField, table, uid, field);
						existingFuncFieldChanged(table, uid, field, el);
					});

					return me;
				};

				// initialize ourself
				me.init();

			});
				// migrate properties from existing, pre-loaded extensions
			return $.extend(true, constructor, parent);
			// see Loose Augmentation http://www.adequatelygood.com/2010/3/JavaScript-Module-Pattern-In-Depth
		})(TYPO3.Form || {});

			// setting some default values
		TYPO3.Form.defaults = {};

			// the mother of all methods, always called when a field is updated
		TYPO3.Form.prototype.updateField = function($formField, table, uid, field) {
				// find the right "visual" field, as this is different on different types
				// (where $formField is the hidden field) and selects (where $formField is the select)
			if ($formField.parent().find('.formField').length > 0) {
				$formField.parent().find('.formField').addClass('t3-state-changed');
			} else if ($formField.siblings('.checkbox').length > 0) {
				$formField.parent().addClass('t3-state-changed');
			} else {
				$formField.addClass('t3-state-changed');
			}
		}


			// register the form functions as jQuery Plugin
		$.fn.t3Form = function(options) {
				// apply all util functions to ourself (for use in templates, etc.)
			return this.each(function() {
				(new TYPO3.Form(this, options));
			});
		};


		/** INITIALIZATION CODE **/
		$(document).ready(function() {
				// run the form functions on all containers
			$('.typo3-TCEforms').t3Form({});
		});
	});
})(TYPO3.jQuery, window, document);