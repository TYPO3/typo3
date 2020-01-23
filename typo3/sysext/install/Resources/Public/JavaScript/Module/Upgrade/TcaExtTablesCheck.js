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
define(["require","exports","jquery","../AbstractInteractableModule","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Renderable/InfoBox","../../Renderable/ProgressBar","../../Renderable/Severity","../../Router"],(function(e,t,n,s,r,a,o,c,i,l,h){"use strict";class d extends s.AbstractInteractableModule{constructor(){super(...arguments),this.selectorCheckTrigger=".t3js-tcaExtTablesCheck-check",this.selectorOutputContainer=".t3js-tcaExtTablesCheck-output"}initialize(e){this.currentModal=e,this.check(),e.on("click",this.selectorCheckTrigger,e=>{e.preventDefault(),this.check()})}check(){const e=this.getModalBody(),t=n(this.selectorOutputContainer),s=i.render(l.loading,"Loading...","");t.empty().html(s),new o(h.getUrl("tcaExtTablesCheck")).get({cache:"no-cache"}).then(async n=>{const s=await n.resolve();if(e.empty().append(s.html),r.setButtons(s.buttons),!0===s.success&&Array.isArray(s.status))if(s.status.length>0){const n=c.render(l.warning,"Following extensions change TCA in ext_tables.php","Check ext_tables.php files, look for ExtensionManagementUtility calls and $GLOBALS['TCA'] modifications");e.find(this.selectorOutputContainer).append(n),s.status.forEach(n=>{const s=c.render(n.severity,n.title,n.message);t.append(s),e.append(s)})}else{const t=c.render(l.ok,"No TCA changes in ext_tables.php files. Good job!","");e.find(this.selectorOutputContainer).append(t)}else a.error("Something went wrong",'Use "Check for broken extensions"')},t=>{h.handleAjaxError(t,e)})}}return new d}));