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
import o from"@typo3/backend/link-browser.js";import a from"@typo3/backend/modal.js";import f from"@typo3/core/ajax/ajax-request.js";var s=function(){const t={onFieldChangeItems:null};return t.setOnFieldChangeItems=function(e){t.onFieldChangeItems=e},t.checkReference=function(){const e='form[name="'+o.parameters.formName+'"] [data-formengine-input-name="'+o.parameters.itemName+'"]',n=t.getParent();if(n&&n.document&&n.document.querySelector(e))return n.document.querySelector(e);a.dismiss()},o.finalizeFunction=function(e){const n=t.checkReference();if(n){const r=o.getLinkAttributeValues();r.url=e,new f(TYPO3.settings.ajaxUrls.link_browser_encodetypolink).withQueryArguments(r).get().then(async m=>{const i=await m.resolve();i.typoLink&&(n.value=i.typoLink,n.dispatchEvent(new Event("change",{bubbles:!0,cancelable:!0})),t.onFieldChangeItems instanceof Array&&t.getParent().TYPO3.FormEngine.processOnFieldChange(t.onFieldChangeItems),a.dismiss())})}},t.getParent=function(){let e;typeof window.parent<"u"&&typeof window.parent.document.list_frame<"u"&&window.parent.document.list_frame.parent.document.querySelector(".t3js-modal-iframe")!==null?e=window.parent.document.list_frame:typeof window.parent<"u"&&typeof window.parent.frames.list_frame<"u"&&window.parent.frames.list_frame.parent.document.querySelector(".t3js-modal-iframe")!==null?e=window.parent.frames.list_frame:typeof window.frames<"u"&&typeof window.frames.frameElement<"u"&&window.frames.frameElement!==null&&window.frames.frameElement.classList.contains("t3js-modal-iframe")?e=window.frames.frameElement.contentWindow.parent:window.opener&&(e=window.opener);const n=o.parameters?.formName;if(n&&!t.windowHasForm(e,n)){const r=t.findFormWindow(n);r&&(e=r)}return e},t.windowHasForm=function(e,n){return e?.document?.querySelector('form[name="'+n+'"]')!==null},t.findFormWindow=function(e){try{for(let n=0;n<top.frames.length;n++)try{const r=top.frames[n];if(r!==window&&t.windowHasForm(r,e))return r}catch{}}catch{}return null},t}();export{s as default};
