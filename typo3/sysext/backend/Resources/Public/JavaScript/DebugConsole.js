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
 * Module: TYPO3/CMS/Backend/DebugConsole
 * The debug console shown at the bottom of the backend
 */
define(['jquery'], function ($) {
	'use strict';

	/**
	 *
	 * @type {{$consoleDom: null, settings: {autoscroll: boolean}}}
	 * @exports TYPO3/CMS/Backend/DebugConsole
	 */
	var DebugConsole = {
		$consoleDom: null,
		settings: {
			autoscroll: true
		}
	};

	/**
	 * Initializes the console
	 */
	DebugConsole.initialize = function() {
		DebugConsole.createDOM();
	};

	/**
	 * Create the basic DOM of the Debugger Console
	 */
	DebugConsole.createDOM = function() {
		if (DebugConsole.$consoleDom !== null) {
			return DebugConsole.$consoleDom;
		}

		DebugConsole.$consoleDom =
			$('<div />', {id: 'typo3-debug-console'}).append(
				$('<div />', {class: 't3js-topbar topbar'}).append(
					$('<p />', {class: 'pull-left'}).text(' TYPO3 Debug Console').prepend(
						$('<span />', {class: 'fa fa-terminal topbar-icon'})
					).append(
						$('<span />', {class: 'badge'})
					),
					$('<div />', {class: 't3js-buttons btn-group pull-right'})
				),
				$('<div />').append(
					$('<div />', {role: 'tabpanel'}).append(
						$('<ul />', {class: 'nav nav-tabs t3js-debuggroups', role: 'tablist'})
					),
					$('<div />', {class: 'tab-content t3js-debugcontent'})
				)
			);

		DebugConsole.addButton(
			$('<button />', {class: 'btn btn-default btn-sm ' + (DebugConsole.settings.autoscroll ? 'active' : ''), title: TYPO3.lang['debuggerconsole.autoscroll']}).append(
				$('<span />', {class: 't3-icon fa fa-magnet'})
			), function() {
				$(this).button('toggle');
				DebugConsole.settings.autoscroll = !DebugConsole.settings.autoscroll;
			}
		).addButton(
			$('<button />', {class: 'btn btn-default btn-sm', title: TYPO3.lang['debuggerconsole.toggle.collapse']}).append(
				$('<span />', {class: 't3-icon fa fa-chevron-down'})
			), function() {
				var $button = $(this),
					$icon = $button.find('.t3-icon'),
					$innerContainer = DebugConsole.$consoleDom.find('.t3js-topbar').next();
				$innerContainer.toggle();
				if ($innerContainer.is(':visible')) {
					$button.attr('title', TYPO3.lang['debuggerconsole.toggle.collapse']);
					$icon.toggleClass('fa-chevron-down', true).toggleClass('fa-chevron-up', false);
					DebugConsole.resetGlobalUnreadCounter();
				} else {
					$button.attr('title', TYPO3.lang['debuggerconsole.toggle.expand']);
					$icon.toggleClass('fa-chevron-down', false).toggleClass('fa-chevron-up', true);
				}
			}
		).addButton(
			$('<button />', {class: 'btn btn-default btn-sm', title: TYPO3.lang['debuggerconsole.clear']}).append(
				$('<span />', {class: 't3-icon fa fa-undo'})
			), function() {
				DebugConsole.flush();
			}
		).addButton(
			$('<button />', {class: 'btn btn-default btn-sm', title: TYPO3.lang['debuggerconsole.close']}).append(
				$('<span />', {class: 't3-icon fa fa-times'})
			), function() {
				DebugConsole.$consoleDom.remove();
				DebugConsole.$consoleDom = null;
				DebugConsole.createDOM();
			}
		);
	};

	/**
	 * Adds a button and it's callback to the console's toolbar
	 *
	 * @param {Object} $button
	 * @param {function} callback
	 * @returns {{$consoleDom: null, settings: {autoscroll: boolean}}}
	 */
	DebugConsole.addButton = function($button, callback) {
		$button.on('click', callback);
		DebugConsole.$consoleDom.find('.t3js-buttons').append($button);

		return DebugConsole;
	};

	/**
	 * Attach the Debugger Console to the viewport
	 */
	DebugConsole.attachToViewport = function() {
		var $viewport = $('#typo3-contentContainer');
		if ($viewport.has(DebugConsole.$consoleDom).length === 0) {
			$viewport.append(DebugConsole.$consoleDom);
		}
	};

	/**
	 * Add the debug message to the console
	 *
	 * @param {String} message
	 * @param {String} header
	 * @param {String} [group=Debug]
	 */
	DebugConsole.add = function(message, header, group) {
		DebugConsole.attachToViewport();

		var $line = $('<p />').html(message);
		if (typeof header !== 'undefined' && header.length > 0) {
			$line.prepend($('<strong />').text(header));
		}

		if (typeof group === 'undefined' || group.length === 0) {
			group = 'Debug';
		}

		var tabIdentifier = 'debugtab-' + group.toLowerCase().replace(/\W+/g, '-'),
			$debugTabs = DebugConsole.$consoleDom.find('.t3js-debuggroups'),
			$tabContent = DebugConsole.$consoleDom.find('.t3js-debugcontent'),
			$tab = DebugConsole.$consoleDom.find('.t3js-debuggroups li[data-identifier=' + tabIdentifier + ']');

		// check if group tab exists
		if ($tab.length === 0) {
			// create new tab
			$tab =
				$('<li />', {role: 'presentation', 'data-identifier': tabIdentifier}).append(
					$('<a />', {href: '#' + tabIdentifier, 'aria-controls': tabIdentifier, role: 'tab', 'data-toggle': 'tab'}).text(group + ' ').append(
						$('<span />', {class: 'badge'})
					)
				).on('shown.bs.tab', function() {
					$(this).find('.badge').text('');
				});
			$debugTabs.append($tab);
			$tabContent.append(
				$('<div />', {role: 'tabpanel', class: 'tab-pane', id: tabIdentifier}).append(
					$('<div />', {class: 't3js-messages messages'})
				)
			);
		}

		DebugConsole.identifyTabLengthPresentationIcon($debugTabs);

		// activate the first tab if no one is active
		if ($debugTabs.find('.active').length === 0) {
			$debugTabs.find('a:first').tab('show');
		}

		DebugConsole.incrementInactiveTabCounter($tab);
		DebugConsole.incrementUnreadMessagesIfCollapsed();

		var $messageBox = $('#' + tabIdentifier + ' .t3js-messages'),
			isMessageBoxActive = $messageBox.parent().hasClass('active');

		$messageBox.append($line);
		if (DebugConsole.settings.autoscroll && isMessageBoxActive) {
			$messageBox.scrollTop($messageBox.prop('scrollHeight'));
		}
	};

	/**
	 * Gets a proper console icon depending on the amount of tabs
	 *
	 * @param {Object} $tabs
	 */
	DebugConsole.identifyTabLengthPresentationIcon = function($tabs) {
		var terminalIcon1 = true,
			terminalIcon2 = false;

		if ($tabs.children().length >= 10) {
			// too many tabs
			// much debug
			// so wow
			terminalIcon1 = false;
			terminalIcon2 = true;
		}
		DebugConsole.$consoleDom.find('.topbar-icon').toggleClass('fa-meh-o', terminalIcon2).toggleClass('fa-terminal', terminalIcon1);
	};

	/**
	 * Increment the counter of unread messages in the given tab
	 *
	 * @param {Object} $tab
	 */
	DebugConsole.incrementInactiveTabCounter = function($tab) {
		if (!$tab.hasClass('active')) {
			var $badge = $tab.find('.badge'),
				value = parseInt($badge.text());

			if (isNaN(value)) {
				value = 0;
			}
			$badge.text(++value);
		}
	};

	/**
	 * Increment the counter of unread messages in the tabbar
	 */
	DebugConsole.incrementUnreadMessagesIfCollapsed = function() {
		var $topbar = DebugConsole.$consoleDom.find('.t3js-topbar'),
			$innerContainer = $topbar.next();

		if ($innerContainer.is(':hidden')) {
			var $badge = $topbar.find('.badge'),
				value = parseInt($badge.text());

			if (isNaN(value)) {
				value = 0;
			}
			$badge.text(++value);
		}
	};

	/**
	 * Reset global unread counter
	 */
	DebugConsole.resetGlobalUnreadCounter = function() {
		var $topbar = DebugConsole.$consoleDom.find('.t3js-topbar'),
			$badge = $topbar.find('.badge');

		$badge.text('');
	};

	/**
	 * Reset the console to it's virginity
	 */
	DebugConsole.flush = function() {
		var $debugTabs = DebugConsole.$consoleDom.find('.t3js-debuggroups'),
			$tabContent = DebugConsole.$consoleDom.find('.t3js-debugcontent');

		$debugTabs.children().remove();
		$tabContent.children().remove();

		DebugConsole.identifyTabLengthPresentationIcon($debugTabs);
	};

	/**
	 * Destroy everything of the console
	 */
	DebugConsole.destroy = function() {
		DebugConsole.$consoleDom.remove();
		DebugConsole.$consoleDom = null;
	};

	$(DebugConsole.initialize);

	// expose as global object
	TYPO3.DebugConsole = DebugConsole;

	return DebugConsole;

});
