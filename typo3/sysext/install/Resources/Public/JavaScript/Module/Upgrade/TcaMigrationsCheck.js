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
define(["require","exports","../AbstractInteractableModule","jquery","../../Router","../../Renderable/ProgressBar","../../Renderable/FlashMessage","../../Renderable/Severity","../../Renderable/InfoBox","TYPO3/CMS/Backend/Modal"],function(e,t,r,n,s,o,i,a,c,l){"use strict";return new class extends r.AbstractInteractableModule{constructor(){super(...arguments),this.selectorCheckTrigger=".t3js-tcaMigrationsCheck-check",this.selectorOutputContainer=".t3js-tcaMigrationsCheck-output"}initialize(e){this.currentModal=e,this.check(),e.on("click",this.selectorCheckTrigger,e=>{e.preventDefault(),this.check()})}check(){const e=n(this.selectorOutputContainer),t=this.getModalBody(),r=o.render(a.loading,"Loading...","");e.empty().html(r),n.ajax({url:s.getUrl("tcaMigrationsCheck"),cache:!1,success:e=>{if(t.empty().append(e.html),l.setButtons(e.buttons),!0===e.success&&Array.isArray(e.status))if(e.status.length>0){const r=c.render(a.warning,"TCA migrations need to be applied","Check the following list and apply needed changes.");t.find(this.selectorOutputContainer).empty(),t.find(this.selectorOutputContainer).append(r),e.status.forEach(e=>{const r=c.render(e.severity,e.title,e.message);t.find(this.selectorOutputContainer).append(r)})}else{const e=c.render(a.ok,"No TCA migrations need to be applied","Your TCA looks good.");t.find(this.selectorOutputContainer).append(e)}else{const e=i.render(a.error,"Something went wrong",'Use "Check for broken extensions"');t.find(this.selectorOutputContainer).append(e)}},error:e=>{s.handleAjaxError(e,t)}})}}});