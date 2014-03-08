/**
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
 * provides a jQuery plugin called "tabMenu" that is hooked on to each
 * item of the element of the menu
 */
define('TYPO3/CMS/Backend/Plugins/TabMenuPlugin', ['jquery'], function($) {

	// constructor method
	// element can be a tab (not the tab content)
	var TabMenuPlugin = function(element, options) {
		me = this;
		me.$element = $(element);

		// if no options given, check if one of the parents (menuElement) has the TabMenuPlugin
		if (undefined === options) {
			var $parent = me.$element;
			do {
				if ($parent.data('TabMenuPlugin')) {
					options = $parent.data('TabMenuPlugin').options;
					break;
				}
				$parent = $parent.parent();
			}
			while ($parent.length > 0);
		}
		// merging options
		me.options = $.extend({}, TabMenuPlugin.defaults, options);
		me.detectContainerElement();
	};

	// methods applied to each TabMenu item
	TabMenuPlugin.prototype = {
		// used for each menu container item, so that it initializes the events
		// for its tabs
		initialize: function() {
			var $activeItem;

			// show first tab of this menu, or the last active stored in the local storage
			if (localStorage && localStorage.getItem('TabMenuPlugin-ActiveItem-' + me.containerId)) {
				$activeItem = $(localStorage.getItem('TabMenuPlugin-ActiveItem-' + me.containerId));
				$activeItem = $activeItem.find(me.options.tabSelector).first();
			}
			if (!$activeItem || $activeItem.length == 0) {
				$activeItem = me.$containerElement.find(me.options.tabSelector).first();
			}
			if ($activeItem.length > 0) {
				$activeItem.tabMenu('toggle');
			}
			me._initEvents();
		},

		// detect the menu element (if the plugin is called on a tab label, find the menu item)
		detectContainerElement: function() {
			me.$containerElement = me.$element.closest(me.options.tabMenuContainerSelector);
			me.containerId = me.$containerElement.prop('id');
		},

		// initialize events
		_initEvents: function() {
			// events binding to toggle the tab menu on clicking the head
			$(me.options.tabSelector, me.$containerElement).on('click', function(evt) {
				evt.preventDefault();
				$(this).tabMenu('toggle');
			});
		},

		// called on a tab item, that shows the container target
		// and disables the other container targets on the same level
		toggle: function() {
			var
				$activeItem = this.$element,
				$parent = $activeItem.closest('li'),
				// get DOM id of the target tab container
				$target = $($activeItem.attr('href'));

			// trigger jQuery hook: "show"
			$activeItem.trigger({
				type: 'show',
				relatedTarget: $target
			});

			// update tab label class, and the siblings
			me.$containerElement.find('li').not($parent).removeClass(me.options.activeClass);
			$parent.addClass(me.options.activeClass);

			// save the change in the local storage
			if (localStorage && $parent.prop('id')) {
				localStorage.setItem('TabMenuPlugin-ActiveItem-' + me.containerId, '#' + $parent.prop('id'));
			}

			// display target tab content
			$target.show().siblings().hide();

			// trigger jQuery hook "shown"
			$activeItem.trigger({
				type: 'shown',
				relatedTarget: $target
			});
		}
	};

	// default options
	TabMenuPlugin.defaults = {
		// the selector for the tab label, should contain the content tab as href=""
		tabSelector: '[data-toggle="TabMenu"]',
		// the container selector, contains all information about the tabs
		tabMenuContainerSelector: '.typo3-dyntabmenu-tabs',
		// the class that the tab selector gets
		activeClass: 'active'
	};

	// register the jQuery plugin "as $('myContainer').tabMenu()"
	$.fn.tabMenu = function(action, options) {
		return this.each(function() {
			var $this = $(this),
				data = $this.data('TabMenuPlugin');

			// only apply the tabmenu to an item that does not have the tabmenu initialized yet
			if (!data) {
				$this.data('TabMenuPlugin', (data = new TabMenuPlugin(this, options)));
			}

			// option is an action to call sth directly
			if (typeof action == 'string') {
				data[action]();
			}
		});
	};

	return TabMenuPlugin;
});
