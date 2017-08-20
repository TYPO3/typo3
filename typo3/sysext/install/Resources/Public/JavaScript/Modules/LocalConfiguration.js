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
 * Module: TYPO3/CMS/Install/LocalConfiguration
 */
define(['jquery', 'TYPO3/CMS/Install/FlashMessage', 'TYPO3/CMS/Install/ProgressBar', 'TYPO3/CMS/Install/InfoBox', 'TYPO3/CMS/Install/Severity', 'bootstrap'], function($, FlashMessage, ProgressBar, InfoBox, Severity) {
	'use strict';

	return {
		selectorToggleAllTrigger: '.t3js-localConfiguration-toggleAll',
		selectorWriteTrigger: '.t3js-localConfiguration-write',
		selectorSearchTrigger: '.t3js-localConfiguration-search',
		selectorWriteToken: '#t3js-localConfiguration-write-token',
		selectorOutputContainer: '.t3js-localConfiguration-output',

		initialize: function() {
			var self = this;

			$(document).on('click', this.selectorWriteTrigger, function() {
				self.write();
			});

			// Expand / collapse "Toggle all" button
			$(document).on('click', this.selectorToggleAllTrigger, function() {
				var $panels = $('.t3js-localConfiguration .panel-collapse');
				var action = ($panels.eq(0).hasClass('in')) ? 'hide' : 'show';
				$panels.collapse(action);
			});

			// Make jquerys "contains" work case-insensitive
			jQuery.expr[':'].contains = jQuery.expr.createPseudo(function(arg) {
				return function (elem) {
					return jQuery(elem).text().toUpperCase().indexOf(arg.toUpperCase()) >= 0;
				};
			});

			// Focus search field on certain user interactions
			$(document).on('keydown', function(e) {
				var $searchInput = $(self.selectorSearchTrigger);
				if (e.ctrlKey || e.metaKey) {
					// Focus search field on ctrl-f
					switch (String.fromCharCode(e.which).toLowerCase()) {
						case 'f':
							e.preventDefault();
							$searchInput.focus();
							break;
					}
				} else if (e.keyCode === 27) {
					// Clear search on ESC key
					e.preventDefault();
					$searchInput.val('').focus();
				}
			});

			// Perform expand collapse on search matches
			$(document).on('keyup', this.selectorSearchTrigger, function() {
				var typedQuery = $(this).val();
				var $searchInput = $(self.selectorSearchTrigger);
				$('div.item').each(function() {
					var $item = $(this);
					if ($(':contains(' + typedQuery + ')', $item).length > 0 || $('input[value*="' + typedQuery + '"]', $item).length > 0) {
						$item.removeClass('hidden').addClass('searchhit');
					} else {
						$item.removeClass('searchhit').addClass('hidden');
					}
				});
				$('.searchhit').parent().collapse('show');
				self.handleButtonScrolling();
				// Make search field clearable
				require(['jquery.clearable'], function() {
					var searchResultShown = ('' !== $searchInput.first().val());
					$searchInput.clearable().focus();
				});
			});

			// Trigger fixed button calculation on collapse / expand
			$(document).on('shown.bs.collapse', '.gridder-show .collapse', function() {
				self.handleButtonScrolling();
			});
			$(document).on('hidden.bs.collapse', '.gridder-show .collapse', function() {
				self.handleButtonScrolling();
			});
		},

		write: function() {
			var url = location.href + '&install[controller]=ajax';
			var configurationValues = {};
			$('.gridder-show .t3js-localConfiguration-pathValue').each(function(i, element) {
				var $element = $(element);
				if ($element.attr('type') === 'checkbox') {
					if (element.checked) {
						configurationValues[$element.data('path')] = '1';
					} else {
						configurationValues[$element.data('path')] = '0';
					}
				} else {
					configurationValues[$element.data('path')] = $element.val();
				}
			});
			var postData = {
				'install': {
					'action': 'localConfigurationWrite',
					'token': $(this.selectorWriteToken).text(),
					'configurationValues': configurationValues
				}
			};
			var $outputContainer = $(this.selectorOutputContainer);
			var message = ProgressBar.render(Severity.loading, 'Loading...', '');
			$outputContainer.empty().html(message);
			$.ajax({
				method: 'POST',
				data: postData,
				url: url,
				cache: false,
				success: function(data) {
					$outputContainer.empty();
					if (data.success === true && Array.isArray(data.status)) {
						data.status.forEach(function(element) {
							var message = InfoBox.render(element.severity, element.title, element.message);
							$outputContainer.append(message);
						});
					}
				},
				error: function() {
					var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
					$outputContainer.empty().html(message);
				}
			});
		},

		/**
		 * Fix or unfix the "Write configuration" / "Toggle all" buttons at browser window
		 * bottom if a scrollbar is shown
		 */
		handleButtonScrolling: function() {
			var $fixedFooterHandler = $('#fixed-footer-handler');
			var $fixedFooter = $('#fixed-footer');
			if (!this.isScrolledIntoView($fixedFooterHandler)) {
				$fixedFooter.addClass('fixed');
				$fixedFooter.width($('.t3js-localConfiguration .panel-group').width());
			} else {
				$fixedFooter.removeClass('fixed');
			}
		},

		/**
		 * Helper of handleButtonScrolling()
		 * See if an element is within current viewport.
		 *
		 * @param element
		 * @returns {boolean}
		 */
		isScrolledIntoView: function(element) {
			var $window = $(window);
			var docViewTop = $window.scrollTop();
			var docViewBottom = docViewTop + $window.height();
			var $elem = $(element);
			var elemTop = $elem.offset().top;
			var elemBottom = elemTop + $elem.height();
			return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
		}
	};
});
