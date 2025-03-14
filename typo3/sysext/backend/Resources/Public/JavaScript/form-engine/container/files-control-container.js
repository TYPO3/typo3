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
import{MessageUtility}from"@typo3/backend/utility/message-utility.js";import{AjaxDispatcher}from"@typo3/backend/form-engine/inline-relation/ajax-dispatcher.js";import NProgress from"nprogress";import Sortable from"sortablejs";import FormEngine from"@typo3/backend/form-engine.js";import FormEngineValidation from"@typo3/backend/form-engine-validation.js";import Icons from"@typo3/backend/icons.js";import InfoWindow from"@typo3/backend/info-window.js";import Modal from"@typo3/backend/modal.js";import DocumentService from"@typo3/core/document-service.js";import RegularEvent from"@typo3/core/event/regular-event.js";import Severity from"@typo3/backend/severity.js";import Utility from"@typo3/backend/utility.js";import{selector}from"@typo3/core/literals.js";var Selectors,States,Separators,SortDirections;!function(e){e.toggleSelector='[data-bs-toggle="formengine-file"]',e.controlSectionSelector=".t3js-formengine-file-header-control",e.deleteRecordButtonSelector=".t3js-editform-delete-file-reference",e.enableDisableRecordButtonSelector=".t3js-toggle-visibility-button",e.infoWindowButton='[data-action="infowindow"]',e.synchronizeLocalizeRecordButtonSelector=".t3js-synchronizelocalize-button",e.controlContainer=".t3js-file-controls"}(Selectors||(Selectors={})),function(e){e.new="isNewFileReference",e.visible="panel-visible",e.collapsed="panel-collapsed",e.notLoaded="t3js-not-loaded"}(States||(States={})),function(e){e.structureSeparator="-"}(Separators||(Separators={})),function(e){e.DOWN="down",e.UP="up"}(SortDirections||(SortDirections={}));class FilesControlContainer extends HTMLElement{constructor(){super(...arguments),this.container=null,this.recordsContainer=null,this.ajaxDispatcher=null,this.appearance=null,this.requestQueue={},this.progressQueue={},this.handlePostMessage=e=>{if(!MessageUtility.verifyOrigin(e.origin))throw"Denied message sent by "+e.origin;if("typo3:foreignRelation:insert"===e.data.actionName){if(void 0===e.data.objectGroup)throw"No object group defined for message";if(e.data.objectGroup!==this.container.dataset.objectGroup)return;this.importRecord([e.data.objectGroup,e.data.uid]).then((()=>{if(e.source){const t={actionName:"typo3:foreignRelation:inserted",objectGroup:e.data.objectId,table:e.data.table,uid:e.data.uid};MessageUtility.send(t,e.source)}}))}if("typo3:foreignRelation:delete"===e.data.actionName){if(e.data.objectGroup!==this.container.dataset.objectGroup)return;const t=e.data.directRemoval||!1,o=[e.data.objectGroup,e.data.uid].join("-");this.deleteRecord(o,t)}}}async connectedCallback(){const e=this.getAttribute("identifier")||"";await DocumentService.ready(),this.container=this.querySelector(selector`[id="${e}"]`),null!==this.container&&(this.recordsContainer=this.container.querySelector(selector`[id="${this.container.getAttribute("id")}_records"]`),this.ajaxDispatcher=new AjaxDispatcher(this.container.dataset.objectGroup),this.registerEvents())}registerEvents(){this.registerInfoButton(),this.registerSort(),this.registerEnableDisableButton(),this.registerDeleteButton(),this.registerSynchronizeLocalize(),this.registerToggle(),new RegularEvent("message",this.handlePostMessage).bindTo(window),this.getAppearance().useSortable&&new Sortable(this.recordsContainer,{group:this.recordsContainer.getAttribute("id"),handle:".sortableHandle",onSort:()=>{this.updateSorting()}})}getFileReferenceContainer(e){return this.container.querySelector(selector`[data-object-id="${e}"]`)}getCollapseButton(e){return this.container.querySelector(selector`[aria-controls="${e}_fields"]`)}collapseElement(e,t){const o=this.getCollapseButton(t);e.classList.remove(States.visible),e.classList.add(States.collapsed),o.setAttribute("aria-expanded","false")}expandElement(e,t){const o=this.getCollapseButton(t);e.classList.remove(States.collapsed),e.classList.add(States.visible),o.setAttribute("aria-expanded","true")}isNewRecord(e){return this.getFileReferenceContainer(e).classList.contains(States.new)}updateExpandedCollapsedStateLocally(e,t){const o=this.getFileReferenceContainer(e),i=this.container.querySelectorAll('[name="uc[inlineView]['+o.dataset.topmostParentTable+"]["+o.dataset.topmostParentUid+"]"+o.dataset.fieldName+'"]');i.length&&(i[0].value=t?"1":"0")}registerToggle(){new RegularEvent("click",((e,t)=>{e.preventDefault(),e.stopImmediatePropagation(),this.loadRecordDetails(t.closest(Selectors.toggleSelector).parentElement.dataset.objectId)})).delegateTo(this.container,`${Selectors.toggleSelector} .form-irre-header-cell:not(${Selectors.controlSectionSelector}`)}registerSort(){new RegularEvent("click",((e,t)=>{e.preventDefault(),e.stopImmediatePropagation(),this.changeSortingByButton(t.closest("[data-object-id]").dataset.objectId,t.dataset.direction)})).delegateTo(this.container,Selectors.controlSectionSelector+' [data-action="sort"]')}createRecord(e,t,o=null){let i=this.container.dataset.objectGroup;null!==o&&(i+=Separators.structureSeparator+o),null!==o?(this.getFileReferenceContainer(i).insertAdjacentHTML("afterend",t),this.memorizeAddRecord(e,o)):(this.recordsContainer.insertAdjacentHTML("beforeend",t),this.memorizeAddRecord(e,null))}async importRecord(e,t){return this.ajaxDispatcher.send(this.ajaxDispatcher.newRequest(this.ajaxDispatcher.getEndpoint("file_reference_create")),e).then((async e=>{this.isBelowMax()&&this.createRecord(e.compilerInput.uid,e.data,void 0!==t?t:null)}))}registerEnableDisableButton(){new RegularEvent("click",((e,t)=>{e.preventDefault(),e.stopImmediatePropagation();const o=t.closest("[data-object-id]").dataset.objectId,i=this.getFileReferenceContainer(o),n=selector`data${i.dataset.fieldName}[${t.dataset.hiddenField}]`,r=this.recordsContainer.querySelector('[data-formengine-input-name="'+n+'"'),a=this.recordsContainer.querySelector('[name="'+n+'"');null!==r&&null!==a&&(r.checked=!r.checked,a.value=r.checked?"1":"0",FormEngineValidation.markFieldAsChanged(r));const s="t3-form-field-container-inline-hidden";let l;i.classList.contains(s)?(l="actions-edit-hide",i.classList.remove(s)):(l="actions-edit-unhide",i.classList.add(s)),Icons.getIcon(l,Icons.sizes.small).then((e=>{t.replaceChild(document.createRange().createContextualFragment(e),t.querySelector(".t3js-icon"))}))})).delegateTo(this.container,Selectors.enableDisableRecordButtonSelector)}registerInfoButton(){new RegularEvent("click",((e,t)=>{e.preventDefault(),e.stopImmediatePropagation(),InfoWindow.showItem(t.dataset.infoTable,t.dataset.infoUid)})).delegateTo(this.container,Selectors.infoWindowButton)}registerDeleteButton(){new RegularEvent("click",((e,t)=>{e.preventDefault(),e.stopImmediatePropagation();const o=TYPO3.lang["label.confirm.delete_record.title"]||"Delete this record?",i=(TYPO3.lang["label.confirm.delete_record.content"]||"Are you sure you want to delete the record '%s'?").replace("%s",t.dataset.recordInfo);Modal.confirm(o,i,Severity.warning,[{text:TYPO3.lang["buttons.confirm.delete_record.no"]||"Cancel",active:!0,btnClass:"btn-default",name:"no",trigger:(e,t)=>t.hideModal()},{text:TYPO3.lang["buttons.confirm.delete_record.yes"]||"Yes, delete this record",btnClass:"btn-warning",name:"yes",trigger:(e,o)=>{this.deleteRecord(t.closest("[data-object-id]").dataset.objectId),o.hideModal()}}])})).delegateTo(this.container,Selectors.deleteRecordButtonSelector)}registerSynchronizeLocalize(){new RegularEvent("click",((e,t)=>{e.preventDefault(),e.stopImmediatePropagation(),this.ajaxDispatcher.send(this.ajaxDispatcher.newRequest(this.ajaxDispatcher.getEndpoint("file_reference_synchronizelocalize")),[this.container.dataset.objectGroup,t.dataset.type]).then((async e=>{this.recordsContainer.insertAdjacentHTML("beforeend",e.data);const t=this.container.dataset.objectGroup+Separators.structureSeparator;for(const o of e.compilerInput.delete)this.deleteRecord(t+o,!0);for(const o of Object.values(e.compilerInput.localize)){if(void 0!==o.remove){const e=this.getFileReferenceContainer(t+o.remove);e.parentElement.removeChild(e)}this.memorizeAddRecord(o.uid,null)}}))})).delegateTo(this.container,Selectors.synchronizeLocalizeRecordButtonSelector)}loadRecordDetails(e){const t=this.recordsContainer.querySelector(selector`[id="${e}_fields"]`),o=this.getFileReferenceContainer(e),i=void 0!==this.requestQueue[e];if(null!==t&&!o.classList.contains(States.notLoaded))this.collapseExpandRecord(e);else{const n=this.getProgress(e,o.dataset.objectIdHash);if(i)this.requestQueue[e].abort(),delete this.requestQueue[e],delete this.progressQueue[e],n.done();else{const i=this.ajaxDispatcher.newRequest(this.ajaxDispatcher.getEndpoint("file_reference_details"));this.ajaxDispatcher.send(i,[e]).then((async i=>{delete this.requestQueue[e],delete this.progressQueue[e],o.classList.remove(States.notLoaded),t.innerHTML=i.data,this.collapseExpandRecord(e),n.done(),FormEngine.reinitialize(),FormEngineValidation.initializeInputFields(),FormEngineValidation.validate(this.container)})),this.requestQueue[e]=i,n.start()}}}collapseExpandRecord(e){const t=this.getFileReferenceContainer(e),o=!0===this.getAppearance().expandSingle,i=t.classList.contains(States.collapsed);let n=[];const r=[];o&&i&&(n=this.collapseAllRecords(t.dataset.objectUid)),t.classList.contains(States.collapsed)?this.expandElement(t,e):this.collapseElement(t,e),this.isNewRecord(e)?this.updateExpandedCollapsedStateLocally(e,i):i?r.push(t.dataset.objectUid):i||n.push(t.dataset.objectUid),this.ajaxDispatcher.send(this.ajaxDispatcher.newRequest(this.ajaxDispatcher.getEndpoint("file_reference_expandcollapse")),[e,r.join(","),n.join(",")])}memorizeAddRecord(e,t=null){const o=this.getFormFieldForElements();if(null===o)return;let i=Utility.trimExplode(",",o.value);if(t){const o=[];for(let n=0;n<i.length;n++)i[n].length&&o.push(i[n]),t===i[n]&&o.push(e);i=o}else i.push(e);o.value=i.join(","),FormEngineValidation.markFieldAsChanged(o),document.dispatchEvent(new Event("change")),this.redrawSortingButtons(this.container.dataset.objectGroup,i),this.isBelowMax()||this.toggleContainerControls(!1),FormEngine.reinitialize(),FormEngineValidation.initializeInputFields(),FormEngineValidation.validate(this.container)}memorizeRemoveRecord(e){const t=this.getFormFieldForElements();if(null===t)return[];const o=Utility.trimExplode(",",t.value),i=o.indexOf(e);return i>-1&&(o.splice(i,1),t.value=o.join(","),FormEngineValidation.markFieldAsChanged(t),document.dispatchEvent(new Event("change")),this.redrawSortingButtons(this.container.dataset.objectGroup,o)),o}changeSortingByButton(e,t){const o=this.getFileReferenceContainer(e),i=o.dataset.objectUid,n=Array.from(this.recordsContainer.children).map((e=>e.dataset.objectUid)),r=n.indexOf(i);let a=!1;if(t===SortDirections.UP&&r>0?(n[r]=n[r-1],n[r-1]=i,a=!0):t===SortDirections.DOWN&&r<n.length-1&&(n[r]=n[r+1],n[r+1]=i,a=!0),a){const e=this.container.dataset.objectGroup+Separators.structureSeparator,i=t===SortDirections.UP?1:0;o.parentElement.insertBefore(this.getFileReferenceContainer(e+n[r-i]),this.getFileReferenceContainer(e+n[r+1-i])),this.updateSorting()}}updateSorting(){const e=this.getFormFieldForElements();if(null===e)return;const t=Array.from(this.recordsContainer.querySelectorAll(selector`[data-object-parent-group="${this.container.dataset.objectGroup}"][data-placeholder-record="0"]`)).map((e=>e.dataset.objectUid));e.value=t.join(","),FormEngineValidation.markFieldAsChanged(e),document.dispatchEvent(new Event("formengine:files:sorting-changed")),document.dispatchEvent(new Event("change")),this.redrawSortingButtons(this.container.dataset.objectGroup,t)}deleteRecord(e,t=!1){const o=this.getFileReferenceContainer(e),i=o.dataset.objectUid;if(o.classList.add("t3js-file-reference-deleted"),!this.isNewRecord(e)&&!t){const e=this.container.querySelector(selector`[name="cmd${o.dataset.fieldName}[delete]"]`);e.removeAttribute("disabled"),o.parentElement.insertAdjacentElement("afterbegin",e)}new RegularEvent("transitionend",(()=>{o.remove(),FormEngineValidation.validate(this.container)})).bindTo(o),this.memorizeRemoveRecord(i),o.classList.add("form-irre-object--deleted"),this.isBelowMax()&&this.toggleContainerControls(!0)}toggleContainerControls(e){this.container.querySelectorAll(Selectors.controlContainer).forEach((t=>{t.querySelectorAll("button, a").forEach((t=>{t.style.display=e?null:"none"}))}))}getProgress(e,t){const o="#"+t+"_header";let i;return void 0!==this.progressQueue[e]?i=this.progressQueue[e]:(i=NProgress,i.configure({parent:o,showSpinner:!1}),this.progressQueue[e]=i),i}collapseAllRecords(e){const t=this.getFormFieldForElements(),o=[];if(null!==t){const i=Utility.trimExplode(",",t.value);for(const t of i){if(t===e)continue;const i=this.container.dataset.objectGroup+Separators.structureSeparator+t,n=this.getFileReferenceContainer(i);n.classList.contains(States.visible)&&(this.collapseElement(n,i),this.isNewRecord(i)?this.updateExpandedCollapsedStateLocally(i,!1):o.push(t))}}return o}getFormFieldForElements(){const e=this.container.querySelectorAll(selector`[name="${this.container.dataset.formField}"]`);return e.length>0?e[0]:null}redrawSortingButtons(e,t=[]){if(0===t.length){const e=this.getFormFieldForElements();null!==e&&(t=Utility.trimExplode(",",e.value))}0!==t.length&&t.forEach(((o,i)=>{const n=this.getFileReferenceContainer(e+Separators.structureSeparator+o),r=this.container.querySelector('[id="'+n.dataset.objectIdHash+'_header"]'),a=r.querySelector('[data-action="sort"][data-direction="'+SortDirections.UP+'"]');if(null!==a){let e="actions-move-up";0===i?(a.classList.add("disabled"),e="empty-empty"):a.classList.remove("disabled"),Icons.getIcon(e,Icons.sizes.small).then((e=>{a.replaceChild(document.createRange().createContextualFragment(e),a.querySelector(".t3js-icon"))}))}const s=r.querySelector('[data-action="sort"][data-direction="'+SortDirections.DOWN+'"]');if(null!==s){let e="actions-move-down";i===t.length-1?(s.classList.add("disabled"),e="empty-empty"):s.classList.remove("disabled"),Icons.getIcon(e,Icons.sizes.small).then((e=>{s.replaceChild(document.createRange().createContextualFragment(e),s.querySelector(".t3js-icon"))}))}}))}isBelowMax(){const e=this.getFormFieldForElements();if(null===e)return!0;if(void 0!==TYPO3.settings.FormEngineInline.config[this.container.dataset.objectGroup]){if(Utility.trimExplode(",",e.value).length>=TYPO3.settings.FormEngineInline.config[this.container.dataset.objectGroup].max)return!1}return!0}getAppearance(){if(null===this.appearance&&(this.appearance={},"string"==typeof this.container.dataset.appearance))try{this.appearance=JSON.parse(this.container.dataset.appearance)}catch(e){console.error(e)}return this.appearance}}window.customElements.define("typo3-formengine-container-files",FilesControlContainer);