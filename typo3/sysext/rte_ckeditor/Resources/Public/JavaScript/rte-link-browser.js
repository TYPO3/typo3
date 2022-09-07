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
import $ from"jquery";import LinkBrowser from"@typo3/recordlist/link-browser.js";import Modal from"@typo3/backend/modal.js";class RteLinkBrowser{constructor(){this.plugin=null,this.CKEditor=null,this.ranges=[],this.siteUrl=""}initialize(t){let e=Modal.currentModal.data("ckeditor");if(void 0!==e)this.CKEditor=e;else{let e;e=void 0!==top.TYPO3.Backend&&void 0!==top.TYPO3.Backend.ContentContainer.get()?top.TYPO3.Backend.ContentContainer.get():window.parent,$.each(e.CKEDITOR.instances,((e,i)=>{i.id===t&&(this.CKEditor=i)}))}window.addEventListener("beforeunload",(()=>{this.CKEditor.getSelection().selectRanges(this.ranges)})),this.ranges=this.CKEditor.getSelection().getRanges(),$.extend(RteLinkBrowser,$("body").data()),$(".t3js-removeCurrentLink").on("click",(t=>{t.preventDefault(),this.CKEditor.execCommand("unlink"),Modal.dismiss()}))}finalizeFunction(t){const e=this.CKEditor.document.createElement("a"),i=LinkBrowser.getLinkAttributeValues();let r=i.params?i.params:"";i.target&&e.setAttribute("target",i.target),i.class&&e.setAttribute("class",i.class),i.title&&e.setAttribute("title",i.title),delete i.title,delete i.class,delete i.target,delete i.params,$.each(i,((t,i)=>{e.setAttribute(t,i)}));const n=t.match(/^([a-z0-9]+:\/\/[^:\/?#]+(?:\/?[^?#]*)?)(\??[^#]*)(#?.*)$/);if(n&&n.length>0){t=n[1]+n[2];const e=n[2].length>0?"&":"?";r.length>0&&("&"===r[0]&&(r=r.substr(1)),r.length>0&&(t+=e+r)),t+=n[3]}e.setAttribute("href",t);const s=this.CKEditor.getSelection();s.selectRanges(this.ranges),s&&""===s.getSelectedText()&&s.selectElement(s.getStartElement()),s&&s.getSelectedText()?e.setText(s.getSelectedText()):e.setText(e.getAttribute("href")),this.CKEditor.insertElement(e),Modal.dismiss()}}let rteLinkBrowser=new RteLinkBrowser;export default rteLinkBrowser;LinkBrowser.finalizeFunction=t=>{rteLinkBrowser.finalizeFunction(t)};