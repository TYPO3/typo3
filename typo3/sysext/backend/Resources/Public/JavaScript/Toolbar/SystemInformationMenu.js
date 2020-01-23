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
define(["require","exports","jquery","TYPO3/CMS/Core/Ajax/AjaxRequest","../Icons","../Storage/Persistent","../Viewport"],(function(e,t,o,n,r,s,a){"use strict";var i;!function(e){e.containerSelector="#typo3-cms-backend-backend-toolbaritems-systeminformationtoolbaritem",e.toolbarIconSelector=".toolbar-item-icon .t3js-icon",e.menuContainerSelector=".dropdown-menu",e.moduleLinks=".t3js-systeminformation-module",e.counter=".t3js-systeminformation-counter"}(i||(i={}));class l{constructor(){this.timer=null,this.updateMenu=()=>{const e=o(i.toolbarIconSelector,i.containerSelector),t=e.clone(),s=o(i.containerSelector).find(i.menuContainerSelector);null!==this.timer&&(clearTimeout(this.timer),this.timer=null),r.getIcon("spinner-circle-light",r.sizes.small).then(t=>{e.replaceWith(t)}),new n(TYPO3.settings.ajaxUrls.systeminformation_render).get().then(async e=>{s.html(await e.resolve()),l.updateCounter(),o(i.moduleLinks).on("click",this.openModule)}).finally(()=>{o(i.toolbarIconSelector,i.containerSelector).replaceWith(t),this.timer=setTimeout(this.updateMenu,3e5)})},a.Topbar.Toolbar.registerEvent(this.updateMenu)}static updateCounter(){const e=o(i.containerSelector).find(i.menuContainerSelector).find(".t3js-systeminformation-container"),t=o(i.counter),n=e.data("count"),r=e.data("severityclass");t.text(n).toggle(parseInt(n,10)>0),t.removeClass(),t.addClass("t3js-systeminformation-counter toolbar-item-badge badge"),""!==r&&t.addClass(r)}openModule(e){e.preventDefault(),e.stopPropagation();let t={};const n={},r=o(e.currentTarget).data("modulename"),i=o(e.currentTarget).data("moduleparams"),l=Math.floor((new Date).getTime()/1e3);s.isset("systeminformation")&&(t=JSON.parse(s.get("systeminformation"))),n[r]={lastAccess:l},o.extend(!0,t,n),s.set("systeminformation",JSON.stringify(t)).done(()=>{TYPO3.ModuleMenu.App.showModule(r,i),a.Topbar.refresh()})}}return new l}));