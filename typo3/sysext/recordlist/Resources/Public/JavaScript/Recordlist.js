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
 * Usability improvements for the record list
 */
define('TYPO3/CMS/Recordlist/Recordlist', ['jquery', 'TYPO3/CMS/Backend/Storage'], function($, Storage) {
	var Recordlist = {
		identifier: {
			toggle: '.t3js-toggle-recordlist'
		},
		classes: {
			toggleIconState: {
				collapsed: 'fa-chevron-down',
				expanded: 'fa-chevron-up'
			}
		}
	};

	Recordlist.initialize = function() {
		$(document).on('click', Recordlist.identifier.toggle, function(e) {
			e.preventDefault();

			var $me = $(this),
				table = $me.data('table'),
				$target = $($me.data('target')),
				isExpanded = $target.data('state') === 'expanded';

			$me.find('.t3-icon').toggleClass(Recordlist.classes.toggleIconState.collapsed).toggleClass(Recordlist.classes.toggleIconState.expanded);

			// Store collapse state in UC
			var storedModuleDataList = {};

			if (Storage.Persistent.isset('moduleData.list')) {
				storedModuleDataList = Storage.Persistent.get('moduleData.list');
			}

			var collapseConfig = {};
			collapseConfig[table] = isExpanded ? 1 : 0;

			$.extend(true, storedModuleDataList, collapseConfig);
			Storage.Persistent.set('moduleData.list', storedModuleDataList).done(function() {
				$target.data('state', isExpanded ? 'collapsed' : 'expanded');
			});
		});
	};

	$(document).ready(function() {
		Recordlist.initialize();
	});
});