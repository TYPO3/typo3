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
/*****************************************************************
 * HTMLArea.Event: Utility functions for dealing with events     *
 *****************************************************************/
HTMLArea.Event = function ($, UserAgent, Util) {

	var Event = {

		NAMESPACE: '.htmlarea',

		// Key codes for key events
		BACKSPACE: 8,
		TAB: 9,
		ENTER: 13,
		ESC: 27,
		SPACE: 32,
		LEFT: 37,
		UP: 38,
		RIGHT: 39,
		DOWN: 40,
		DELETE: 46,
		F11: 122,
		NON_BREAKING_SPACE: 160,

		// DOM Level 3 defines values for event.key
		domLevel3Keys: {
			'Backspace': 8,
			'Tab': 9,
			'Enter': 13,
			'Esc': 27,
			'Escape': 27,
			'Spacebar': 32,
			' ': 32,
			'Left': 37,
			'ArrowLeft': 37,
			'Up': 38,
			'ArrowUp': 38,
			'Right': 39,
			'ArrowRight': 39,
			'Down': 40,
			'ArrowDown': 40,
			'Del': 46,
			'Delete': 46,
			'0': 48,
			'1': 49,
			'2': 50,
			'3': 51,
			'4': 52,
			'5': 53,
			'6': 54,
			'7': 55,
			'8': 56,
			'9': 57,
			'F11': 122
		},

		// Safari keypress events for special keys return bad keycodes
		safariKeys: {
		    3 : 13, // enter
		    63234 : 37, // left
		    63235 : 39, // right
		    63232 : 38, // up
		    63233 : 40, // down
		    63276 : 33, // page up
		    63277 : 34, // page down
		    63272 : 46, // delete
		    63273 : 36, // home
		    63275 : 35  // end
		},

		/**
		 * Attach an event handler on an element
		 *
		 * @param object|string element: the element to which the event handler is attached or a jquery selector
		 * @param string eventName: the name of the event
		 * @param function handler: the event handler
		 * @param object options: options for handling the event
		 * @return void
		 */
		on: function (element, eventName, handler, options) {
			var data = {};
			if (typeof options === 'object') {
				Util.apply(data, options);
			}
			if (data.delegate) {
				$(element).on(eventName + Event.NAMESPACE, data.delegate, data, handler);
			} else {
				$(element).on(eventName + Event.NAMESPACE, data, handler);
			}
		},

		/**
		 * Attach an event handler on an element. The handler is executed at most once.
		 *
		 * @param object|string element: the element to which the event handler is attached or a jquery selector
		 * @param string eventName: the name of the event
		 * @param function handler: the event handler
		 * @param object options: options for handling the event
		 * @return void
		 */
		one: function (element, eventName, handler, options) {
			var data = {};
			if (typeof options === 'object') {
				Util.apply(data, options);
			}
			$(element).one(eventName + Event.NAMESPACE, data, handler);
		},

		/**
		 * Attach an event handler on an element
		 *
		 * @param object|string element: the element to which the event handler is attached or a jquery selector
		 * @param string eventName: the name of the event
		 * @param function handler: the event handler
		 * @return void
		 */
		off: function (element, eventName, handler) {
			if (typeof eventName === 'undefined' && typeof handler === 'undefined') {
				$(element).off(Event.NAMESPACE);
			} else if (typeof handler === 'undefined') {
				$(element).off(eventName + Event.NAMESPACE);
			} else {
				$(element).off(eventName + Event.NAMESPACE, handler);
			}
		},

		/**
		 * Attach an event handler on an element
		 *
		 * @param object event: the jQuery event object
		 * @return void
		 */
		stopEvent: function (event) {
			event.preventDefault();
			event.stopPropagation();
		},

		/**
		 * Trigger an event
		 *
		 * @param object|string element: the element to which the event handler is attached or a jquery selector
		 * @param string eventName: the name of the event
		 * @param array extraParameters: extra parameters to be passed to the event handler
		 * @return void
		 */
		trigger: function(element, eventName, extraParameters) {
			if (typeof extraParameters === 'undefined') {
				$(element).trigger(eventName);
			} else {
				$(element).trigger(eventName, extraParameters);
			}
		},

		/**
		 * Returns a normalized key for the event.
		 *
		 * @param object event: the jQuery event object
		 * @return integer the normalized key
		 */
		getKey: function (event) {
			return Event.normalizeKey(event.originalEvent.key ? event.originalEvent.key : (event.originalEvent.charCode ? event.originalEvent.charCode : (event.originalEvent.keyCode ? event.originalEvent.keyCode : event.originalEvent.which)));
			return Event.normalizeKey((event.originalEvent.charCode ? event.originalEvent.charCode : (event.originalEvent.keyCode ? event.originalEvent.keyCode : event.originalEvent.which)));
		},

		/**
		 * Returns a normalized key
		 *
		 * @param integer key: the key
		 * @return integer the normalized key
		 */
		normalizeKey: function(key){
		    return UserAgent.isSafari ? (Event.safariKeys[key] || key) : (Event.domLevel3Keys[key] || key);
		},

		/**
		 * Get the original browser event
		 *
		 * @param object event: the jQuery  event object
		 * @return object the browser event
		 */
		getBrowserEvent: function (event) {
			return event.originalEvent;
		}
	};

	return Event;

}(HTMLArea.jQuery, HTMLArea.UserAgent, HTMLArea.util);
