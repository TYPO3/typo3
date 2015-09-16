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
 * API for context help.
 */
define('TYPO3/CMS/Backend/ContextHelp', ['jquery', 'TYPO3/CMS/Backend/Popover', 'bootstrap'], function($) {

	/**
	 * The main ContextHelp object
	 *
	 * @type {{ajaxUrl: *, localCache: {}, openContext: null}}
	 */
	var ContextHelp = {
		ajaxUrl: TYPO3.settings.ajaxUrls['context_help'],
		localCache: {},
		helpModuleUrl: '',
		trigger: 'click',
		placement: 'auto',
		selector: '.t3-help-link'
	};

	/**
	 * Initialize context help trigger
	 */
	ContextHelp.initialize = function() {
		ContextHelp.helpModuleUrl = top.TYPO3.settings.ContextHelp.moduleUrl;
		var title = '&nbsp;';
		if (typeof(top.TYPO3.LLL) !== 'undefined') {
			title = top.TYPO3.LLL.core.csh_tooltip_loading;
		} else if (opener && typeof(opener.top.TYPO3.LLL) !== 'undefined') {
			title = opener.top.TYPO3.LLL.core.csh_tooltip_loading;
		}
		var $element = $(this.selector);
		$element
			.attr('data-loaded', 'false')
			.attr('data-html', true)
			.attr('data-original-title', title)
			.attr('data-placement', this.placement)
			.attr('data-trigger', this.trigger);
		TYPO3.Popover.popover($element);

		$(document).on('show.bs.popover', ContextHelp.selector, function(evt) {
			var $me = $(this),
				description = $me.data('description');
			if (typeof description !== 'undefined' && description !== '') {
				TYPO3.Popover.setOptions($me, {
					title: $me.data('title'),
					content: description
				});
			} else if ($me.attr('data-loaded') === 'false' && $me.data('table')) {
				ContextHelp.loadHelp($me);
			}
		});
		$(document).on('shown.bs.popover', ContextHelp.selector, function(evt) {
			var $popover = $(evt.target).data('bs.popover').$tip;
			if (!$popover.find('.popover-title').is(':visible')) {
				$popover.addClass('no-title');
			}
		});
		$(document).on('click', '.tipIsLinked', function(e) {
			$('.popover').each(function() {
				var $popover = $(this);
				if ($popover.has(e.target).length) {
					ContextHelp.showHelpPopup($popover.data('bs.popover').$element);
				}
			});
		});
		$(document).on('click', 'body', function (e) {
			$(ContextHelp.selector).each(function () {
				var $triggerElement = $(this);
				// the 'is' for buttons that trigger popups
				// the 'has' for icons within a button that triggers a popup
				if (!$triggerElement.is(e.target) && $triggerElement.has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
					TYPO3.Popover.hide($triggerElement);
				}
			});
		});
	};

	/**
	 * Open the help popup
	 *
	 * @param {object} $trigger
	 */
	ContextHelp.showHelpPopup = function($trigger) {
		var configuration = top.TYPO3.configuration.ContextHelpWindows || top.TYPO3.configuration.PopupWindow;
		try {
			var cshWindow = window.open(
				ContextHelp.helpModuleUrl +
					'&tx_cshmanual_help_cshmanualcshmanual[table]=' + $trigger.data('table') +
					'&tx_cshmanual_help_cshmanualcshmanual[field]=' + $trigger.data('field'),
				'ContextHelpWindow',
				'height=' + configuration.height + ',width=' + configuration.width + ',status=0,menubar=0,scrollbars=1'
			);
			cshWindow.focus();
			TYPO3.Popover.hide($trigger);
			return cshWindow;
		} catch(e) {
			// do nothing
		}
	};

	/**
	 * Load help data
	 *
	 * @param {object} $trigger
	 */
	ContextHelp.loadHelp = function($trigger) {
		var table = $trigger.data('table');
		var field = $trigger.data('field');
		// If a table is defined, use ajax call to get the tooltip's content
		if (table) {
			// Load content
			$.getJSON(ContextHelp.ajaxUrl, {
				params: {
					action: 'getContextHelp',
					table: table,
					field: field
				}
			}).done(function(data) {
				var title = data.title || '';
				var content = data.content || '<p></p>';
				TYPO3.Popover.setOptions($trigger, {
					title: title,
					content: content
				});
				$trigger
					.attr('data-loaded', 'true')
					.one('hidden.bs.popover', function() {
						TYPO3.Popover.show($trigger);
					});
				TYPO3.Popover.hide($trigger);
			});
		}
	};

	ContextHelp.initialize();
	TYPO3.ContextHelp = ContextHelp;
	return ContextHelp;
});
