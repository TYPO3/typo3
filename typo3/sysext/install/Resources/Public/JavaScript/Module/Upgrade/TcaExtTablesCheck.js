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
define(["require","exports","../AbstractInteractableModule","jquery","../../Router","../../Renderable/ProgressBar","../../Renderable/Severity","../../Renderable/InfoBox","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification"],function(e,t,s,n,r,a,o,c,i,l){"use strict";return new class extends s.AbstractInteractableModule{constructor(){super(...arguments),this.selectorCheckTrigger=".t3js-tcaExtTablesCheck-check",this.selectorOutputContainer=".t3js-tcaExtTablesCheck-output"}initialize(e){this.currentModal=e,this.check(),e.on("click",this.selectorCheckTrigger,e=>{e.preventDefault(),this.check()})}check(){const e=this.getModalBody(),t=n(this.selectorOutputContainer),s=a.render(o.loading,"Loading...","");t.empty().html(s),n.ajax({url:r.getUrl("tcaExtTablesCheck"),cache:!1,success:s=>{if(e.empty().append(s.html),i.setButtons(s.buttons),!0===s.success&&Array.isArray(s.status))if(s.status.length>0){const n=c.render(o.warning,"Following extensions change TCA in ext_tables.php","Check ext_tables.php files, look for ExtensionManagementUtility calls and $GLOBALS['TCA'] modifications");e.find(this.selectorOutputContainer).append(n),s.status.forEach(s=>{const n=c.render(s.severity,s.title,s.message);t.append(n),e.append(n)})}else{const t=c.render(o.ok,"No TCA changes in ext_tables.php files. Good job!","");e.find(this.selectorOutputContainer).append(t)}else l.error("Something went wrong",'Use "Check for broken extensions"')},error:t=>{r.handleAjaxError(t,e)}})}}});