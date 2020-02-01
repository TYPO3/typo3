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
define(["require","exports","jquery","TYPO3/CMS/Core/Ajax/AjaxRequest"],(function(e,t,a,s){"use strict";let r=e("muuri");return new class{constructor(){this.selector=".dashboard-grid",a(()=>{this.initialize()})}initialize(){const e={dragEnabled:!0,dragSortHeuristics:{sortInterval:50,minDragDistance:10,minBounceBackAngle:1},layoutDuration:400,layoutEasing:"ease",dragPlaceholder:{enabled:!0,duration:400,createElement:e=>e.getElement().cloneNode(!0)},dragSortPredicate:{action:"move",threshold:30},dragStartPredicate:{handle:".js-dashboard-move-widget"},dragReleaseDuration:400,dragReleaseEasing:"ease",layout:{fillGaps:!1,rounding:!1}};if(a(this.selector).length){const t=new r(this.selector,e);t.on("dragStart",()=>{a(".dashboard-item").removeClass("dashboard-item--enableSelect")}),t.on("dragReleaseEnd",()=>{a(".dashboard-item").addClass("dashboard-item--enableSelect"),this.saveItems(t)}),a(".dashboard-item").on("widgetContentRendered",()=>{t.refreshItems().layout()})}}saveItems(e){let t=e.getItems().map((function(e){return[e.getElement().getAttribute("data-widget-key"),e.getElement().getAttribute("data-widget-hash")]}));new s(TYPO3.settings.ajaxUrls["ext-dashboard-save-widget-positions"]).post({widgets:t}).then(async e=>{await e.resolve()})}}}));