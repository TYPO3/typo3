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
import LinkBrowser from"@typo3/backend/link-browser.js";import Modal from"@typo3/backend/modal.js";import RegularEvent from"@typo3/core/event/regular-event.js";import{LINK_ALLOWED_ATTRIBUTES,addLinkPrefix}from"@typo3/rte-ckeditor/plugin/typo3-link.js";class RteLinkBrowser{constructor(){this.editor=null,this.selectionStartPosition=null,this.selectionEndPosition=null}initialize(t){this.editor=Modal.currentModal.userData.editor,this.selectionStartPosition=Modal.currentModal.userData.selectionStartPosition,this.selectionEndPosition=Modal.currentModal.userData.selectionEndPosition;const e=document.querySelector(".t3js-removeCurrentLink");null!==e&&new RegularEvent("click",(t=>{t.preventDefault(),this.restoreSelection(),this.editor.execute("unlink"),Modal.dismiss()})).bindTo(e)}finalizeFunction(t){const e=LinkBrowser.getLinkAttributeValues(),i=e.params?e.params:"";delete e.params;const n=this.convertAttributes(e,"");this.restoreSelection(),this.editor.execute("link",this.sanitizeLink(t,i),n),Modal.dismiss()}restoreSelection(){this.editor.model.change((t=>{const e=[t.createRange(this.selectionStartPosition,this.selectionEndPosition)];t.setSelection(e)}))}convertAttributes(t,e){const i={attrs:{}};for(const[e,n]of Object.entries(t))LINK_ALLOWED_ATTRIBUTES.includes(e)&&(i.attrs[addLinkPrefix(e)]=n);return"string"==typeof e&&""!==e&&(i.linkText=e),i}sanitizeLink(t,e){const i=t.match(/^([a-z0-9]+:\/\/[^:\/?#]+(?:\/?[^?#]*)?)(\??[^#]*)(#?.*)$/);if(i&&i.length>0){t=i[1]+i[2];const n=i[2].length>0?"&":"?";e.length>0&&("&"===e[0]&&(e=e.substr(1)),e.length>0&&(t+=n+e)),t+=i[3]}return t}}let rteLinkBrowser=new RteLinkBrowser;export default rteLinkBrowser;LinkBrowser.finalizeFunction=t=>{rteLinkBrowser.finalizeFunction(t)};