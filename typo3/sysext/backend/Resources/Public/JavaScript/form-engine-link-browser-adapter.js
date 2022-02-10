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
import $ from"jquery";import LinkBrowser from"@typo3/recordlist/link-browser.js";import Modal from"@typo3/backend/modal.js";export default(function(){const e={onFieldChangeItems:null,setOnFieldChangeItems:function(n){e.onFieldChangeItems=n},checkReference:function(){const n='form[name="'+LinkBrowser.parameters.formName+'"] [data-formengine-input-name="'+LinkBrowser.parameters.itemName+'"]',t=e.getParent();if(t&&t.document&&t.document.querySelector(n))return t.document.querySelector(n);Modal.dismiss()}};return LinkBrowser.finalizeFunction=function(n){const t=e.checkReference();if(t){const r=LinkBrowser.getLinkAttributeValues();r.url=n,$.ajax({url:TYPO3.settings.ajaxUrls.link_browser_encodetypolink,data:r,method:"GET"}).done((function(n){n.typoLink&&(t.value=n.typoLink,t.dispatchEvent(new Event("change",{bubbles:!0,cancelable:!0})),e.onFieldChangeItems instanceof Array&&e.getParent().TYPO3.FormEngine.processOnFieldChange(e.onFieldChangeItems),Modal.dismiss())}))}},e.getParent=function(){let e;return void 0!==window.parent&&void 0!==window.parent.document.list_frame&&null!==window.parent.document.list_frame.parent.document.querySelector(".t3js-modal-iframe")?e=window.parent.document.list_frame:void 0!==window.parent&&void 0!==window.parent.frames.list_frame&&null!==window.parent.frames.list_frame.parent.document.querySelector(".t3js-modal-iframe")?e=window.parent.frames.list_frame:void 0!==window.frames&&void 0!==window.frames.frameElement&&null!==window.frames.frameElement&&window.frames.frameElement.classList.contains("t3js-modal-iframe")?e=window.frames.frameElement.contentWindow.parent:window.opener&&(e=window.opener),e},e}());