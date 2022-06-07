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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","../AbstractInteractableModule","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Renderable/FlashMessage","../../Renderable/InfoBox","../../Renderable/ProgressBar","../../Renderable/Severity","../../Router"],(function(e,t,n,r,s,o,a,i,c,l,u){"use strict";n=__importDefault(n);class d extends r.AbstractInteractableModule{constructor(){super(...arguments),this.selectorCheckTrigger=".t3js-tcaMigrationsCheck-check",this.selectorOutputContainer=".t3js-tcaMigrationsCheck-output"}initialize(e){this.currentModal=e,this.check(),e.on("click",this.selectorCheckTrigger,e=>{e.preventDefault(),this.check()})}check(){this.setModalButtonsState(!1);const e=(0,n.default)(this.selectorOutputContainer),t=this.getModalBody(),r=c.render(l.loading,"Loading...","");e.empty().html(r),new o(u.getUrl("tcaMigrationsCheck")).get({cache:"no-cache"}).then(async e=>{const n=await e.resolve();if(t.empty().append(n.html),s.setButtons(n.buttons),!0===n.success&&Array.isArray(n.status))if(n.status.length>0){const e=i.render(l.warning,"TCA migrations need to be applied","Check the following list and apply needed changes.");t.find(this.selectorOutputContainer).empty(),t.find(this.selectorOutputContainer).append(e),n.status.forEach(e=>{const n=i.render(e.severity,e.title,e.message);t.find(this.selectorOutputContainer).append(n)})}else{const e=i.render(l.ok,"No TCA migrations need to be applied","Your TCA looks good.");t.find(this.selectorOutputContainer).append(e)}else{const e=a.render(l.error,"Something went wrong",'Use "Check for broken extensions"');t.find(this.selectorOutputContainer).append(e)}},e=>{u.handleAjaxError(e,t)})}}return new d}));