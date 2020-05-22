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
define(["require","exports","jquery","../AbstractInteractableModule","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Renderable/InfoBox","../../Renderable/ProgressBar","../../Renderable/Severity","../../Router","bootstrap"],(function(e,t,s,r,n,a,o,i,c,l,d){"use strict";class u extends r.AbstractInteractableModule{constructor(){super(...arguments),this.selectorGridderBadge=".t3js-environmentCheck-badge",this.selectorExecuteTrigger=".t3js-environmentCheck-execute",this.selectorOutputContainer=".t3js-environmentCheck-output"}initialize(e){this.currentModal=e,this.runTests(),e.on("click",this.selectorExecuteTrigger,e=>{e.preventDefault(),this.runTests()})}runTests(){this.setModalButtonsState(!1);const e=this.getModalBody(),t=s(this.selectorGridderBadge);t.text("").hide();const r=c.render(l.loading,"Loading...","");e.find(this.selectorOutputContainer).empty().append(r),new o(d.getUrl("environmentCheckGetStatus")).get({cache:"no-cache"}).then(async r=>{const o=await r.resolve();e.empty().append(o.html),n.setButtons(o.buttons);let c=0,l=0;!0===o.success&&"object"==typeof o.status?(s.each(o.status,(t,s)=>{Array.isArray(s)&&s.length>0&&s.forEach(t=>{1===t.severity&&c++,2===t.severity&&l++;const s=i.render(t.severity,t.title,t.message);e.find(this.selectorOutputContainer).append(s)})}),l>0?t.removeClass("label-warning").addClass("label-danger").text(l).show():c>0&&t.removeClass("label-error").addClass("label-warning").text(c).show()):a.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},t=>{d.handleAjaxError(t,e)})}}return new u}));