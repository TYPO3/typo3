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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/KeyMap
 * HTMLArea.KeyMap: Utility functions for dealing with key events   *
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event'],
	function (Event) {

	/**
	 * Constructor method
	 *
	 * @param {Object} element: the element to which the key map is attached
	 * @param {String} eventName: the event name
	 * @constructor
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/KeyMap
	 */
	var KeyMap = function (element, eventName) {

		// Key bindings
		this.keyBindings = {};

		// Attach the key map event handler to the element
		var self = this;
		Event.on(element, eventName, function (event) { return self.handler(event); });
	};	

	/**
	 * Add an event handler to the keymap for a given combination of keys
	 *
	 * @param {Object} options: options for the binding; possible keys:
	 *	key: a key or an array of keys
	 *	ctrl: boolean,
	 *	shift: boolean,
	 *	alt: boolean,
	 *	handler: an event handler
	 * @return void
	 */
	KeyMap.prototype.addBinding = function (options) {
		var key = options.key,
			normalizedKey;
		if (typeof key === 'string' || typeof key === 'number') {
			key = [key];
		}
		for (var i = 0, n = key.length; i < n; i++) {
			// Normalizing hot keys
			normalizedKey = key[i];
			if (typeof normalizedKey === 'string' && normalizedKey.length === 1) {
				normalizedKey = normalizedKey.toUpperCase().charCodeAt(0);
			}
			if (typeof this.keyBindings[normalizedKey] === 'undefined') {
				this.keyBindings[normalizedKey] = [];
			}
			this.keyBindings[normalizedKey].push({
				ctrl: options.ctrl,
				shift: options.shift,
				alt: options.alt,
				handler: options.handler
			});
		}
	};

	/**
	 * Key map handler
	 * @return {Boolean} false if the event was handled
	 */
	KeyMap.prototype.handler = function (event) {
		var key = Event.getKey(event);
		var keyBindings = this.keyBindings[key];
		if (typeof keyBindings !== 'undefined') {
			for (var i = 0, n = keyBindings.length; i < n; i++) {
				var keyBinding = keyBindings[i];
				if ((typeof keyBinding.alt === 'undefined' || event.altKey == keyBinding.alt)
					&& (typeof keyBinding.shift === 'undefined' || event.shiftKey == keyBinding.shift)
					&& (typeof keyBinding.ctrl === 'undefined' || event.ctrlKey == keyBinding.ctrl || event.metaKey == keyBinding.ctrl)
				) {
					return keyBinding.handler(event);
				}
			}
		}
		return true;
	};

	return KeyMap;

});
