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
define(["require","exports","jquery","TYPO3/CMS/Backend/Icons","TYPO3/CMS/Backend/Viewport"],function(e,t,o,n,c){"use strict";var r;!function(e){e.containerSelector="#typo3-cms-opendocs-backend-toolbaritems-opendocstoolbaritem",e.closeSelector=".t3js-topbar-opendocs-close",e.menuContainerSelector=".dropdown-menu",e.toolbarIconSelector=".toolbar-item-icon .t3js-icon",e.openDocumentsItemsSelector=".t3js-topbar-opendocs-item",e.counterSelector="#tx-opendocs-counter",e.entrySelector=".t3js-open-doc"}(r||(r={}));class s{constructor(){this.hashDataAttributeName="opendocsidentifier",this.toggleMenu=(()=>{o(".scaffold").removeClass("scaffold-toolbar-expanded"),o(r.containerSelector).toggleClass("open")}),c.Topbar.Toolbar.registerEvent(()=>{this.initializeEvents(),this.updateMenu()})}static updateNumberOfDocs(){const e=o(r.containerSelector).find(r.openDocumentsItemsSelector).length;o(r.counterSelector).text(e).toggle(e>0)}updateMenu(){let e=o(r.toolbarIconSelector,r.containerSelector),t=e.clone();n.getIcon("spinner-circle-light",n.sizes.small).done(t=>{e.replaceWith(t)}),o.ajax({url:TYPO3.settings.ajaxUrls.opendocs_menu,type:"post",cache:!1,success:e=>{o(r.containerSelector).find(r.menuContainerSelector).html(e),s.updateNumberOfDocs(),o(r.toolbarIconSelector,r.containerSelector).replaceWith(t)}})}initializeEvents(){o(r.containerSelector).on("click",r.closeSelector,e=>{e.preventDefault();const t=o(e.currentTarget).data(this.hashDataAttributeName);t&&this.closeDocument(t)}).on("click",r.entrySelector,e=>{e.preventDefault();const t=o(e.currentTarget);this.toggleMenu(),window.jump(t.attr("href"),"web_list","web",t.data("pid"))})}closeDocument(e){o.ajax({url:TYPO3.settings.ajaxUrls.opendocs_closedoc,type:"post",cache:!1,data:{md5sum:e},success:e=>{o(r.menuContainerSelector,r.containerSelector).html(e),s.updateNumberOfDocs(),o(r.containerSelector).toggleClass("open")}})}}let a;return a=new s,"undefined"!=typeof TYPO3&&(TYPO3.OpendocsMenu=a),a});