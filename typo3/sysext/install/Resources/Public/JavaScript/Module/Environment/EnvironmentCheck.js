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
define(["require","exports","jquery","../AbstractInteractableModule","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Renderable/InfoBox","../../Renderable/ProgressBar","../../Renderable/Severity","../../Router","bootstrap"],(function(e,t,r,s,n,a,o,i,l,c,d){"use strict";class u extends s.AbstractInteractableModule{constructor(){super(...arguments),this.selectorGridderBadge=".t3js-environmentCheck-badge",this.selectorExecuteTrigger=".t3js-environmentCheck-execute",this.selectorOutputContainer=".t3js-environmentCheck-output"}initialize(e){this.currentModal=e,this.runTests(),e.on("click",this.selectorExecuteTrigger,e=>{e.preventDefault(),this.runTests()})}runTests(){const e=this.getModalBody(),t=r(this.selectorGridderBadge);t.text("").hide();const s=l.render(c.loading,"Loading...","");e.find(this.selectorOutputContainer).empty().append(s),this.findInModal(this.selectorExecuteTrigger).addClass("disabled").prop("disabled",!0),new o(d.getUrl("environmentCheckGetStatus")).get({cache:"no-cache"}).then(async s=>{const o=await s.resolve();e.empty().append(o.html),n.setButtons(o.buttons);let l=0,c=0;!0===o.success&&"object"==typeof o.status?(r.each(o.status,(t,r)=>{Array.isArray(r)&&r.length>0&&r.forEach(t=>{1===t.severity&&l++,2===t.severity&&c++;const r=i.render(t.severity,t.title,t.message);e.find(this.selectorOutputContainer).append(r)})}),c>0?t.removeClass("label-warning").addClass("label-danger").text(c).show():l>0&&t.removeClass("label-error").addClass("label-warning").text(l).show()):a.error("Something went wrong")},t=>{d.handleAjaxError(t,e)})}}return new u}));