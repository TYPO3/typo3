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
import l from"@typo3/backend/link-browser.js";import o from"@typo3/backend/modal.js";import{resolveLink as u}from"@typo3/backend/link-resolver.js";import d from"@typo3/core/event/regular-event.js";import{LINK_ALLOWED_ATTRIBUTES as h,addLinkPrefix as f}from"@typo3/rte-ckeditor/plugin/typo3-link.js";import"@typo3/backend/element/combobox-element.js";class m{constructor(){this.editor=null,this.selectionStartPosition=null,this.selectionEndPosition=null}initialize(){this.editor=o.currentModal.userData.editor,this.selectionStartPosition=o.currentModal.userData.selectionStartPosition,this.selectionEndPosition=o.currentModal.userData.selectionEndPosition;const i=document.querySelector(".t3js-removeCurrentLink");i!==null&&new d("click",t=>{t.preventDefault(),this.restoreSelection(),this.editor.execute("unlink"),o.dismiss()}).bindTo(i)}async finalizeFunction(i){const t=l.getLinkAttributeValues(),e=t.params?t.params:"";delete t.params;const n=this.sanitizeLink(i,e),s=this.selectionStartPosition.isEqual(this.selectionEndPosition)&&(t.title||(await u(n)).title)||"",a=this.convertAttributes(t,s);this.restoreSelection(),this.editor.execute("link",n,a),o.dismiss()}restoreSelection(){this.editor.model.change(i=>{const t=[i.createRange(this.selectionStartPosition,this.selectionEndPosition)];i.setSelection(t)})}convertAttributes(i,t){const e={attrs:{}};for(const[n,s]of Object.entries(i))h.includes(n)&&(e.attrs[f(n)]=s);return typeof t=="string"&&t!==""&&(e.linkText=t),e}sanitizeLink(i,t){const e=i.match(/^([a-z0-9]+:\/\/[^:/?#]+(?:\/?[^?#]*)?)(\??[^#]*)(#?.*)$/);if(e&&e.length>0){i=e[1]+e[2];const n=e[2].length>0?"&":"?";t.length>0&&(t.startsWith("&")&&(t=t.substr(1)),t.length>0&&(i+=n+t)),i+=e[3]}return i}}const c=new m;l.finalizeFunction=r=>{c.finalizeFunction(r)};export{c as default};
