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
define(["require","exports","jquery","./ElementBrowser","TYPO3/CMS/Backend/LegacyTree"],function(e,t,n,i,l){"use strict";var r=function e(){l.noop(),e.File=new c,e.Selector=new o,n(function(){e.elements=n("body").data("elements"),n("[data-close]").on("click",function(t){t.preventDefault(),e.File.insertElement("file_"+n(t.currentTarget).data("fileIndex"),1===parseInt(n(t.currentTarget).data("close"),10))}),n("#t3js-importSelection").on("click",e.Selector.handle),n("#t3js-toggleSelection").on("click",e.Selector.toggle)})},c=function(){function e(){}return e.prototype.insertElement=function(e,t){var n=!1;if(void 0!==r.elements[e]){var l=r.elements[e];n=i.insertElement(l.table,l.uid,l.type,l.fileName,l.filePath,l.fileExt,l.fileIcon,"",t)}return n},e}(),o=function(){function e(){var e=this;this.toggle=function(t){t.preventDefault();var n=e.getItems();n.length&&n.each(function(e,t){t.checked=t.checked?null:"checked"})},this.handle=function(t){t.preventDefault();var n=e.getItems(),l=[];if(n.length){if(n.each(function(e,t){t.checked&&t.name&&l.push(t.name)}),l.length>0)for(var c=0;c<l.length;c++)r.File.insertElement(l[c]);i.focusOpenerAndClose()}}}return e.prototype.getItems=function(){return n("#typo3-filelist").find(".typo3-bulk-item")},e}();return new r});