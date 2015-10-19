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
 * Module: TYPO3/CMS/Backend/LegacyCssClasses
 * JavaScript replacement for Legacy CSS Classes
 */
define(['jquery'], function($) {
	'use strict';

	/**
	 *
	 * @type {{replacements: Array}}
	 * @exports TYPO3/CMS/Backend/LegacyCssClasses
	 */
	var LegacyCssClasses = {
		replacements: [
			{
				selector: '.t3-table',
				remove: ['t3-table'],
				add: ['table', 'table-striped', 'table-hover']
			}
		]
	};

	$(function() {
		$.each(LegacyCssClasses.replacements, function(key, replacement) {
			var $items = $(replacement.selector);
			if ($items.length > 0) {
				$items.each(function() {
					var $item = $(this);
					if (replacement.remove.length > 0) {
						$.each(replacement.remove, function(oldClassId, oldClassName) {
							$item.removeClass(oldClassName);
						});
					}
					if (replacement.add.length > 0) {
						$.each(replacement.add, function(newClassId, newClassName) {
							$item.addClass(newClassName);
						});
					}
				});
			}
		});
	});

	return LegacyCssClasses;
});
