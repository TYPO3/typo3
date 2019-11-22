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
var __awaiter=this&&this.__awaiter||function(e,t,n,r){return new(n||(n=Promise))((function(s,o){function a(e){try{c(r.next(e))}catch(e){o(e)}}function i(e){try{c(r.throw(e))}catch(e){o(e)}}function c(e){var t;e.done?s(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(a,i)}c((r=r.apply(e,t||[])).next())}))};define(["require","exports","jquery","../AbstractInteractableModule","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Renderable/InfoBox","../../Renderable/ProgressBar","../../Renderable/Severity","../../Router"],(function(e,t,n,r,s,o,a,i,c,l,h){"use strict";class u extends r.AbstractInteractableModule{constructor(){super(...arguments),this.selectorCheckTrigger=".t3js-tcaExtTablesCheck-check",this.selectorOutputContainer=".t3js-tcaExtTablesCheck-output"}initialize(e){this.currentModal=e,this.check(),e.on("click",this.selectorCheckTrigger,e=>{e.preventDefault(),this.check()})}check(){const e=this.getModalBody(),t=n(this.selectorOutputContainer),r=c.render(l.loading,"Loading...","");t.empty().html(r),new a(h.getUrl("tcaExtTablesCheck")).get({cache:"no-cache"}).then(n=>__awaiter(this,void 0,void 0,(function*(){const r=yield n.resolve();if(e.empty().append(r.html),s.setButtons(r.buttons),!0===r.success&&Array.isArray(r.status))if(r.status.length>0){const n=i.render(l.warning,"Following extensions change TCA in ext_tables.php","Check ext_tables.php files, look for ExtensionManagementUtility calls and $GLOBALS['TCA'] modifications");e.find(this.selectorOutputContainer).append(n),r.status.forEach(n=>{const r=i.render(n.severity,n.title,n.message);t.append(r),e.append(r)})}else{const t=i.render(l.ok,"No TCA changes in ext_tables.php files. Good job!","");e.find(this.selectorOutputContainer).append(t)}else o.error("Something went wrong",'Use "Check for broken extensions"')})),t=>{h.handleAjaxError(t,e)})}}return new u}));