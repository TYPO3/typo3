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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","../AbstractInteractableModule","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Renderable/InfoBox","../../Renderable/ProgressBar","../../Renderable/Severity","../../Router"],(function(e,t,s,n,a,o,r,i,c,l,u){"use strict";s=__importDefault(s);class h extends n.AbstractInteractableModule{constructor(){super(...arguments),this.selectorCheckTrigger=".t3js-tcaExtTablesCheck-check",this.selectorOutputContainer=".t3js-tcaExtTablesCheck-output"}initialize(e){this.currentModal=e,this.check(),e.on("click",this.selectorCheckTrigger,e=>{e.preventDefault(),this.check()})}check(){this.setModalButtonsState(!1);const e=this.getModalBody(),t=(0,s.default)(this.selectorOutputContainer),n=c.render(l.loading,"Loading...","");t.empty().html(n),new r(u.getUrl("tcaExtTablesCheck")).get({cache:"no-cache"}).then(async s=>{const n=await s.resolve();if(e.empty().append(n.html),a.setButtons(n.buttons),!0===n.success&&Array.isArray(n.status))if(n.status.length>0){const s=i.render(l.warning,"Following extensions change TCA in ext_tables.php","Check ext_tables.php files, look for ExtensionManagementUtility calls and $GLOBALS['TCA'] modifications");e.find(this.selectorOutputContainer).append(s),n.status.forEach(s=>{const n=i.render(s.severity,s.title,s.message);t.append(n),e.append(n)})}else{const t=i.render(l.ok,"No TCA changes in ext_tables.php files. Good job!","");e.find(this.selectorOutputContainer).append(t)}else o.error("Something went wrong",'Please use the module "Check for broken extensions" to find a possible extension causing this issue.')},t=>{u.handleAjaxError(t,e)})}}return new h}));