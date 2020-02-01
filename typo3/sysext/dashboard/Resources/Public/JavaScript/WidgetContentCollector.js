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
define(["require","exports","jquery","TYPO3/CMS/Core/Ajax/AjaxRequest"],(function(e,t,i,n){"use strict";return new class{constructor(){this.selector=".dashboard-item",i(()=>{this.initialize()})}initialize(){i(this.selector).each((e,t)=>{new n(TYPO3.settings.ajaxUrls["ext-dashboard-get-widget-content"]).withQueryArguments({widget:i(t).data("widget-key")}).get().then(async e=>{const n=await e.resolve();i(t).find(".widget-content").html(n.content),i(t).find(".widget-content").removeClass("hide"),i(t).find(".widget-waiting").addClass("hide"),Object.keys(n.eventdata).length>0?i(t).trigger("widgetContentRendered",n.eventdata):i(t).trigger("widgetContentRendered")}).catch(e=>{i(t).find(".widget-error").removeClass("hide"),i(t).find(".widget-waiting").addClass("hide"),console.warn("Error while retrieving widget ["+i(t).data("widget-key")+"] content: "+e.message)})})}}}));