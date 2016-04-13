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
 * Module: TYPO3/CMS/Taskcenter/Taskcenter
 */
define(['jquery',
		'TYPO3/CMS/Backend/Icons',
		'jquery-ui/sortable'
		], function($, Icons) {
	'use strict';

	/**
	 *
	 * @type {{}}
	 * @exports  TYPO3/CMS/Taskcenter/Taskcenter
	 */
	var Taskcenter = {};

	/**
	 *
	 * @param {Object} element
	 * @param {Boolean} isCollapsed
	 */
	Taskcenter.collapse = function(element, isCollapsed) {
		var $item = $(element);
		var $parent = $item.parent();
		var $icon = $parent.find('.t3js-taskcenter-header-collapse .t3js-icon');
		var isCollapsed = isCollapsed;
		var iconName;

		if(isCollapsed) {
			iconName = 'actions-view-list-expand';
		} else {
			iconName = 'actions-view-list-collapse';
		}
		Icons.getIcon(iconName, Icons.sizes.small, null, null, 'inline').done(function(icon) {
			$icon.replaceWith(icon);
		});

		$.ajax({
			url: TYPO3.settings.ajaxUrls['taskcenter_collapse'],
			type: 'post',
			cache: false,
			data: {
				'item': $parent.data('taskcenterId'),
				'state': isCollapsed
			}
		});
	};

	/**
	 *
	 */
	Taskcenter.initializeSorting = function() {
		$('#task-list').sortable({
			update: function(event, ui) {
				$.ajax({
					url: TYPO3.settings.ajaxUrls['taskcenter_sort'],
					type: 'post',
					cache: false,
					data: {
						'data': $(this).sortable('serialize', {
							key: 'task-list[]',
							expression: /[=_](.+)/
						})
					}
				});
			}
		});
	};

	/**
	 * Register listeners
	 */
	Taskcenter.initializeEvents = function() {
		$('.t3js-taskcenter-collapse').on('show.bs.collapse', function() {
			Taskcenter.collapse($(this), 0);
		});
		$('.t3js-taskcenter-collapse').on('hide.bs.collapse', function() {
			Taskcenter.collapse($(this), 1);
		});
		Taskcenter.initializeSorting();
	};

	$(Taskcenter.initializeEvents);

	return Taskcenter;
});
