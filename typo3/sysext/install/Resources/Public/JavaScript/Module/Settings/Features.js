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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","../AbstractInteractableModule","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Router"],(function(e,t,s,a,r,n,o,l){"use strict";s=__importDefault(s);class c extends a.AbstractInteractableModule{constructor(){super(...arguments),this.selectorSaveTrigger=".t3js-features-save"}initialize(e){this.currentModal=e,this.getContent(),e.on("click",this.selectorSaveTrigger,e=>{e.preventDefault(),this.save()})}getContent(){const e=this.getModalBody();new o(l.getUrl("featuresGetContent")).get({cache:"no-cache"}).then(async t=>{const s=await t.resolve();!0===s.success&&"undefined"!==s.html&&s.html.length>0?(e.empty().append(s.html),r.setButtons(s.buttons)):n.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},t=>{l.handleAjaxError(t,e)})}save(){this.setModalButtonsState(!1);const e=this.getModalBody(),t=this.getModuleContent().data("features-save-token"),a={};(0,s.default)(this.findInModal("form").serializeArray()).each((e,t)=>{a[t.name]=t.value}),a["install[action]"]="featuresSave",a["install[token]"]=t,new o(l.getUrl()).post(a).then(async e=>{const t=await e.resolve();!0===t.success&&Array.isArray(t.status)?(t.status.forEach(e=>{n.showMessage(e.title,e.message,e.severity)}),this.getContent()):n.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},t=>{l.handleAjaxError(t,e)})}}return new c}));