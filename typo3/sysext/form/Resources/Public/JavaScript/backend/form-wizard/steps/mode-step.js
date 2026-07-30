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
import{html as r}from"lit";import n from"~labels/form.form_manager_javascript";import{Categories as d}from"@typo3/backend/new-record-wizard.js";class l{constructor(e){this.context=e,this.key="template",this.title=n.get("formManager.newFormWizard.step1.progressLabel"),this.autoAdvance=!0,this.hideActions=!0,this.selected=null,this.groups=[],this.templateMap=new Map,this.groups=this.context.formManager.getTemplatesGroupedByPrototype();const a={};this.groups.forEach((i,o)=>{a[String(o)]={identifier:i.identifier,label:i.label,items:i.templates.map(t=>{const s=`${t.prototypeIdentifier}::${t.templatePath}`;return this.templateMap.set(s,t),{identifier:s,label:t.label,description:t.description??"",icon:t.iconIdentifier,iconOverlay:null,url:null,requestType:"event",defaultValues:void 0,saveAndClose:void 0,event:"form-template-selected"}})}}),this.wizardCategories=d.fromData(a)}isComplete(){return this.selected!==null}render(){return r`<typo3-backend-new-record-wizard .categories=${this.wizardCategories} displaymenu=false displayfilter=false @form-template-selected=${e=>{e.preventDefault(),this.handleSelection(e.detail.item.identifier)}}></typo3-backend-new-record-wizard>`}reset(){this.selected=null,this.context.clearStoreData(this.key)}getValue(){return this.selected}setValue(e){this.selected=e,this.context.wizard.requestUpdate()}beforeAdvance(){this.context.setStoreData(this.key,this.getValue())}getSummaryData(){const e=this.context.getStoreData(this.key);return e?[{label:n.get("formManager.form_template"),value:r`<typo3-backend-icon identifier=${e.iconIdentifier} size=small class=me-1></typo3-backend-icon>${e.label}`}]:[]}handleSelection(e){this.selected=this.templateMap.get(e)??null,this.context.setStoreData(this.key,this.getValue()),this.context.dispatchAutoAdvance()}}export{l as ModeStep,l as default};
