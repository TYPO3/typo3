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
define(["require","exports","jquery","TYPO3/CMS/Recordlist/LinkBrowser","TYPO3/CMS/Backend/Modal","ckeditor"],function(t,e,i,n,r){"use strict";var o=new(function(){function t(){this.plugin=null,this.CKEditor=null,this.siteUrl=""}return t.prototype.initialize=function(e){var n=this,o=r.currentModal.data("ckeditor");if(void 0!==o)this.CKEditor=o;else{var a=void 0;a=void 0!==top.TYPO3.Backend&&void 0!==top.TYPO3.Backend.ContentContainer.get()?top.TYPO3.Backend.ContentContainer.get():window.parent,i.each(a.CKEDITOR.instances,function(t,i){i.id===e&&(n.CKEditor=i)})}i.extend(t,i("body").data()),i(".t3js-class-selector").on("change",function(){i("option:selected",n).data("linkTitle")&&i(".t3js-linkTitle").val(i("option:selected",n).data("linkTitle"))}),i(".t3js-removeCurrentLink").on("click",function(t){t.preventDefault(),n.CKEditor.execCommand("unlink"),r.dismiss()})},t.prototype.finalizeFunction=function(t){var e=this.CKEditor.document.createElement("a"),o=n.getLinkAttributeValues(),a=o.params?o.params:"";o.target&&e.setAttribute("target",o.target),o.class&&e.setAttribute("class",o.class),o.title&&e.setAttribute("title",o.title),delete o.title,delete o.class,delete o.target,delete o.params,i.each(o,function(t,i){e.setAttribute(t,i)}),e.setAttribute("href",t+a);var s=this.CKEditor.getSelection();s&&""===s.getSelectedText()&&s.selectElement(s.getStartElement()),s&&s.getSelectedText()?e.setText(s.getSelectedText()):e.setText(e.getAttribute("href")),this.CKEditor.insertElement(e),r.dismiss()},t}());return n.finalizeFunction=function(t){o.finalizeFunction(t)},o});