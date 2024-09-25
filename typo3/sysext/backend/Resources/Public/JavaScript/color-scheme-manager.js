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
var Identifier;!function(e){e.switch="typo3-backend-color-scheme-switch"}(Identifier||(Identifier={}));class ColorSchemeManager{constructor(){document.addEventListener("typo3:color-scheme:update",this.onBroadcastSchemeUpdate.bind(this))}onBroadcastSchemeUpdate(e){const t=e.detail.payload?.name||e.detail.name;document.documentElement.setAttribute("data-color-scheme",t),window.frames.list_frame?.document.documentElement.setAttribute("data-color-scheme",t),this.updateActiveScheme(t)}updateActiveScheme(e){document.querySelector(Identifier.switch).activeColorScheme=e}}export default new ColorSchemeManager;