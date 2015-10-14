/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with DocumentHeader source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Uses the icon API of the core to fetch icons via AJAX.
 */
define(['jquery'], function($) {
	'use strict';

	var Icons = {
		cache: {},
		sizes: {
			small: 'small',
			default: 'default',
			large: 'large',
			overlay: 'overlay'
		},
		states: {
			default: 'default',
			disabled: 'disabled'
		}
	};

	/**
	 * Get the icon by its identifier.
	 *
	 * @param {string} identifier
	 * @param {string} size
	 * @param {string} overlayIdentifier
	 * @param {string} state
	 * @return {Promise<Array>}
	 */
	Icons.getIcon = function(identifier, size, overlayIdentifier, state) {
		return $.when.apply($, Icons.fetch([[identifier, size, overlayIdentifier, state]]));
	};

	/**
	 * Fetches multiple icons by passing the parameters of getIcon() for each requested
	 * icon as array.
	 *
	 * @param {Array} icons
	 * @return {Promise<Array>}
	 */
	Icons.getIcons = function(icons) {
		if (!icons instanceof Array) {
			throw 'Icons must be an array of multiple definitions.';
		}
		return $.when.apply($, Icons.fetch(icons));
	};

	/**
	 * Performs the AJAX request to fetch the icon.
	 *
	 * @param {Array} icons
	 * @return {Array}
	 * @private
	 */
	Icons.fetch = function(icons) {
		var promises = [],
			requestedIcons = {},
			cachedRequestedIcons = {};

		for (var i = 0; i < icons.length; ++i) {
			/**
			 * Icon keys:
			 *
			 * 0: identifier
			 * 1: size
			 * 2: overlayIdentifier
			 * 3: state
			 */
			var icon = icons[i];
			icon[1] = icon[1] || Icons.sizes.default;
			icon[3] = icon[3] || Icons.states.default;

			var cacheIdentifier = icon.join('_');
			if (Icons.isCached(cacheIdentifier)) {
				$.extend(cachedRequestedIcons, Icons.getFromCache(cacheIdentifier));
			} else {
				requestedIcons[icon[0]] = {
					cacheIdentifier: cacheIdentifier,
					icon: icon
				};
			}
		}

		if (Object.keys(cachedRequestedIcons).length > 0) {
			promises.push(cachedRequestedIcons);
		}

		if (Object.keys(requestedIcons).length > 0) {
			promises.push(
				$.ajax({
					url: TYPO3.settings.ajaxUrls['icons'],
					data: {
						requestedIcons: JSON.stringify(
							$.map(requestedIcons, function(o) {
								return [o['icon']];
							})
						)
					},
					success: function(data) {
						$.each(data, function(identifier, markup) {
							var cacheIdentifier = requestedIcons[identifier].cacheIdentifier,
								cacheEntry = {};

							cacheEntry[identifier] = markup;
							Icons.putInCache(cacheIdentifier, cacheEntry);
						});
					}
				})
			);
		}

		return promises;
	};

	/**
	 * Check whether icon was fetched already
	 *
	 * @param {String} cacheIdentifier
	 * @returns {Boolean}
	 * @private
	 */
	Icons.isCached = function(cacheIdentifier) {
		return typeof Icons.cache[cacheIdentifier] !== 'undefined';
	};

	/**
	 * Get icon from cache
	 *
	 * @param {String} cacheIdentifier
	 * @returns {String}
	 * @private
	 */
	Icons.getFromCache = function(cacheIdentifier) {
		return Icons.cache[cacheIdentifier];
	};

	/**
	 * Put icon into cache
	 *
	 * @param {String} cacheIdentifier
	 * @param {Object} markup
	 * @private
	 */
	Icons.putInCache = function(cacheIdentifier, markup) {
		Icons.cache[cacheIdentifier] = markup;
	};

	return Icons;
});
