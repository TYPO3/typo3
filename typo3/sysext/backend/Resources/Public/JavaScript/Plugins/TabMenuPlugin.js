/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2013 Daniel Sattler <daniel.sattler@b13.de>
 *      2013 Benjamin Mack <benni@typo3.org>
 *  All rights reserved
 *
 *  Released under GNU/GPL2+ (see license file in the main directory)
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  This copyright notice MUST APPEAR in all copies of this script
 *
 ***************************************************************/
/**
 * provides a jQuery plugin called "tabMenu" that is hooked on to each
 * item of the element of the menu
 *
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
				var $parent = $parent.parent();
				if ($parent.data('TabMenuPlugin')) {
					options = $parent.data('TabMenuPlugin').options;
					break;
				}
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
		// for its tabs, and
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
		}
		// detect the menu element (if the plugin is called on a tab label, find the menu item)
		,detectContainerElement: function() {
			if (me.$element.is(me.options.tabMenuContainerSelector)) {
				me.$containerElement = me.$element;
			} else {
				me.$containerElement = me.$element.closest(me.options.tabMenuContainerSelector);
			}
			me.containerId = me.$containerElement.prop('id');
		}

		// initialize events
		,_initEvents: function() {

			// events binding to toggle the tab menu on clicking the head
			 $(me.options.tabSelector, me.$containerElement).on('click', function(evt) {
				evt.preventDefault();
				$(this).tabMenu('toggle');
			});
		}

		// called on a tab item, that shows the container target
		// and disables the other container targets on the same level
		,toggle: function() {

			var
				$activeItem = this.$element
				,$parent = $activeItem.closest('li')
				// get DOM id of the target tab container
				,$target = $($activeItem.attr('href'));

			// click on active tab
			if ($parent.hasClass(me.options.activeClass)) {
				return;
			}

			// trigger jQuery hook: "show"
			$activeItem.trigger({
				type: 'show'
				,relatedTarget: $target
			});

			// update tab label class, and the siblings
			$parent.addClass(me.options.activeClass).siblings().removeClass(me.options.activeClass);
			// save the change in the local storage
			if (localStorage && $parent.prop('id')) {
				localStorage.setItem('TabMenuPlugin-ActiveItem-' + me.containerId, '#' + $parent.prop('id'));
			}

			// display target tab content
			$target.show().siblings().hide();

			// trigger jQuery hook "shown"
			$activeItem.trigger({
				type: 'shown'
				,relatedTarget: $target
			});
		}
	};

	// default options
	TabMenuPlugin.defaults = {
		// the selector for the tab label, should contain the content tab as href=""
		tabSelector: '[data-toggle="TabMenu"]'
		// the container selector, contains all information about the tabs
		,tabMenuContainerSelector: '.typo3-dyntabmenu'
		// the class that the tab selector gets
		,activeClass: 'active'
	};

	// register the jQuery plugin "as $('myContainer').tabMenu()"
	$.fn.tabMenu = function(action, options) {
		return this.each(function() {
			var $this = $(this)
				,data = $this.data('TabMenuPlugin');

			// only apply the tabmenu to an item that does not have the tabmenu initialized yet
			if (!data) {
				$this.data('TabMenuPlugin', (data = new TabMenuPlugin(this, options)));
			}

			// option is an action to call sth directly
			if (typeof action == 'string') {
				data[action]();
			}
		})
	};

	return TabMenuPlugin;
});