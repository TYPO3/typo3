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
var __awaiter=this&&this.__awaiter||function(t,e,n,a){return new(n||(n=Promise))((function(o,r){function i(t){try{l(a.next(t))}catch(t){r(t)}}function s(t){try{l(a.throw(t))}catch(t){r(t)}}function l(t){var e;t.done?o(t.value):(e=t.value,e instanceof n?e:new n((function(t){t(e)}))).then(i,s)}l((a=a.apply(t,e||[])).next())}))};define(["require","exports","../AbstractInteractableModule","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Renderable/InfoBox","../../Renderable/ProgressBar","../../Renderable/Severity","../../Router","bootstrap"],(function(t,e,n,a,o,r,i,s,l,c){"use strict";class u extends n.AbstractInteractableModule{constructor(){super(...arguments),this.selectorOutputContainer=".t3js-mailTest-output",this.selectorMailTestButton=".t3js-mailTest-execute"}initialize(t){this.currentModal=t,this.getData(),t.on("click",this.selectorMailTestButton,t=>{t.preventDefault(),this.send()})}getData(){const t=this.getModalBody();new r(c.getUrl("mailTestGetData")).get({cache:"no-cache"}).then(e=>__awaiter(this,void 0,void 0,(function*(){const n=yield e.resolve();!0===n.success?(t.empty().append(n.html),a.setButtons(n.buttons)):o.error("Something went wrong")})),e=>{c.handleAjaxError(e,t)})}send(){const t=this.getModuleContent().data("mail-test-token"),e=this.findInModal(this.selectorOutputContainer),n=s.render(l.loading,"Loading...","");e.empty().html(n),new r(c.getUrl()).post({install:{action:"mailTest",token:t,email:this.findInModal(".t3js-mailTest-email").val()}}).then(t=>__awaiter(this,void 0,void 0,(function*(){const n=yield t.resolve();e.empty(),Array.isArray(n.status)?n.status.forEach(t=>{const n=i.render(t.severity,t.title,t.message);e.html(n)}):o.error("Something went wrong")})),()=>{o.error("Something went wrong")})}}return new u}));