/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Jost Baron <j.baron@netzkoenig.de>
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

/**
 * This file provides a jQuery plugin for generating 'clearable' input fields.
 * These fields show a "clear"-button when someone hovers over them and
 * they are not empty.
 * Options:
 *   * 'onClear':	Function that is called after clearing. Takes no arguments,
 *					'this' is set to the clearable input element. Defaults to an
 *					empty function.
 */
(function($) {
	$.fn.clearable = function(options) {

		var defaults = {
			'onClear'	: function() {}
		};

		// Merge defaults and given options. Given options have higher priority
		// because they are the last argument.
		var settings = $.extend({}, defaults, options);

		// Iterate over the list of inputs and make each clearable. Return
		// the list to allow chaining.
		return this.each(function() {

			// The input element to make clearable.
			var $input = $(this);

			// Wrap it with a div and add a span that is the trigger for
			// clearing.
			$input.wrap('<div class="t3-clearable-wrapper"/>');
			$input.after('<span class="t3-icon t3-icon-actions t3-icon-actions-input t3-icon-input-clear t3-tceforms-input-clearer"/>');
			$input.addClass('t3-clearable');

			var $wrapper = $input.parent();
			var $clearer = $input.next();

			// Add some data to the wrapper indicating if it is currently being
			// hovered or not.
			$input.data('isHovering', false);
			$wrapper.hover(
				function() {
					$input.data('isHovering', true);
				},
				function() {
					$input.data('isHovering', false);
			});

			// Register a listener the various events triggering the clearer to
			// be shown or hidden.
			var handler = function() {
				if ($input.data('isHovering') && ($input.val() != '')) {
					$clearer.show();
				}
				else {
					$clearer.hide();
				}
			};

			$wrapper.on('mouseover mouseout', handler);
			$input.on('keypress', handler);


			// The actual clearing action. Focus the input element afterwards,
			// the user probably wants to type into it after clearing.
			$clearer.click(function() {
				$input.val('');
				$input.focus();
				handler();

				if ('function' === typeof(settings.onClear)) {
					settings.onClear.call($input.get());
				}
			});

			// Initialize the clearer icon
			handler();
		});
	};
})(jQuery);