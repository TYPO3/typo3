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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent
 * Identify the current user agent
 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent
 */
define([], function () {

	var userAgent = navigator.userAgent.toLowerCase();
	var documentMode = document.documentMode,
		isOpera = /opera/i.test(userAgent),
		isEdge = /edge/i.test(userAgent),
		isChrome = !isEdge && /\bchrome\b/i.test(userAgent),
		isWebKit = !isEdge && /webkit/i.test(userAgent),
		isSafari = !isEdge && !isChrome && /safari/i.test(userAgent),
		isIE = (!isOpera && /msie/i.test(userAgent)) || /trident/i.test(userAgent),
		isIE6 = isIE && /msie 6/i.test(userAgent),
		isIE7 = isIE && (/msie 7/i.test(userAgent) || documentMode == 7),
		isIE8 = isIE && ((/msie 8/i.test(userAgent) && documentMode != 7) || documentMode == 8),
		isIEBeforeIE9 = isIE6 || isIE7 || isIE8 || (isIE && typeof documentMode !== 'undefined' && documentMode < 9),
		isGecko = !isWebKit && !isIE && !isEdge && /gecko/i.test(userAgent),
		isiPhone = /iphone/i.test(userAgent),
		isiPad = /ipad/i.test(userAgent);
	return {
		isOpera: isOpera,
		isEdge: isEdge,
		isChrome: isChrome,
		isWebKit: isWebKit,
		isSafari: isSafari,
		isIE: isIE,
		isIE6: isIE6,
		isIE7: isIE7,
		isIE8: isIE8,
		isIEBeforeIE9: isIEBeforeIE9,
		isGecko: isGecko,
		isGecko2: isGecko && /rv:1\.8/i.test(userAgent),
		isGecko3: isGecko && /rv:1\.9/i.test(userAgent),
		isWindows: /windows|win32/i.test(userAgent),
		isMac: /macintosh|mac os x/i.test(userAgent),
		isAir: /adobeair/i.test(userAgent),
		isLinux: /linux/i.test(userAgent),
		isAndroid: /android/i.test(userAgent),
		isiPhone: isiPhone,
		isiPad: isiPad,
		isiOS: isiPhone || isiPad,
		/**
		 * Check if the client agent is supported
		 *
		 * @return boolean true if the client is supported
		 */
		isSupported: function () {
			return isGecko || isWebKit || isOpera || isEdge ||(isIE && !isIEBeforeIE9);
		}
	};
});
