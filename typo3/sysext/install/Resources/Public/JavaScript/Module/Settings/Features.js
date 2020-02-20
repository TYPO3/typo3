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
define(["require","exports","jquery","../AbstractInteractableModule","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Router"],(function(e,t,s,a,n,r,o,i){"use strict";class c extends a.AbstractInteractableModule{constructor(){super(...arguments),this.selectorSaveTrigger=".t3js-features-save"}initialize(e){this.currentModal=e,this.getContent(),e.on("click",this.selectorSaveTrigger,e=>{e.preventDefault(),this.save()})}getContent(){const e=this.getModalBody();new o(i.getUrl("featuresGetContent")).get({cache:"no-cache"}).then(async t=>{const s=await t.resolve();!0===s.success&&"undefined"!==s.html&&s.html.length>0?(e.empty().append(s.html),n.setButtons(s.buttons)):r.error("Something went wrong")},t=>{i.handleAjaxError(t,e)})}save(){const e=this.getModalBody(),t=this.getModuleContent().data("features-save-token"),a={};s(this.findInModal("form").serializeArray()).each((e,t)=>{a[t.name]=t.value}),a["install[action]"]="featuresSave",a["install[token]"]=t,new o(i.getUrl()).post(a).then(async e=>{const t=await e.resolve();!0===t.success&&Array.isArray(t.status)?(t.status.forEach(e=>{r.showMessage(e.title,e.message,e.severity)}),this.getContent()):r.error("Something went wrong")},t=>{i.handleAjaxError(t,e)})}}return new c}));