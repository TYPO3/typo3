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
define(["require","exports","../AbstractInteractableModule","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Renderable/InfoBox","../../Renderable/ProgressBar","../../Renderable/Severity","../../Router","bootstrap"],(function(e,t,s,o,n,a,r,l,i,c){"use strict";class u extends s.AbstractInteractableModule{constructor(){super(...arguments),this.selectorOutputContainer=".t3js-mailTest-output",this.selectorMailTestButton=".t3js-mailTest-execute"}initialize(e){this.currentModal=e,this.getData(),e.on("click",this.selectorMailTestButton,e=>{e.preventDefault(),this.send()}),e.on("submit","form",e=>{e.preventDefault(),this.send()})}getData(){const e=this.getModalBody();new a(c.getUrl("mailTestGetData")).get({cache:"no-cache"}).then(async t=>{const s=await t.resolve();!0===s.success?(e.empty().append(s.html),o.setButtons(s.buttons)):n.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},t=>{c.handleAjaxError(t,e)})}send(){this.setModalButtonsState(!1);const e=this.getModuleContent().data("mail-test-token"),t=this.findInModal(this.selectorOutputContainer),s=l.render(i.loading,"Loading...","");t.empty().html(s),new a(c.getUrl()).post({install:{action:"mailTest",token:e,email:this.findInModal(".t3js-mailTest-email").val()}}).then(async e=>{const s=await e.resolve();t.empty(),Array.isArray(s.status)?s.status.forEach(e=>{const s=r.render(e.severity,e.title,e.message);t.html(s)}):n.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},()=>{n.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")}).finally(()=>{this.setModalButtonsState(!0)})}}return new u}));