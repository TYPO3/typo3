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
 * This class handle the tabs in the TYPO3 backend.
 * It stores the last active tab and open it again after a reload,
 */
define('TYPO3/CMS/Backend/Tabs', ['jquery', 'TYPO3/CMS/Backend/Storage', 'bootstrap'], function ($) {

	/**
	 * Tabs helper
	 *
	 * @type {{storage: (Storage.Client|*), cacheTimeInSeconds: number, storeLastActiveTab: number}}
	 */
	var Tabs = {
		storage: top.TYPO3.Storage.Client,
		// cache liftime in seconds
		cacheTimeInSeconds: 1800,
		storeLastActiveTab: 1
	};

	/**
	 * initialize Tabs Helper
	 */
	Tabs.initialize = function() {
		$('.t3js-tabs').each(function() {
			var $tabContainer = $(this);
			Tabs.storeLastActiveTab = $tabContainer.data('store-last-tab') == '1' ? 1 : 0;
			$tabContainer.find('a[href="' + Tabs.receiveActiveTab($tabContainer.attr('id')) + '"]').tab('show');
			$tabContainer.on('show.bs.tab', function(e) {
				if (Tabs.storeLastActiveTab == 1) {
					var id = e.currentTarget.id;
					var target = e.target.hash;
					Tabs.storeActiveTab(id, target);
				}
			});
		});
	};

	/**
	 * receive active tab from storage
	 *
	 * @param id
	 * @returns {string}
	 */
	Tabs.receiveActiveTab = function(id) {
		var target = Tabs.storage.get(id) || '';
		var expire = Tabs.storage.get(id + '.expire') || 0;
		if (expire > Tabs.getTimestamp()) {
			return target;
		}
		return '';
	};

	/**
	 * store active tab in storage
	 *
	 * @param id
	 * @param target
	 */
	Tabs.storeActiveTab = function(id, target) {
		Tabs.storage.set(id, target);
		Tabs.storage.set(id + '.expire', Tabs.getTimestamp() + Tabs.cacheTimeInSeconds);
	};

	/**
	 * get unixtimestamp
	 *
	 * @returns {number}
	 */
	Tabs.getTimestamp = function() {
		return Math.round((new Date()).getTime() / 1000);
	};

	/**
	 * return the Tabs object
	 */
	return function() {
		Tabs.initialize();
		return Tabs;
	}();
});
