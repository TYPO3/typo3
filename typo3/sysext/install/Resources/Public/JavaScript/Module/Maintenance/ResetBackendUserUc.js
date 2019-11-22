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
var __awaiter=this&&this.__awaiter||function(e,t,n,s){return new(n||(n=Promise))((function(r,a){function i(e){try{c(s.next(e))}catch(e){a(e)}}function o(e){try{c(s.throw(e))}catch(e){a(e)}}function c(e){var t;e.done?r(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(i,o)}c((s=s.apply(e,t||[])).next())}))};define(["require","exports","TYPO3/CMS/Core/Ajax/AjaxRequest","TYPO3/CMS/Backend/Notification","../../Router"],(function(e,t,n,s,r){"use strict";return new class{initialize(e){e.addClass("disabled").prop("disabled",!0),new n(r.getUrl("resetBackendUserUc")).get({cache:"no-cache"}).then(e=>__awaiter(this,void 0,void 0,(function*(){const t=yield e.resolve();!0===t.success&&Array.isArray(t.status)?t.status.length>0&&t.status.forEach(e=>{s.success(e.message)}):s.error("Something went wrong ...")})),()=>{s.error("Dumping autoload files went wrong on the server side. Check the system for broken extensions and try again")}).finally(()=>{e.removeClass("disabled").prop("disabled",!1)})}}}));