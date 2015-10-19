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
define(['jquery', 'jquery-ui/sortable'], function($) {
	'use strict';

	/**
	 *
	 * @type {{}}
	 * @exports  TYPO3/CMS/Taskcenter/Taskcenter
	 */
	var Taskcenter = {};

	/**
	 *
	 */
	Taskcenter.resizeIframe = function() {
		var $listFrame = $('#list_frame');
		if ($listFrame.length > 0) {
			$listFrame.ready(function() {
				var parent = $('#typo3-docbody');
				var parentHeight = parent.height();
				var parentWidth = parent.width() - $('#taskcenter-menu').width() - 61;
				$listFrame.css({height: parentHeight + 'px', width: parentWidth + 'px'});

				$(window).on('resize', function() {
					Taskcenter.resizeIframe();
				});
			});
		}
	};

	/**
	 *
	 * @param {Object} element
	 */
	Taskcenter.doCollapseOrExpand = function(element) {
		var itemParent = element.parent();
		var item = element.next('div').next('div').next('div').next('div');
		var state = itemParent.hasClass('expanded') ? 1 : 0;
		itemParent.toggleClass('expanded', state);
		itemParent.toggleClass('collapsed', !state);
		item.toggle(state);
		if (state) {
			element.find('i.fa').removeClass('fa-caret-down').addClass('fa-caret-up');
		} else {
			element.find('i.fa').removeClass('fa-caret-up').addClass('fa-caret-down');
		}

		$.ajax({
			url: TYPO3.settings.ajaxUrls['taskcenter_collapse'],
			type: 'post',
			cache: false,
			data: {
				'item': itemParent.prop('id'),
				'state': state
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
		$('#taskcenter-menu').find('.down').on('click', function() {
			Taskcenter.doCollapseOrExpand($(this));
		});

		Taskcenter.resizeIframe();
		Taskcenter.initializeSorting();
	};

	$(Taskcenter.initializeEvents);

	return Taskcenter;
});
