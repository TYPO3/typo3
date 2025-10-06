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
import l from"@typo3/backend/link-browser.js";import o from"@typo3/backend/modal.js";import a from"@typo3/core/event/regular-event.js";import{LINK_ALLOWED_ATTRIBUTES as u,addLinkPrefix as d}from"@typo3/rte-ckeditor/plugin/typo3-link.js";import"@typo3/backend/element/combobox-element.js";class h{constructor(){this.editor=null,this.selectionStartPosition=null,this.selectionEndPosition=null}initialize(){this.editor=o.currentModal.userData.editor,this.selectionStartPosition=o.currentModal.userData.selectionStartPosition,this.selectionEndPosition=o.currentModal.userData.selectionEndPosition;const e=document.querySelector(".t3js-removeCurrentLink");e!==null&&new a("click",t=>{t.preventDefault(),this.restoreSelection(),this.editor.execute("unlink"),o.dismiss()}).bindTo(e)}finalizeFunction(e){const t=l.getLinkAttributeValues(),i=t.params?t.params:"";delete t.params;const s=this.convertAttributes(t,"");this.restoreSelection(),this.editor.execute("link",this.sanitizeLink(e,i),s),o.dismiss()}restoreSelection(){this.editor.model.change(e=>{const t=[e.createRange(this.selectionStartPosition,this.selectionEndPosition)];e.setSelection(t)})}convertAttributes(e,t){const i={attrs:{}};for(const[n,s]of Object.entries(e))u.includes(n)&&(i.attrs[d(n)]=s);return typeof t=="string"&&t!==""&&(i.linkText=t),i}sanitizeLink(e,t){const i=e.match(/^([a-z0-9]+:\/\/[^:/?#]+(?:\/?[^?#]*)?)(\??[^#]*)(#?.*)$/);if(i&&i.length>0){e=i[1]+i[2];const n=i[2].length>0?"&":"?";t.length>0&&(t.startsWith("&")&&(t=t.substr(1)),t.length>0&&(e+=n+t)),e+=i[3]}return e}}const c=new h;l.finalizeFunction=r=>{c.finalizeFunction(r)};export{c as default};
