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
define(["require","exports","jquery","../Icons","../Storage/Persistent","../Viewport"],(function(e,t,n,o,r,i){"use strict";var a;return function(e){e.containerSelector="#typo3-cms-backend-backend-toolbaritems-systeminformationtoolbaritem",e.toolbarIconSelector=".toolbar-item-icon .t3js-icon",e.menuContainerSelector=".dropdown-menu",e.moduleLinks=".t3js-systeminformation-module",e.counter=".t3js-systeminformation-counter"}(a||(a={})),new(function(){function e(){var t=this;this.timer=null,this.updateMenu=function(){var r=n(a.toolbarIconSelector,a.containerSelector),i=r.clone(),s=n(a.containerSelector).find(a.menuContainerSelector);null!==t.timer&&(clearTimeout(t.timer),t.timer=null),o.getIcon("spinner-circle-light",o.sizes.small).done((function(e){r.replaceWith(e)})),n.ajax({url:TYPO3.settings.ajaxUrls.systeminformation_render,type:"post",cache:!1,success:function(o){s.html(o),e.updateCounter(),n(a.moduleLinks).on("click",t.openModule)},complete:function(){n(a.toolbarIconSelector,a.containerSelector).replaceWith(i)}}).done((function(){t.timer=setTimeout(t.updateMenu,3e5)}))},i.Topbar.Toolbar.registerEvent(this.updateMenu)}return e.updateCounter=function(){var e=n(a.containerSelector).find(a.menuContainerSelector).find(".t3js-systeminformation-container"),t=n(a.counter),o=e.data("count"),r=e.data("severityclass");t.text(o).toggle(parseInt(o,10)>0),t.removeClass(),t.addClass("t3js-systeminformation-counter toolbar-item-badge badge"),""!==r&&t.addClass(r)},e.prototype.openModule=function(e){e.preventDefault(),e.stopPropagation();var t={},o={},a=n(e.currentTarget).data("modulename"),s=n(e.currentTarget).data("moduleparams"),c=Math.floor((new Date).getTime()/1e3);r.isset("systeminformation")&&(t=JSON.parse(r.get("systeminformation"))),o[a]={lastAccess:c},n.extend(!0,t,o),r.set("systeminformation",JSON.stringify(t)).done((function(){TYPO3.ModuleMenu.App.showModule(a,s),i.Topbar.refresh()}))},e}())}));