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
define(["require","exports","TYPO3/CMS/Core/Ajax/AjaxRequest"],(function(e,t,n){"use strict";return new class{constructor(){this.selector=".dashboard-item",this.initialize()}initialize(){document.querySelectorAll(this.selector).forEach(e=>{const t=e.querySelector(".widget-waiting"),s=e.querySelector(".widget-content"),i=e.querySelector(".widget-error");new n(TYPO3.settings.ajaxUrls.dashboard_get_widget_content).withQueryArguments({widget:e.dataset.widgetKey}).get().then(async n=>{const i=await n.resolve();let r;null!==s&&(s.innerHTML=i.content,s.classList.remove("hide")),null!==t&&t.classList.add("hide");const a={bubbles:!0};r=Object.keys(i.eventdata).length>0?new CustomEvent("widgetContentRendered",Object.assign(Object.assign({},a),{detail:i.eventdata})):new Event("widgetContentRendered",a),e.dispatchEvent(r)}).catch(n=>{null!==i&&i.classList.remove("hide"),null!==t&&t.classList.add("hide"),console.warn(`Error while retrieving widget [${e.dataset.widgetKey}] content: ${n.message}`)})})}}}));