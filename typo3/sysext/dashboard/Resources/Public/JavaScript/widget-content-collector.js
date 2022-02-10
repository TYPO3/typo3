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
import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import RegularEvent from"@typo3/core/event/regular-event.js";class WidgetContentCollector{constructor(){this.selector=".dashboard-item",this.initialize()}initialize(){this.registerEvents();document.querySelectorAll(this.selector).forEach(e=>{let t;t=new Event("widgetRefresh",{bubbles:!0}),e.dispatchEvent(t)})}registerEvents(){new RegularEvent("widgetRefresh",(e,t)=>{e.preventDefault(),this.getContentForWidget(t)}).delegateTo(document,this.selector)}getContentForWidget(e){const t=e.querySelector(".widget-waiting"),n=e.querySelector(".widget-content"),s=e.querySelector(".widget-error");t.classList.remove("hide"),n.classList.add("hide"),s.classList.add("hide");new AjaxRequest(TYPO3.settings.ajaxUrls.dashboard_get_widget_content).withQueryArguments({widget:e.dataset.widgetKey}).get().then(async s=>{const i=await s.resolve();let r;null!==n&&(n.innerHTML=i.content,n.classList.remove("hide")),null!==t&&t.classList.add("hide");const o={bubbles:!0};r=Object.keys(i.eventdata).length>0?new CustomEvent("widgetContentRendered",{...o,detail:i.eventdata}):new Event("widgetContentRendered",o),e.dispatchEvent(r)}).catch(n=>{null!==s&&s.classList.remove("hide"),null!==t&&t.classList.add("hide"),console.warn(`Error while retrieving widget [${e.dataset.widgetKey}] content: ${n.message}`)})}}export default new WidgetContentCollector;