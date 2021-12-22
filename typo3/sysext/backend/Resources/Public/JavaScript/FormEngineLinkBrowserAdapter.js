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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","TYPO3/CMS/Recordlist/LinkBrowser","TYPO3/CMS/Backend/Modal"],(function(e,n,t,r,a){"use strict";return t=__importDefault(t),function(){const e={onFieldChangeItems:null,setOnFieldChangeItems:function(n){e.onFieldChangeItems=n},checkReference:function(){const n='form[name="'+r.parameters.formName+'"] [data-formengine-input-name="'+r.parameters.itemName+'"]',t=e.getParent();if(t&&t.document&&t.document.querySelector(n))return t.document.querySelector(n);a.dismiss()}};return r.finalizeFunction=function(n){const i=e.checkReference();if(i){const o=r.getLinkAttributeValues();o.url=n,t.default.ajax({url:TYPO3.settings.ajaxUrls.link_browser_encodetypolink,data:o,method:"GET"}).done((function(n){n.typoLink&&(i.value=n.typoLink,i.dispatchEvent(new Event("change",{bubbles:!0,cancelable:!0})),e.onFieldChangeItems instanceof Array&&e.getParent().TYPO3.FormEngine.processOnFieldChange(e.onFieldChangeItems),a.dismiss())}))}},e.getParent=function(){let e;return void 0!==window.parent&&void 0!==window.parent.document.list_frame&&null!==window.parent.document.list_frame.parent.document.querySelector(".t3js-modal-iframe")?e=window.parent.document.list_frame:void 0!==window.parent&&void 0!==window.parent.frames.list_frame&&null!==window.parent.frames.list_frame.parent.document.querySelector(".t3js-modal-iframe")?e=window.parent.frames.list_frame:void 0!==window.frames&&void 0!==window.frames.frameElement&&null!==window.frames.frameElement&&window.frames.frameElement.classList.contains("t3js-modal-iframe")?e=window.frames.frameElement.contentWindow.parent:window.opener&&(e=window.opener),e},e}()}));