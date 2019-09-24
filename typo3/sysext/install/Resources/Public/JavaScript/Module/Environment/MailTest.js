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
define(["require","exports","../AbstractInteractableModule","jquery","../../Router","../../Renderable/ProgressBar","../../Renderable/Severity","../../Renderable/InfoBox","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification","bootstrap"],function(t,e,r,s,a,n,o,i,l,c){"use strict";return new class extends r.AbstractInteractableModule{constructor(){super(...arguments),this.selectorOutputContainer=".t3js-mailTest-output",this.selectorMailTestButton=".t3js-mailTest-execute"}initialize(t){this.currentModal=t,this.getData(),t.on("click",this.selectorMailTestButton,t=>{t.preventDefault(),this.send()})}getData(){const t=this.getModalBody();s.ajax({url:a.getUrl("mailTestGetData"),cache:!1,success:e=>{!0===e.success?(t.empty().append(e.html),l.setButtons(e.buttons)):c.error("Something went wrong")},error:e=>{a.handleAjaxError(e,t)}})}send(){const t=this.getModuleContent().data("mail-test-token"),e=this.findInModal(this.selectorOutputContainer),r=n.render(o.loading,"Loading...","");e.empty().html(r),s.ajax({url:a.getUrl(),method:"POST",data:{install:{action:"mailTest",token:t,email:this.findInModal(".t3js-mailTest-email").val()}},cache:!1,success:t=>{e.empty(),Array.isArray(t.status)?t.status.forEach(t=>{const r=i.render(t.severity,t.title,t.message);e.html(r)}):c.error("Something went wrong")},error:()=>{c.error("Something went wrong")}})}}});