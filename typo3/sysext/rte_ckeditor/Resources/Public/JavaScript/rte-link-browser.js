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
import LinkBrowser from"@typo3/backend/link-browser.js";import Modal from"@typo3/backend/modal.js";import RegularEvent from"@typo3/core/event/regular-event.js";import{LINK_ALLOWED_ATTRIBUTES,addLinkPrefix}from"@typo3/rte-ckeditor/plugin/typo3-link.js";class RteLinkBrowser{constructor(){this.editor=null,this.ranges=null}initialize(e){this.editor=Modal.currentModal.userData.ckeditor,this.linkCommand=this.editor.commands.get("link"),this.ranges=this.editor.model.document.selection.getRanges(),window.addEventListener("beforeunload",(()=>{this.editor.model.change((e=>{e.setSelection(this.ranges)}))}));const t=document.querySelector(".t3js-removeCurrentLink");null!==t&&new RegularEvent("click",(e=>{e.preventDefault(),this.editor.execute("unlink"),Modal.dismiss()})).bindTo(t)}finalizeFunction(e){const t=LinkBrowser.getLinkAttributeValues(),n=t.params?t.params:"";delete t.params;const i=this.convertAttributes(t,"");this.editor.model.change((e=>e.setSelection(this.ranges))),this.linkCommand.execute(this.sanitizeLink(e,n),i),Modal.dismiss()}convertAttributes(e,t){const n={attrs:{}};for(const[t,i]of Object.entries(e))LINK_ALLOWED_ATTRIBUTES.includes(t)&&(n.attrs[addLinkPrefix(t)]=i);return"string"==typeof t&&""!==t&&(n.linkText=t),n}sanitizeLink(e,t){const n=e.match(/^([a-z0-9]+:\/\/[^:\/?#]+(?:\/?[^?#]*)?)(\??[^#]*)(#?.*)$/);if(n&&n.length>0){e=n[1]+n[2];const i=n[2].length>0?"&":"?";t.length>0&&("&"===t[0]&&(t=t.substr(1)),t.length>0&&(e+=i+t)),e+=n[3]}return e}}let rteLinkBrowser=new RteLinkBrowser;export default rteLinkBrowser;LinkBrowser.finalizeFunction=e=>{rteLinkBrowser.finalizeFunction(e)};