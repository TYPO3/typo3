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
import r from"@typo3/backend/link-browser.js";import o from"@typo3/core/ajax/ajax-request.js";import{FormEngineLinkBrowserSetLinkEvent as s}from"@typo3/backend/event/form-engine-link-browser-set-link-event.js";var a=(function(){const e={onFieldChangeItems:null};return e.setOnFieldChangeItems=function(n){e.onFieldChangeItems=n},r.finalizeFunction=async n=>{const i=await new o(TYPO3.settings.ajaxUrls.link_browser_encodetypolink).withQueryArguments({...r.getLinkAttributeValues(),url:n}).get(),{typoLink:t}=await i.resolve();t&&window.frameElement.dispatchEvent(new s(t,e.onFieldChangeItems))},e})();export{a as default};
