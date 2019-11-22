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
var __awaiter=this&&this.__awaiter||function(t,e,n,a){return new(n||(n=Promise))((function(r,i){function o(t){try{s(a.next(t))}catch(t){i(t)}}function c(t){try{s(a.throw(t))}catch(t){i(t)}}function s(t){var e;t.done?r(t.value):(e=t.value,e instanceof n?e:new n((function(t){t(e)}))).then(o,c)}s((a=a.apply(t,e||[])).next())}))};define(["require","exports","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Router","../AbstractInteractableModule"],(function(t,e,n,a,r,i){"use strict";class o extends i.AbstractInteractableModule{initialize(t){this.currentModal=t,this.getData()}getData(){const t=this.getModalBody();new a(r.getUrl("systemInformationGetData")).get({cache:"no-cache"}).then(e=>__awaiter(this,void 0,void 0,(function*(){const a=yield e.resolve();!0===a.success?t.empty().append(a.html):n.error("Something went wrong")})),e=>{r.handleAjaxError(e,t)})}}return new o}));