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
var __awaiter=this&&this.__awaiter||function(e,t,n,a){return new(n||(n=Promise))((function(r,s){function o(e){try{c(a.next(e))}catch(e){s(e)}}function i(e){try{c(a.throw(e))}catch(e){s(e)}}function c(e){var t;e.done?r(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(o,i)}c((a=a.apply(e,t||[])).next())}))};define(["require","exports","jquery","../AbstractInteractableModule","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Router"],(function(e,t,n,a,r,s,o,i){"use strict";class c extends a.AbstractInteractableModule{constructor(){super(...arguments),this.selectorSaveTrigger=".t3js-features-save"}initialize(e){this.currentModal=e,this.getContent(),e.on("click",this.selectorSaveTrigger,e=>{e.preventDefault(),this.save()})}getContent(){const e=this.getModalBody();new o(i.getUrl("featuresGetContent")).get({cache:"no-cache"}).then(t=>__awaiter(this,void 0,void 0,(function*(){const n=yield t.resolve();!0===n.success&&"undefined"!==n.html&&n.html.length>0?(e.empty().append(n.html),r.setButtons(n.buttons)):s.error("Something went wrong")})),t=>{i.handleAjaxError(t,e)})}save(){const e=this.getModalBody(),t=this.getModuleContent().data("features-save-token"),a={};n(this.findInModal("form").serializeArray()).each((e,t)=>{a[t.name]=t.value}),a["install[action]"]="featuresSave",a["install[token]"]=t,new o(i.getUrl()).post(a).then(e=>__awaiter(this,void 0,void 0,(function*(){const t=yield e.resolve();!0===t.success&&Array.isArray(t.status)?t.status.forEach(e=>{s.showMessage(e.title,e.message,e.severity)}):s.error("Something went wrong")})),t=>{i.handleAjaxError(t,e)})}}return new c}));