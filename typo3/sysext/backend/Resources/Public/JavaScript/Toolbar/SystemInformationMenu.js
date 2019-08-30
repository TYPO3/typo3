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
define(["require","exports","jquery","../Icons","../Storage/Persistent","../Viewport"],function(e,t,o,n,r,s){"use strict";var a;!function(e){e.containerSelector="#typo3-cms-backend-backend-toolbaritems-systeminformationtoolbaritem",e.toolbarIconSelector=".toolbar-item-icon .t3js-icon",e.menuContainerSelector=".dropdown-menu",e.moduleLinks=".t3js-systeminformation-module",e.counter=".t3js-systeminformation-counter"}(a||(a={}));class i{constructor(){this.timer=null,this.updateMenu=(()=>{const e=o(a.toolbarIconSelector,a.containerSelector),t=e.clone(),r=o(a.containerSelector).find(a.menuContainerSelector);null!==this.timer&&(clearTimeout(this.timer),this.timer=null),n.getIcon("spinner-circle-light",n.sizes.small).done(t=>{e.replaceWith(t)}),o.ajax({url:TYPO3.settings.ajaxUrls.systeminformation_render,type:"post",cache:!1,success:e=>{r.html(e),i.updateCounter(),o(a.moduleLinks).on("click",this.openModule)},complete:()=>{o(a.toolbarIconSelector,a.containerSelector).replaceWith(t)}}).done(()=>{this.timer=setTimeout(this.updateMenu,3e5)})}),s.Topbar.Toolbar.registerEvent(this.updateMenu)}static updateCounter(){const e=o(a.containerSelector).find(a.menuContainerSelector).find(".t3js-systeminformation-container"),t=o(a.counter),n=e.data("count"),r=e.data("severityclass");t.text(n).toggle(parseInt(n,10)>0),t.removeClass(),t.addClass("t3js-systeminformation-counter toolbar-item-badge badge"),""!==r&&t.addClass(r)}openModule(e){e.preventDefault(),e.stopPropagation();let t={};const n={},a=o(e.currentTarget).data("modulename"),i=o(e.currentTarget).data("moduleparams"),c=Math.floor((new Date).getTime()/1e3);r.isset("systeminformation")&&(t=JSON.parse(r.get("systeminformation"))),n[a]={lastAccess:c},o.extend(!0,t,n),r.set("systeminformation",JSON.stringify(t)).done(()=>{TYPO3.ModuleMenu.App.showModule(a,i),s.Topbar.refresh()})}}return new i});