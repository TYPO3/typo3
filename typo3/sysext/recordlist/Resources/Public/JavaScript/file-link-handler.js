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
import LinkBrowser from"@typo3/recordlist/link-browser.js";import RegularEvent from"@typo3/core/event/regular-event.js";class FileLinkHandler{constructor(){new RegularEvent("click",(e,n)=>{e.preventDefault(),LinkBrowser.finalizeFunction(n.getAttribute("href"))}).delegateTo(document,"a.t3js-fileLink"),new RegularEvent("click",(e,n)=>{e.preventDefault(),LinkBrowser.finalizeFunction(document.body.dataset.currentLink)}).delegateTo(document,"input.t3js-linkCurrent")}}export default new FileLinkHandler;