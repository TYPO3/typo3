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
 * API for popover windows powered by Twitter Bootstrap.
 */
define(['jquery', 'bootstrap'], function($) {

	/**
	 * the main popover object
	 *
	 * @type {{}}
	 */
	var Popover = {
	};

	/**
	 * Initialize
	 */
	Popover.initialize = function(selector) {
		selector = selector || '[data-toggle="popover"]';
		$(selector).popover();
	};

	/**
	 * popover wrapper function
	 *
	 * @param $element
	 */
	Popover.popover = function($element) {
		$element.popover();
	};

	/**
	 * Set popover options on $element
	 *
	 * @param {object} $element
	 * @param {object} options
	 */
	Popover.setOptions = function($element, options) {
		options = options || {};
		var title = options.title || '';
		var content = options.content || ' ';
		$element
			.attr('data-original-title', title)
			.attr('data-content', content)
			.attr('data-placement', 'auto')
			.popover(options);
	};

	/**
	 * Show popover with title and content on $element
	 *
	 * @param {object} $element
	 */
	Popover.show = function($element) {
		$element.popover('show');
	};

	/**
	 * Hide popover on $element
	 *
	 * @param $element
	 */
	Popover.hide = function($element) {
		$element.popover('hide');
	};

	/**
	 * Destroy popover on $element
	 *
	 * @param $element
	 */
	Popover.destroy = function($element) {
		$element.popover('destroy');
	};

	/**
	 * Toggle popover on $element
	 *
	 * @param $element
	 */
	Popover.toggle = function($element) {
		$element.popover('toggle');
	};

	Popover.initialize();
	TYPO3.Popover = Popover;
	return Popover;
});
