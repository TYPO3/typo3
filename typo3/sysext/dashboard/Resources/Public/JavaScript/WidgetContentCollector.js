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
define(["require","exports","TYPO3/CMS/Core/Ajax/AjaxRequest","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,s,n){"use strict";return new class{constructor(){this.selector=".dashboard-item",this.initialize()}initialize(){this.registerEvents();document.querySelectorAll(this.selector).forEach(e=>{let t;t=new Event("widgetRefresh",{bubbles:!0}),e.dispatchEvent(t)})}registerEvents(){new n("widgetRefresh",(e,t)=>{e.preventDefault(),this.getContentForWidget(t)}).delegateTo(document,this.selector)}getContentForWidget(e){const t=e.querySelector(".widget-waiting"),n=e.querySelector(".widget-content"),i=e.querySelector(".widget-error");t.classList.remove("hide"),n.classList.add("hide"),i.classList.add("hide");new s(TYPO3.settings.ajaxUrls.dashboard_get_widget_content).withQueryArguments({widget:e.dataset.widgetKey}).get().then(async s=>{const i=await s.resolve();let r;null!==n&&(n.innerHTML=i.content,n.classList.remove("hide")),null!==t&&t.classList.add("hide");const d={bubbles:!0};r=Object.keys(i.eventdata).length>0?new CustomEvent("widgetContentRendered",Object.assign(Object.assign({},d),{detail:i.eventdata})):new Event("widgetContentRendered",d),e.dispatchEvent(r)}).catch(s=>{null!==i&&i.classList.remove("hide"),null!==t&&t.classList.add("hide"),console.warn(`Error while retrieving widget [${e.dataset.widgetKey}] content: ${s.message}`)})}}}));