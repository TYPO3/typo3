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
define(["require","exports","../AbstractInteractableModule","jquery","../../Router","../../Renderable/ProgressBar","../../Renderable/InfoBox","../../Renderable/Severity","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification","bootstrap"],function(e,t,r,s,n,a,o,i,l,d){"use strict";return new class extends r.AbstractInteractableModule{constructor(){super(...arguments),this.selectorGridderBadge=".t3js-environmentCheck-badge",this.selectorExecuteTrigger=".t3js-environmentCheck-execute",this.selectorOutputContainer=".t3js-environmentCheck-output"}initialize(e){this.currentModal=e,this.runTests(),e.on("click",this.selectorExecuteTrigger,e=>{e.preventDefault(),this.runTests()})}runTests(){const e=this.getModalBody(),t=s(this.selectorGridderBadge);t.text("").hide();const r=a.render(i.loading,"Loading...","");e.find(this.selectorOutputContainer).empty().append(r),this.findInModal(this.selectorExecuteTrigger).addClass("disabled").prop("disabled",!0),s.ajax({url:n.getUrl("environmentCheckGetStatus"),cache:!1,success:r=>{e.empty().append(r.html),l.setButtons(r.buttons);let n=0,a=0;!0===r.success&&"object"==typeof r.status?(s.each(r.status,(t,r)=>{Array.isArray(r)&&r.length>0&&r.forEach(t=>{1===t.severity&&n++,2===t.severity&&a++;const r=o.render(t.severity,t.title,t.message);e.find(this.selectorOutputContainer).append(r)})}),a>0?t.removeClass("label-warning").addClass("label-danger").text(a).show():n>0&&t.removeClass("label-error").addClass("label-warning").text(n).show()):d.error("Something went wrong")},error:t=>{n.handleAjaxError(t,e)}})}}});