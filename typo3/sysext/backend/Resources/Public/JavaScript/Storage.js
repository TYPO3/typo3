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
 * Adds a public API for the browsers' localStorage called
 * TYPO3.Storage.Client and the Backend Users "uc",
 * available via TYPO3.Storage.Persistent
 */
define('TYPO3/CMS/Backend/Storage', ['jquery'], function ($) {
	var Storage = {
		Client: {},
		Persistent: {
			_data: false
		}
	};

	/**
	 * simple localStorage wrapper, common functions get/set/clear
	 */
	Storage.Client.get = function(key) {
		return localStorage.getItem('t3-' + key);
	};
	Storage.Client.set = function(key, value) {
		return localStorage.setItem('t3-' + key, value);
	};
	Storage.Client.clear = function() {
		localStorage.clear();
	};
	/**
	 * checks if a key was set before, useful to not do all the undefined checks all the time
	 */
	Storage.Client.isset = function(key) {
		var value = this.get(key);
		return (typeof value !== 'undefined' && typeof value !== 'null' && value != 'undefined');
	};

	/**
	 * persistent storage, stores everything on the server
	 * via AJAX, does a greedy load on read
	 * common functions get/set/clear
	 */
	Storage.Persistent.get = function(key) {
		if (this._data === false) {
			var value;
			this._loadFromServer().done(function() {
				value = Storage.Persistent._data[key];
			});
			return value;
		} else {
			return this._data[key];
		}
	};
	Storage.Persistent.set = function(key, value) {
		if (this._data === false) {
			Storage.Persistent._storeOnServer(key, value);
		} else {
			this._data[key] = value;
			this._storeOnServer(key, value);
		}
	};
	Storage.Persistent.clear = function() {
		$.ajax(TYPO3.settings.ajaxUrls['UserSettings::process'], {data: {'action': 'clear'}});
		this._data = false;
	};
	/**
	 * checks if a key was set before, useful to not do all the undefined checks all the time
	 */
	Storage.Persistent.isset = function(key) {
		var value = this.get(key);
		return (typeof value !== 'undefined' && typeof value !== 'null' && value != 'undefined');
	};

	/**
	 * loads the data from outside, only used for the initial call from BackendController
	 * @param data
	 */
	Storage.Persistent.load = function(data) {
		this._data = data;
	};

	/**
	 * loads all data from the server
	 * @returns jQuery Deferred
	 * @private
	 */
	Storage.Persistent._loadFromServer = function() {
		return $.ajax(TYPO3.settings.ajaxUrls['UserSettings::process'], {data: {'action': 'getAll'}, async: false}).done(function(data) {
			Storage.Persistent._data = data;
		});
	};

	/**
	 * stores data on the server, and gets the updated data on return
	 * to always be up-to-date inside the browser
	 * @returns jQuery Deferred
	 * @private
	 */
	Storage.Persistent._storeOnServer = function(key, value) {
		return $.ajax(TYPO3.settings.ajaxUrls['UserSettings::process'], {data: {'action': 'set', key: key, value: value}}).done(function(data) {
			Storage.Persistent._data = data;
		});
	}

	/**
	 * return the Storage object, and attach it to the global TYPO3 object on the global frame
	 */
	return function() {
		top.TYPO3.Storage = Storage;
		return Storage;
	}();
});
