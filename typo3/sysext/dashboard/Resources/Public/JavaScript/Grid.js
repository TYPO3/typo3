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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","muuri","TYPO3/CMS/Core/Ajax/AjaxRequest","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,a,r,s){"use strict";a=__importDefault(a);return new class{constructor(){this.selector=".dashboard-grid",this.initialize()}initialize(){const e={dragEnabled:!0,dragSortHeuristics:{sortInterval:50,minDragDistance:10,minBounceBackAngle:1},layoutDuration:400,layoutEasing:"ease",dragPlaceholder:{enabled:!0,duration:400,createElement:e=>e.getElement().cloneNode(!0)},dragSortPredicate:{action:"move",threshold:30},dragHandle:".js-dashboard-move-widget",dragReleaseDuration:400,dragReleaseEasing:"ease",layout:{fillGaps:!1,rounding:!1}};if(null!==document.querySelector(this.selector)){const t=new a.default(this.selector,e);t.on("dragStart",()=>{document.querySelectorAll(".dashboard-item").forEach(e=>{e.classList.remove("dashboard-item--enableSelect")})}),t.on("dragReleaseEnd",()=>{document.querySelectorAll(".dashboard-item").forEach(e=>{e.classList.add("dashboard-item--enableSelect")}),this.saveItems(t)}),new s("widgetContentRendered",()=>{t.refreshItems().layout()}).delegateTo(document,".dashboard-item")}}saveItems(e){let t=e.getItems().map((function(e){return[e.getElement().getAttribute("data-widget-key"),e.getElement().getAttribute("data-widget-hash")]}));new r(TYPO3.settings.ajaxUrls.dashboard_save_widget_positions).post({widgets:t}).then(async e=>{await e.resolve()})}}}));