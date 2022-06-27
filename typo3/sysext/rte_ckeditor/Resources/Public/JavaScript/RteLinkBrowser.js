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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","TYPO3/CMS/Recordlist/LinkBrowser","TYPO3/CMS/Backend/Modal","ckeditor"],(function(t,e,i,n,s){"use strict";i=__importDefault(i);class l{constructor(){this.plugin=null,this.CKEditor=null,this.ranges=[],this.siteUrl=""}initialize(t){let e=s.currentModal.data("ckeditor");if(void 0!==e)this.CKEditor=e;else{let e;e=void 0!==top.TYPO3.Backend&&void 0!==top.TYPO3.Backend.ContentContainer.get()?top.TYPO3.Backend.ContentContainer.get():window.parent,i.default.each(e.CKEDITOR.instances,(e,i)=>{i.id===t&&(this.CKEditor=i)})}window.addEventListener("beforeunload",()=>{this.CKEditor.getSelection().selectRanges(this.ranges)}),this.ranges=this.CKEditor.getSelection().getRanges(),i.default.extend(l,i.default("body").data()),i.default(".t3js-class-selector").on("change",()=>{i.default("option:selected",this).data("linkTitle")&&i.default(".t3js-linkTitle").val(i.default("option:selected",this).data("linkTitle"))}),i.default(".t3js-removeCurrentLink").on("click",t=>{t.preventDefault(),this.CKEditor.execCommand("unlink"),s.dismiss()})}finalizeFunction(t){const e=this.CKEditor.document.createElement("a"),l=n.getLinkAttributeValues();let a=l.params?l.params:"";l.target&&e.setAttribute("target",l.target),l.class&&e.setAttribute("class",l.class),l.title&&e.setAttribute("title",l.title),delete l.title,delete l.class,delete l.target,delete l.params,i.default.each(l,(t,i)=>{e.setAttribute(t,i)});const r=t.match(/^([a-z0-9]+:\/\/[^:\/?#]+(?:\/?[^?#]*)?)(\??[^#]*)(#?.*)$/);if(r&&r.length>0){t=r[1]+r[2];const e=r[2].length>0?"&":"?";a.length>0&&("&"===a[0]&&(a=a.substr(1)),a.length>0&&(t+=e+a)),t+=r[3]}e.setAttribute("href",t);const o=this.CKEditor.getSelection();o.selectRanges(this.ranges),o&&""===o.getSelectedText()&&o.selectElement(o.getStartElement()),o&&o.getSelectedText()?e.setText(o.getSelectedText()):e.setText(e.getAttribute("href")),this.CKEditor.insertHtml(e.getOuterHtml()),s.dismiss()}}let a=new l;return n.finalizeFunction=t=>{a.finalizeFunction(t)},a}));
