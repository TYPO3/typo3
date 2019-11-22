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
var __awaiter=this&&this.__awaiter||function(e,t,n,a){return new(n||(n=Promise))((function(s,r){function i(e){try{o(a.next(e))}catch(e){r(e)}}function c(e){try{o(a.throw(e))}catch(e){r(e)}}function o(e){var t;e.done?s(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(i,c)}o((a=a.apply(e,t||[])).next())}))};define(["require","exports","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Router"],(function(e,t,n,a,s){"use strict";return new class{initialize(e){e.addClass("disabled").prop("disabled",!0),new a(s.getUrl("cacheClearAll","maintenance")).get({cache:"no-cache"}).then(e=>__awaiter(this,void 0,void 0,(function*(){const t=yield e.resolve();!0===t.success&&Array.isArray(t.status)?t.status.length>0&&t.status.forEach(e=>{n.success(e.title,e.message)}):n.error("Something went wrong clearing caches")})),()=>{n.error("Clearing caches went wrong on the server side. Check the system for broken extensions or missing database tables and try again")}).finally(()=>{e.removeClass("disabled").prop("disabled",!1)})}}}));