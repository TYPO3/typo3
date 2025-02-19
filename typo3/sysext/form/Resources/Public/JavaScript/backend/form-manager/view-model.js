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
import e from"jquery";import F from"@typo3/backend/modal.js";import b from"@typo3/backend/severity.js";import t from"@typo3/backend/multi-step-wizard.js";import c from"@typo3/backend/icons.js";import T from"@typo3/backend/notification.js";import y from"@typo3/core/security-utility.js";import{selector as S}from"@typo3/core/literals.js";import Y from"@typo3/backend/sortable-table.js";const g=new y;var l;(function(r){r.newFormModalTrigger='[data-identifier="newForm"]',r.duplicateFormModalTrigger='[data-identifier="duplicateForm"]',r.removeFormModalTrigger='[data-identifier="removeForm"]',r.newFormModeButton='[data-identifier="newFormModeButton"]',r.newFormName='[data-identifier="newFormName"]',r.newFormSavePath='[data-identifier="newFormSavePath"]',r.newFormPrototypeName='[data-identifier="newFormPrototypeName"]',r.newFormTemplate='[data-identifier="newFormTemplate"]',r.duplicateFormName='[data-identifier="duplicateFormName"]',r.duplicateFormSavePath='[data-identifier="duplicateFormSavePath"]',r.showReferences='[data-identifier="showReferences"]',r.referenceLink='[data-identifier="referenceLink"]',r.moduleBody=".module-body.t3js-module-body",r.t3Logo=".t3-message-page-logo",r.t3Footer="#t3-footer",r.formsTable="#forms"})(l||(l={}));function k(r){e(l.newFormModalTrigger).on("click",function(h){h.preventDefault(),t.addSlide("new-form-step-1",TYPO3.lang["formManager.newFormWizard.step1.title"],"",b.notice,TYPO3.lang["formManager.newFormWizard.step1.progressLabel"],async function(m){const o=await c.getIcon("actions-plus",c.sizes.small),n=await c.getIcon("form-page",c.sizes.large),s=await c.getIcon("apps-pagetree-page-default",c.sizes.large);let a;const u=t.setup.$carousel.closest(".modal"),d=u.find(".modal-footer").find('button[name="next"]');t.blurCancelStep(),t.lockNextStep(),t.lockPrevStep(),r.getAccessibleFormStorageFolders().length===0&&(a='<div class="new-form-modal"><div class="row"><label class="col col-form-label">'+TYPO3.lang["formManager.newFormWizard.step1.noStorages"]+"</label></div></div>",m.html(a),r.assert(!1,"No accessible form storage folders",1477506500)),a='<div class="new-form-modal">',a+='<div class="card-container"><div class="card card-size-medium"><div class="card-header"><div class="card-icon">'+s+'</div><div class="card-header-body"><h2 class="card-title">'+TYPO3.lang["formManager.blankForm.label"]+'</h2><span class="card-subtitle">'+TYPO3.lang["formManager.blankForm.subtitle"]+'</span></div></div><div class="card-body"><p class="card-text">'+TYPO3.lang["formManager.blankForm.description"]+'</p></div><div class="card-footer"><button type="button" class="btn btn-default" data-inline="1" value="blank" data-identifier="newFormModeButton">'+o+" "+TYPO3.lang["formManager.blankForm.label"]+'</button></div></div><div class="card card-size-medium"><div class="card-header"><div class="card-icon">'+n+'</div><div class="card-header-body"><h2 class="card-title">'+TYPO3.lang["formManager.predefinedForm.label"]+'</h2><span class="card-subtitle">'+TYPO3.lang["formManager.predefinedForm.subtitle"]+'</span></div></div><div class="card-body"><p class="card-text">'+TYPO3.lang["formManager.predefinedForm.description"]+'</p></div><div class="card-footer"><button type="button" class="btn btn-default" data-inline="1" value="predefined" data-identifier="newFormModeButton">'+o+" "+TYPO3.lang["formManager.predefinedForm.label"]+"</button></div></div>",a+="</div>",m.html(a),e(l.newFormModeButton,u).on("click",function(f){t.set("newFormMode",e(f.currentTarget).val()),t.next()}),d.on("click",async function(){m.html(e("<div />",{class:"text-center"}).append(await c.getIcon("spinner-circle",c.sizes.default,null,null)).prop("outerHTML"))})}),t.addSlide("new-form-step-2",TYPO3.lang["formManager.newFormWizard.step2.title"],"",b.notice,top.TYPO3.lang["wizard.progressStep.configure"],function(m,o){let n,s;t.lockNextStep(),t.unlockPrevStep();const a=t.setup.$carousel.closest(".modal"),u=a.find(".modal-footer").find('button[name="next"]'),d=r.getAccessibleFormStorageFolders();if(o.savePath||(t.set("savePath",d[0].value),t.set("savePathName",d[0].label)),d.length>1){s=e('<select class="new-form-save-path form-select" id="new-form-save-path" data-identifier="newFormSavePath" />');for(let p=0,w=d.length;p<w;++p){const P=new Option(d[p].label,d[p].value);e(s).append(P)}}const i=r.getPrototypes();r.assert(i.length>0,"No prototypes available",1477506501),o.prototypeName||(t.set("prototypeName",i[0].value),t.set("prototypeNameName",i[0].label));const f=e('<select class="new-form-prototype-name form-select" id="new-form-prototype-name" data-identifier="newFormPrototypeName" />');for(let p=0,w=i.length;p<w;++p){const P=new Option(i[p].label,i[p].value);e(f).append(P)}let v=r.getTemplatesForPrototype(i[0].value);r.assert(v.length>0,"No templates available",1477506502),o.templatePath||(t.set("templatePath",v[0].value),t.set("templatePathName",v[0].label));const M=e('<select class="new-form-template form-select" id="new-form-template" data-identifier="newFormTemplate" />');for(let p=0,w=v.length;p<w;++p){const P=new Option(v[p].label,v[p].value);e(M).append(P)}n='<div class="new-form-modal">',o.newFormMode==="blank"?(n+='<h5 class="form-section-headline">'+TYPO3.lang["formManager.blankForm.label"]+"</h5>",t.set("templatePath","EXT:form/Resources/Private/Backend/Templates/FormEditor/Yaml/NewForms/BlankForm.yaml"),t.set("templatePathName",TYPO3.lang["formManager.blankForm.label"])):(n+='<h5 class="form-section-headline">'+TYPO3.lang["formManager.predefinedForm.label"]+"</h5>",i.length>1&&(n+='<div class="mb-3"><label for="new-form-prototype-name"><strong>'+TYPO3.lang["formManager.form_prototype"]+'</strong></label><div class="formengine-field-item t3js-formengine-field-item"><div class="form-control-wrap">'+e(f)[0].outerHTML+"</div></div></div>"),v.length>1&&(n+='<div class="mb-3"><label for="new-form-template"><strong>'+TYPO3.lang["formManager.form_template"]+'</strong></label><div class="formengine-field-item t3js-formengine-field-item"><div class="form-description">'+TYPO3.lang["formManager.form_template_description"]+'</div><div class="form-control-wrap">'+e(M)[0].outerHTML+"</div></div></div>")),n+='<div class="mb-3"><label for="new-form-name"><strong>'+TYPO3.lang["formManager.form_name"]+'</strong></label><div class="formengine-field-item t3js-formengine-field-item"><div class="form-description">'+TYPO3.lang["formManager.form_name_description"]+'</div><div class="form-control-wrap">',o.formName?(n+='<input class="form-control" id="new-form-name" data-identifier="newFormName" value="'+g.encodeHtml(o.formName)+'" />',setTimeout(function(){t.unlockNextStep()},200)):n+='<input class="form-control has-error" id="new-form-name" data-identifier="newFormName" />',n+="</div></div></div>",s&&(n+='<div class="mb-3"><label for="new-form-save-path"><strong>'+TYPO3.lang["formManager.form_save_path"]+'</strong></label><div class="formengine-field-item t3js-formengine-field-item"><div class="form-description">'+TYPO3.lang["formManager.form_save_path_description"]+'</div><div class="form-control-wrap">'+e(s)[0].outerHTML+"</div></div></div>"),n+="</div>",m.html(n),o.savePath&&e(l.newFormSavePath,a).val(o.savePath),o.templatePath&&e(l.newFormTemplate,a).val(o.templatePath),i.length>1?e(l.newFormPrototypeName,a).focus():v.length>1&&e(l.newFormTemplate,a).focus();const N=function(){e(l.newFormTemplate,a).on("change",function(){t.set("templatePath",e(l.newFormTemplate+" option:selected",a).val()),t.set("templatePathName",e(l.newFormTemplate+" option:selected",a).text()),t.set("templatePathOnPrev",e(l.newFormTemplate+" option:selected",a).val())})};e(l.newFormPrototypeName,a).on("change",function(p){t.set("prototypeName",e(l.newFormPrototypeName+" option:selected",a).val()),t.set("prototypeNameName",e(l.newFormPrototypeName+" option:selected",a).text()),v=r.getTemplatesForPrototype(e(p.currentTarget).val()),e(l.newFormTemplate,a).off().empty();for(let w=0,P=v.length;w<P;++w){const O=new Option(v[w].label,v[w].value);e(l.newFormTemplate,a).append(O),t.set("templatePath",v[0].value),t.set("templatePathName",v[0].label)}N()}),N(),o.prototypeName&&(e(l.newFormPrototypeName,a).val(o.prototypeName),e(l.newFormPrototypeName,a).trigger("change"),o.templatePathOnPrev&&(e(l.newFormTemplate,a).find(S`option[value="${o.templatePathOnPrev}"]`).prop("selected",!0),e(l.newFormTemplate,a).trigger("change"))),e(l.newFormName,a).focus(),e(l.newFormName,a).on("keyup paste",function(p){e(p.currentTarget).val().length>0?(e(p.currentTarget).removeClass("has-error"),t.unlockNextStep(),t.set("formName",e(p.currentTarget).val()),"code"in p&&p.code==="Enter"&&t.triggerStepButton("next")):(e(p.currentTarget).addClass("has-error"),t.lockNextStep())}),e(l.newFormSavePath,a).on("change",function(){t.set("savePath",e(l.newFormSavePath+" option:selected",a).val()),t.set("savePathName",e(l.newFormSavePath+" option:selected",a).text())}),o.newFormMode!=="blank"&&!o.templatePathName&&t.set("templatePathName",e(l.newFormTemplate+" option:selected",a).text()),u.on("click",async function(){t.setup.forceSelection=!1,m.html(e("<div />",{class:"text-center"}).append(await c.getIcon("spinner-circle",c.sizes.default,null,null)).prop("outerHTML"))})}),t.addSlide("new-form-step-3",TYPO3.lang["formManager.newFormWizard.step3.title"],"",b.notice,TYPO3.lang["formManager.newFormWizard.step3.progressLabel"],async function(m,o){const n=await c.getIcon("actions-cog",c.sizes.small),s=await c.getIcon("actions-file-t3d",c.sizes.small),a=await c.getIcon("actions-tag",c.sizes.small),u=await c.getIcon("actions-database",c.sizes.small),i=t.setup.$carousel.closest(".modal").find(".modal-footer").find('button[name="next"]');let f='<div class="new-form-modal">';f+='<div class="mb-3"><h5 class="form-section-headline">'+TYPO3.lang["formManager.newFormWizard.step3.check"]+"</h5><p>"+TYPO3.lang["formManager.newFormWizard.step3.message"]+'</p></div><div class="alert alert-notice">',o.prototypeNameName&&(f+='<div class="row my-1"><div class="col col-sm-6">'+n+" "+TYPO3.lang["formManager.form_prototype"]+'</div><div class="col">'+g.encodeHtml(o.prototypeNameName)+"</div></div>"),o.templatePathName&&(f+='<div class="row my-1"><div class="col col-sm-6">'+s+" "+TYPO3.lang["formManager.form_template"]+'</div><div class="col">'+g.encodeHtml(o.templatePathName)+"</div></div>"),f+='<div class="row my-1"><div class="col col-sm-6">'+a+" "+TYPO3.lang["formManager.form_name"]+'</div><div class="col">'+g.encodeHtml(o.formName)+'</div></div><div class="row my-1"><div class="col col-sm-6">'+u+" "+TYPO3.lang["formManager.form_save_path"]+'</div><div class="col">'+g.encodeHtml(o.savePathName)+"</div></div>",f+="</div></div>",m.html(f),i.focus(),i.on("click",async function(){t.setup.forceSelection=!1,m.html(e("<div />",{class:"text-center"}).append(await c.getIcon("spinner-circle",c.sizes.default,null,null)).prop("outerHTML"))})}),t.addFinalProcessingSlide(function(){e.post(r.getAjaxEndpoint("create"),{formName:t.setup.settings.formName,templatePath:t.setup.settings.templatePath,prototypeName:t.setup.settings.prototypeName,savePath:t.setup.settings.savePath},function(m){m.status==="success"?document.location=m.url:T.error(TYPO3.lang["formManager.newFormWizard.step4.errorTitle"],TYPO3.lang["formManager.newFormWizard.step4.errorMessage"]+" "+m.message),t.dismiss()}).fail(function(m,o,n){const s=new DOMParser,a=s.parseFromString(m.responseText,"text/html"),u=e(a.body);T.error(o,n,2),t.dismiss(),e(l.t3Logo,u).remove(),e(l.t3Footer,u).remove(),e(l.moduleBody).html(u.html())})}).then(function(){t.show()})})}function x(r){e(l.removeFormModalTrigger).on("click",function(h){const m=[];h.preventDefault();const o=e(h.currentTarget);m.push({text:TYPO3.lang["formManager.cancel"],active:!0,btnClass:"btn-default",name:"cancel",trigger:function(n,s){s.hideModal()}}),m.push({text:TYPO3.lang["formManager.remove_form"],active:!0,btnClass:"btn-danger",name:"createform",trigger:function(n,s){e.post(r.getAjaxEndpoint("delete"),{formPersistenceIdentifier:o.data("formPersistenceIdentifier")},function(a){a.status==="success"?document.location=a.url:T.error(a.title,a.message),s.hideModal()})}}),F.show(TYPO3.lang["formManager.remove_form_title"],TYPO3.lang["formManager.remove_form_message"].replace("{0}",o.data("formName")),b.error,m)})}function z(r){e(l.duplicateFormModalTrigger).on("click",function(h){h.preventDefault();const m=e(h.currentTarget);t.addSlide("duplicate-form-step-1",TYPO3.lang["formManager.duplicateFormWizard.step1.title"].replace("{0}",m.data("formName")),"",b.notice,top.TYPO3.lang["wizard.progressStep.configure"],function(o){let n,s;t.lockPrevStep(),t.lockNextStep();const a=t.setup.$carousel.closest(".modal"),u=a.find(".modal-footer").find('button[name="next"]'),d=r.getAccessibleFormStorageFolders();if(r.assert(d.length>0,"No accessible form storage folders",1477649539),t.set("formPersistenceIdentifier",m.data("formPersistenceIdentifier")),t.set("savePath",d[0].value),d.length>1){s=e('<select id="duplicate-form-save-path" class="form-select" data-identifier="duplicateFormSavePath" />');for(let i=0,f=d.length;i<f;++i){const v=new Option(d[i].label,d[i].value);e(s).append(v)}}n='<div class="duplicate-form-modal"><h2 class="h3 form-section-headline">'+TYPO3.lang["formManager.new_form_name"]+'</h2><div class="mb-3"><label for="duplicate-form-name"><strong>'+TYPO3.lang["formManager.form_name"]+'</strong></label><div class="formengine-field-item t3js-formengine-field-item"><div class="form-description">'+TYPO3.lang["formManager.form_name_description"]+'</div><div class="form-control-wrap"><input id="duplicate-form-name" class="form-control has-error" data-identifier="duplicateFormName" /></div></div></div>',s&&(n+='<div class="mb-3"><label for="duplicate-form-save-path"><strong>'+TYPO3.lang["formManager.form_save_path"]+'</strong></label><div class="formengine-field-item t3js-formengine-field-item"><div class="form-description">'+TYPO3.lang["formManager.form_save_path_description"]+'</div><div class="form-control-wrap">'+e(s)[0].outerHTML+"</div></div></div>"),n+="</div>",o.html(n),e(l.duplicateFormName,a).focus(),e(l.duplicateFormName,a).on("keyup paste",function(i){const f=e(event.currentTarget);f.val().length>0?(f.removeClass("has-error"),t.unlockNextStep(),t.set("formName",f.val()),"code"in i&&i.code==="Enter"&&t.triggerStepButton("next")):(f.addClass("has-error"),t.lockNextStep())}),u.on("click",async function(){t.setup.forceSelection=!1,t.set("confirmationDuplicateFormName",m.data("formName")),d.length>1?(t.set("savePath",e(l.duplicateFormSavePath+" option:selected",a).val()),t.set("confirmationDuplicateFormSavePath",e(l.duplicateFormSavePath+" option:selected",a).text())):(t.set("savePath",d[0].value),t.set("confirmationDuplicateFormSavePath",d[0].label)),o.html(e("<div />",{class:"text-center"}).append(await c.getIcon("spinner-circle",c.sizes.default,null,null)).prop("outerHTML"))})}),t.addSlide("duplicate-form-step-2",TYPO3.lang["formManager.duplicateFormWizard.step2.title"],"",b.notice,TYPO3.lang["formManager.duplicateFormWizard.step2.progressLabel"],async function(o,n){const s=await c.getIcon("actions-file-t3d",c.sizes.small),a=await c.getIcon("actions-tag",c.sizes.small),u=await c.getIcon("actions-database",c.sizes.small);t.unlockPrevStep(),t.unlockNextStep();const i=t.setup.$carousel.closest(".modal").find(".modal-footer").find('button[name="next"]');let f='<div class="new-form-modal"><div class="row"><div class="col">';f+='<div class="mb-3"><h5 class="form-section-headline">'+TYPO3.lang["formManager.duplicateFormWizard.step2.check"]+"</h5><p>"+TYPO3.lang["formManager.newFormWizard.step3.message"]+'</p></div><div class="alert alert-notice"><div class="dropdown-table-row"><div class="dropdown-table-column dropdown-table-icon">'+s+'</div><div class="dropdown-table-column dropdown-table-title">'+TYPO3.lang["formManager.form_copied"]+'</div><div class="dropdown-table-column dropdown-table-value">'+g.encodeHtml(n.confirmationDuplicateFormName)+'</div></div><div class="dropdown-table-row"><div class="dropdown-table-column dropdown-table-icon">'+a+'</div><div class="dropdown-table-column dropdown-table-title">'+TYPO3.lang["formManager.form_name"]+'</div><div class="dropdown-table-column dropdown-table-value">'+g.encodeHtml(n.formName)+'</div></div><div class="dropdown-table-row"><div class="dropdown-table-column dropdown-table-icon">'+u+'</div><div class="dropdown-table-column dropdown-table-title">'+TYPO3.lang["formManager.form_save_path"]+'</div><div class="dropdown-table-column dropdown-table-value">'+g.encodeHtml(n.confirmationDuplicateFormSavePath)+"</div></div></div>",f+="</div></div></div>",o.html(f),i.focus(),i.on("click",async function(){t.setup.forceSelection=!1,o.html(e("<div />",{class:"text-center"}).append(await c.getIcon("spinner-circle",c.sizes.default,null,null)).prop("outerHTML"))})}),t.addFinalProcessingSlide(function(){e.post(r.getAjaxEndpoint("duplicate"),{formName:t.setup.settings.formName,formPersistenceIdentifier:t.setup.settings.formPersistenceIdentifier,savePath:t.setup.settings.savePath},function(o){o.status==="success"?document.location=o.url:T.error(TYPO3.lang["formManager.duplicateFormWizard.step3.errorTitle"],TYPO3.lang["formManager.duplicateFormWizard.step3.errorMessage"]+" "+o.message),t.dismiss()}).fail(function(o,n,s){const a=new DOMParser,u=a.parseFromString(o.responseText,"text/html"),d=e(u.body);T.error(n,s,2),t.dismiss(),e(l.t3Logo,d).remove(),e(l.t3Footer,d).remove(),e(l.moduleBody).html(d.html())})}).then(function(){t.show()})})}function _(r){e(l.showReferences).on("click",h=>{h.preventDefault();const m=e(h.currentTarget),o=r.getAjaxEndpoint("references")+"&formPersistenceIdentifier="+m.data("formPersistenceIdentifier");e.get(o,async function(n){let s;const a=[];a.push({text:TYPO3.lang["formManager.cancel"],active:!0,btnClass:"btn-default",name:"cancel",trigger:function(i,f){f.hideModal()}});const u=n.references.length,d=await c.getIcon("actions-open",c.sizes.small);if(u>0){s='<div><h2 class="h3">'+TYPO3.lang["formManager.references.headline"].replace("{0}",g.encodeHtml(m.data("formName")))+'</h2></div><div class="table-fit"><table id="forms" class="table table-striped table-hover"><thead><tr><th class="col-icon"></th><th class="col-recordtitle">'+TYPO3.lang["formManager.table.field.title"]+"</th><th>"+TYPO3.lang["formManager.table.field.uid"]+'</th><th class="col-control nowrap"><span class="visually-hidden">'+TYPO3.lang["formManager.table.field._CONTROL_"]+"</span></th></tr></thead><tbody>";for(let i=0,f=n.references.length;i<f;++i)s+='<tr><td class="col-icon">'+n.references[i].recordIcon+'</td><td class="col-recordtitle"><a href="'+g.encodeHtml(n.references[i].recordEditUrl)+'" data-identifier="referenceLink">'+g.encodeHtml(n.references[i].recordTitle)+"</a></td><td>"+g.encodeHtml(n.references[i].recordUid)+'</td><td class="col-control"><div class="btn-group" role="group"><a href="'+g.encodeHtml(n.references[i].recordEditUrl)+'" data-identifier="referenceLink" class="btn btn-default" title="'+TYPO3.lang["formManager.btn.edit.title"]+'">'+d+"</a></div></td></tr>";s+="</tbody></table></div>"}else s="<div><h1>"+TYPO3.lang["formManager.references.title"].replace("{0}",g.encodeHtml(n.formPersistenceIdentifier))+"</h1></div><div>"+TYPO3.lang["formManager.no_references"]+"</div>";s=e(s),e(l.referenceLink,s).on("click",function(i){i.preventDefault(),F.currentModal.hideModal(),document.location=e(i.currentTarget).prop("href")}),F.show(TYPO3.lang["formManager.references.title"].replace("{0}",m.data("formName")),s,b.notice,a)}).fail(function(n,s,a){n.status!==0&&T.error(s,a,2)})})}function H(){const r=document.querySelector(l.formsTable);r!==null&&new Y(r)}function L(r){x(r),k(r),z(r),_(r),H()}export{L as bootstrap};
