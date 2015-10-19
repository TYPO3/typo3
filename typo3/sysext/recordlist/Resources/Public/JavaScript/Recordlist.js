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
 * Module: TYPO3/CMS/Recordlist/Recordlist
 * Usability improvements for the record list
 */
define(['jquery', 'TYPO3/CMS/Backend/Storage', 'TYPO3/CMS/Backend/Icons'], function($, Storage, Icons) {
	'use strict';

	/**
	 *
	 * @type {{identifier: {toggle: string, icons: {collapse: string, expand: string}}}}
	 * @exports TYPO3/CMS/Recordlist/Recordlist
	 */
	var Recordlist = {
		identifier: {
			toggle: '.t3js-toggle-recordlist',
			icons: {
				collapse: 'actions-view-list-collapse',
				expand: 'actions-view-list-expand'
			}
		}
	};

	/**
	 *
	 * @param {Event} e
	 */
	Recordlist.toggleClick = function(e) {
		e.preventDefault();

		var $me = $(this),
			table = $me.data('table'),
			$target = $($me.data('target')),
			isExpanded = $target.data('state') === 'expanded',
			$collapseIcon = $me.find('.collapseIcon'),
			toggleIcon = isExpanded ? Recordlist.identifier.icons.expand : Recordlist.identifier.icons.collapse;

		Icons.getIcon(toggleIcon, Icons.sizes.small).done(function(toggleIcon) {
			$collapseIcon.html(toggleIcon);
		});

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
	};

	$(function() {
		$(document).on('click', Recordlist.identifier.toggle, Recordlist.toggleClick);
	});

	return Recordlist;
});
