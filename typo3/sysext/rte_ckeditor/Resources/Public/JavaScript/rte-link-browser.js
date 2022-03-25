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
import LinkBrowser from"@typo3/recordlist/link-browser.js";import Modal from"@typo3/backend/modal.js";import RegularEvent from"@typo3/core/event/regular-event.js";class RteLinkBrowser{constructor(){this.plugin=null,this.CKEditor=null,this.ranges=[]}initialize(e){this.CKEditor=Modal.currentModal.userData.ckeditor,window.addEventListener("beforeunload",(()=>{this.CKEditor.getSelection().selectRanges(this.ranges)})),this.ranges=this.CKEditor.getSelection().getRanges();const t=document.querySelector(".t3js-removeCurrentLink");null!==t&&new RegularEvent("click",(e=>{e.preventDefault(),this.CKEditor.execCommand("unlink"),Modal.dismiss()})).bindTo(t)}finalizeFunction(e){const t=this.CKEditor.document.createElement("a"),r=LinkBrowser.getLinkAttributeValues();let n=r.params?r.params:"";delete r.params;for(const[e,n]of Object.entries(r))t.setAttribute(e,n);const i=e.match(/^([a-z0-9]+:\/\/[^:\/?#]+(?:\/?[^?#]*)?)(\??[^#]*)(#?.*)$/);if(i&&i.length>0){e=i[1]+i[2];const t=i[2].length>0?"&":"?";n.length>0&&("&"===n[0]&&(n=n.substr(1)),n.length>0&&(e+=t+n)),e+=i[3]}t.setAttribute("href",e);const s=this.CKEditor.getSelection();s.selectRanges(this.ranges),s&&""===s.getSelectedText()&&s.selectElement(s.getStartElement()),s&&s.getSelectedText()?t.setText(s.getSelectedText()):t.setText(t.getAttribute("href")),this.CKEditor.insertElement(t),Modal.dismiss()}}let rteLinkBrowser=new RteLinkBrowser;export default rteLinkBrowser;LinkBrowser.finalizeFunction=e=>{rteLinkBrowser.finalizeFunction(e)};