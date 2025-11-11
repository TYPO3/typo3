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
import r from"@typo3/backend/link-browser.js";import a from"@typo3/backend/modal.js";import m from"@typo3/core/ajax/ajax-request.js";var f=function(){const n={onFieldChangeItems:null};return n.setOnFieldChangeItems=function(e){n.onFieldChangeItems=e},n.checkReference=function(){const e='form[name="'+r.parameters.formName+'"] [data-formengine-input-name="'+r.parameters.itemName+'"]',t=n.getParent();if(t&&t.document&&t.document.querySelector(e))return t.document.querySelector(e);a.dismiss()},r.finalizeFunction=function(e){const t=n.checkReference();if(t){const o=r.getLinkAttributeValues();o.url=e,new m(TYPO3.settings.ajaxUrls.link_browser_encodetypolink).withQueryArguments(o).get().then(async s=>{const i=await s.resolve();i.typoLink&&(t.value=i.typoLink,t.dispatchEvent(new Event("change",{bubbles:!0,cancelable:!0})),n.onFieldChangeItems instanceof Array&&n.getParent().TYPO3.FormEngine.processOnFieldChange(n.onFieldChangeItems),a.dismiss())})}},n.getParent=function(){let e;return typeof window.parent<"u"&&typeof window.parent.document.list_frame<"u"&&window.parent.document.list_frame.parent.document.querySelector(".t3js-modal-iframe")!==null?e=window.parent.document.list_frame:typeof window.parent<"u"&&typeof window.parent.frames.list_frame<"u"&&window.parent.frames.list_frame.parent.document.querySelector(".t3js-modal-iframe")!==null?e=window.parent.frames.list_frame:typeof window.frames<"u"&&typeof window.frames.frameElement<"u"&&window.frames.frameElement!==null&&window.frames.frameElement.classList.contains("t3js-modal-iframe")?e=window.frames.frameElement.contentWindow.parent:window.opener&&(e=window.opener),e},n}();export{f as default};
