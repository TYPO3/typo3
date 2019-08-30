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
define(["require","exports","../AbstractInteractableModule","jquery","../../Router","../../Renderable/ProgressBar","../../Renderable/Severity","../../Renderable/InfoBox","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification"],function(e,t,r,n,s,a,o,c,i,l){"use strict";return new class extends r.AbstractInteractableModule{constructor(){super(...arguments),this.selectorCheckTrigger=".t3js-tcaExtTablesCheck-check",this.selectorOutputContainer=".t3js-tcaExtTablesCheck-output"}initialize(e){this.currentModal=e,this.check(),e.on("click",this.selectorCheckTrigger,e=>{e.preventDefault(),this.check()})}check(){const e=this.getModalBody(),t=n(this.selectorOutputContainer),r=a.render(o.loading,"Loading...","");t.empty().html(r),n.ajax({url:s.getUrl("tcaExtTablesCheck"),cache:!1,success:r=>{if(e.empty().append(r.html),i.setButtons(r.buttons),!0===r.success&&Array.isArray(r.status))if(r.status.length>0){const n=c.render(o.warning,"Extensions change TCA in ext_tables.php",'Check for ExtensionManagementUtility and $GLOBALS["TCA"]');e.find(this.selectorOutputContainer).append(n),r.status.forEach(r=>{const n=c.render(r.severity,r.title,r.message);t.append(n),e.append(n)})}else{const t=c.render(o.ok,"No TCA changes in ext_tables.php files. Good job!","");e.find(this.selectorOutputContainer).append(t)}else l.error("Something went wrong",'Use "Check for broken extensions"')},error:t=>{s.handleAjaxError(t,e)}})}}});