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
 * Module: TYPO3/CMS/Install/CardLayout
 */
define(['jquery', 'bootstrap'], function ($) {
	'use strict';

	return {
		initialize: function() {
			// Card expand / collapse handling
			$(document).on('click', '.gridder-list', function(e) {
				e.preventDefault();
				var $element = $(this);
				var $contentContainer = $element.next();
				if (!$element.hasClass('selectedItem')) {
					// Find possible current open one and close it
					$('.gridder-list').removeClass('selectedItem');
					$('.gridder-content.gridder-show').slideUp(function() {
						$(this).removeClass('gridder-show');
					});
					// Open clicked one in parallel
					$element.addClass('selectedItem');
					$contentContainer.addClass('gridder-show').slideDown();
				} else {
					// Collapse this currently open grid
					$contentContainer.slideUp(function() {
						$contentContainer.removeClass('gridder-show');
					});
					$element.removeClass('selectedItem');
				}
			});

			// Close current and open previous card
			$(document).on('click', '.gridder-nav-prev', function() {
				var $currentOpenContent = $('.gridder-content.gridder-show');
				if ($currentOpenContent.length > 0) {
					var $currentOpenCard = $currentOpenContent.prev();
					var $previousCardContent = $currentOpenCard.prev();
					var $previousCard = $previousCardContent.prev();
					if ($previousCard.length > 0) {
						$currentOpenCard.removeClass('selectedItem');
						$currentOpenContent.slideUp(function() {
							$(this).removeClass('gridder-show');
						});
						$previousCard.addClass('selectedItem');
						$previousCardContent.addClass('gridder-show').slideDown();
					}
				}
			});

			// Close current and open next card
			$(document).on('click', '.gridder-nav-next', function() {
				var $currentOpenContent = $('.gridder-content.gridder-show');
				if ($currentOpenContent.length > 0) {
					var $currentOpenCard = $currentOpenContent.prev();
					var $nextCard = $currentOpenContent.next();
					var $nextCardContent = $nextCard.next();
					if ($nextCardContent.length > 0) {
						$currentOpenCard.removeClass('selectedItem');
						$currentOpenContent.slideUp(function() {
							$(this).removeClass('gridder-show');
						});
						$nextCard.addClass('selectedItem');
						$nextCardContent.addClass('gridder-show').slideDown();
					}
				}
			});

			// Close current open card
			$(document).on('click', '.gridder-close', function() {
				var $currentOpenContent = $('.gridder-content.gridder-show');
				if ($currentOpenContent.length > 0) {
					var $currentOpenCard = $currentOpenContent.prev();
					$currentOpenCard.removeClass('selectedItem');
					$currentOpenContent.slideUp(function() {
						$(this).removeClass('gridder-show');
					});
				}
			});
		}
	};
});
