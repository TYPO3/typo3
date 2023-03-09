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
import LinkBrowser from"@typo3/backend/link-browser.js";import RegularEvent from"@typo3/core/event/regular-event.js";class UrlLinkHandler{constructor(){new RegularEvent("submit",((e,r)=>{e.preventDefault();const n=r.querySelector('[name="lurl"]').value.trim();""!==n&&LinkBrowser.finalizeFunction(n)})).delegateTo(document,"#lurlform")}}export default new UrlLinkHandler;