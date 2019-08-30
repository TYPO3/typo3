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
define(["require","exports","../AbstractInteractableModule","jquery","../../Router","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification","bootstrap"],function(e,t,s,r,i,n,a){"use strict";return new class extends s.AbstractInteractableModule{constructor(){super(...arguments),this.selectorWriteTrigger=".t3js-systemMaintainer-write",this.selectorChosenContainer=".t3js-systemMaintainer-chosen",this.selectorChosenField=".t3js-systemMaintainer-chosen-select"}initialize(t){this.currentModal=t,window.location!==window.parent.location?top.require(["TYPO3/CMS/Install/chosen.jquery.min"],()=>{this.getList()}):e(["TYPO3/CMS/Install/chosen.jquery.min"],()=>{this.getList()}),t.on("click",this.selectorWriteTrigger,e=>{e.preventDefault(),this.write()})}getList(){const e=this.getModalBody();r.ajax({url:i.getUrl("systemMaintainerGetList"),cache:!1,success:t=>{if(!0===t.success){Array.isArray(t.status)&&t.status.forEach(e=>{a.success(e.title,e.message)}),e.html(t.html),n.setButtons(t.buttons),Array.isArray(t.users)&&t.users.forEach(t=>{let s=t.username;t.disable&&(s="[DISABLED] "+s);const i=r("<option>",{value:t.uid}).text(s);t.isSystemMaintainer&&i.attr("selected","selected"),e.find(this.selectorChosenField).append(i)});const s={".t3js-systemMaintainer-chosen-select":{width:"100%",placeholder_text_multiple:"users"}};for(const t in s)s.hasOwnProperty(t)&&e.find(t).chosen(s[t]);e.find(this.selectorChosenContainer).show(),e.find(this.selectorChosenField).trigger("chosen:updated")}},error:t=>{i.handleAjaxError(t,e)}})}write(){const e=this.getModalBody(),t=this.getModuleContent().data("system-maintainer-write-token"),s=this.findInModal(this.selectorChosenField).val();r.ajax({method:"POST",url:i.getUrl(),data:{install:{users:s,token:t,action:"systemMaintainerWrite"}},success:e=>{!0===e.success?Array.isArray(e.status)&&e.status.forEach(e=>{a.success(e.title,e.message)}):a.error("Something went wrong")},error:t=>{i.handleAjaxError(t,e)}})}}});