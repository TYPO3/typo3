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
define(["require","exports","../AbstractInteractableModule","jquery","../../Router","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification"],function(e,t,r,s,a,n,o){"use strict";return new class extends r.AbstractInteractableModule{constructor(){super(...arguments),this.selectorSaveTrigger=".t3js-features-save"}initialize(e){this.currentModal=e,this.getContent(),e.on("click",this.selectorSaveTrigger,e=>{e.preventDefault(),this.save()})}getContent(){const e=this.getModalBody();s.ajax({url:a.getUrl("featuresGetContent"),cache:!1,success:t=>{!0===t.success&&"undefined"!==t.html&&t.html.length>0?(e.empty().append(t.html),n.setButtons(t.buttons)):o.error("Something went wrong")},error:t=>{a.handleAjaxError(t,e)}})}save(){const e=this.getModalBody(),t=this.getModuleContent().data("features-save-token"),r={};s(this.findInModal("form").serializeArray()).each((e,t)=>{r[t.name]=t.value}),r["install[action]"]="featuresSave",r["install[token]"]=t,s.ajax({url:a.getUrl(),method:"POST",data:r,cache:!1,success:e=>{!0===e.success&&Array.isArray(e.status)?e.status.forEach(e=>{o.showMessage(e.title,e.message,e.severity)}):o.error("Something went wrong")},error:t=>{a.handleAjaxError(t,e)}})}}});