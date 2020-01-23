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
define(["require","exports","../AbstractInteractableModule","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Renderable/InfoBox","../../Renderable/ProgressBar","../../Renderable/Severity","../../Router","bootstrap"],(function(t,e,n,s,a,r,o,i,l,c){"use strict";class d extends n.AbstractInteractableModule{constructor(){super(...arguments),this.selectorOutputContainer=".t3js-mailTest-output",this.selectorMailTestButton=".t3js-mailTest-execute"}initialize(t){this.currentModal=t,this.getData(),t.on("click",this.selectorMailTestButton,t=>{t.preventDefault(),this.send()})}getData(){const t=this.getModalBody();new r(c.getUrl("mailTestGetData")).get({cache:"no-cache"}).then(async e=>{const n=await e.resolve();!0===n.success?(t.empty().append(n.html),s.setButtons(n.buttons)):a.error("Something went wrong")},e=>{c.handleAjaxError(e,t)})}send(){const t=this.getModuleContent().data("mail-test-token"),e=this.findInModal(this.selectorOutputContainer),n=i.render(l.loading,"Loading...","");e.empty().html(n),new r(c.getUrl()).post({install:{action:"mailTest",token:t,email:this.findInModal(".t3js-mailTest-email").val()}}).then(async t=>{const n=await t.resolve();e.empty(),Array.isArray(n.status)?n.status.forEach(t=>{const n=o.render(t.severity,t.title,t.message);e.html(n)}):a.error("Something went wrong")},()=>{a.error("Something went wrong")})}}return new d}));