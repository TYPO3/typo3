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
define(["require","exports","../AbstractInteractableModule","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Renderable/InfoBox","../../Renderable/ProgressBar","../../Renderable/Severity","../../Router","bootstrap"],(function(t,e,s,n,a,o,r,i,l,c){"use strict";class u extends s.AbstractInteractableModule{constructor(){super(...arguments),this.selectorOutputContainer=".t3js-mailTest-output",this.selectorMailTestButton=".t3js-mailTest-execute"}initialize(t){this.currentModal=t,this.getData(),t.on("click",this.selectorMailTestButton,t=>{t.preventDefault(),this.send()}),t.on("submit","form",t=>{t.preventDefault(),this.send()})}getData(){const t=this.getModalBody();new o(c.getUrl("mailTestGetData")).get({cache:"no-cache"}).then(async e=>{const s=await e.resolve();!0===s.success?(t.empty().append(s.html),n.setButtons(s.buttons)):a.error("Something went wrong")},e=>{c.handleAjaxError(e,t)})}send(){this.setModalButtonsState(!1);const t=this.getModuleContent().data("mail-test-token"),e=this.findInModal(this.selectorOutputContainer),s=i.render(l.loading,"Loading...","");e.empty().html(s),new o(c.getUrl()).post({install:{action:"mailTest",token:t,email:this.findInModal(".t3js-mailTest-email").val()}}).then(async t=>{const s=await t.resolve();e.empty(),Array.isArray(s.status)?s.status.forEach(t=>{const s=r.render(t.severity,t.title,t.message);e.html(s)}):a.error("Something went wrong")},()=>{a.error("Something went wrong")}).finally(()=>{this.setModalButtonsState(!0)})}}return new u}));