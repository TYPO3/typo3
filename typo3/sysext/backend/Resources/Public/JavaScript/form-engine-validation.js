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
import{DateTime as p}from"luxon";import g from"@typo3/backend/hashing/md5.js";import y from"@typo3/backend/modal.js";import v from"@typo3/backend/severity.js";import h from"@typo3/backend/utility.js";import k from"@typo3/core/event/regular-event.js";import w from"@typo3/backend/utility/dom-helper.js";import{selector as d}from"@typo3/core/literals.js";import N from"@typo3/backend/form/submit-interceptor.js";import{FormEngineReview as x}from"@typo3/backend/form-engine-review.js";let u,S=!1;const b=new Map;class r{static{this.rulesSelector="[data-formengine-validation-rules]"}static{this.inputSelector="[data-formengine-input-params]"}static{this.markerSelector=".t3js-formengine-validation-marker"}static{this.labelSelector=".t3js-formengine-label"}static{this.errorClass="has-error"}static{this.validationErrorClass="has-validation-error"}static{this.passwordDummy="********"}static initialize(t){u=t,u.querySelectorAll("."+r.errorClass).forEach(e=>e.classList.remove(r.errorClass)),r.initializeInputFields(),new x(t),new k("change",(e,s)=>{r.validateField(s),r.markFieldAsChanged(s)}).delegateTo(u,r.rulesSelector),r.registerSubmitCallback(),r.validate()}static initializeInputFields(){u.querySelectorAll(r.inputSelector).forEach(t=>{const s=JSON.parse(t.dataset.formengineInputParams).field,a=u.querySelector(d`[name="${s}"]`);"formengineInputInitialized"in t.dataset||(a.dataset.config=t.dataset.formengineInputParams,r.initializeInputField(s))})}static initializeInputField(t){const e=u.querySelector(d`[name="${t}"]`),s=u.querySelector(d`[data-formengine-input-name="${t}"]`);if(e.dataset.config!==void 0){const a=JSON.parse(e.dataset.config),l=r.formatByEvals(a,e.value);l.length&&(s.value=l)}new k("change",()=>{r.updateInputField(s.dataset.formengineInputName)}).bindTo(s),s.dataset.formengineInputInitialized="true"}static registerCustomEvaluation(t,e){b.has(t)||b.set(t,e)}static formatByEvals(t,e){if(t.evalList!==void 0){const s=h.trimExplode(",",t.evalList);for(const a of s)e=r.formatValue(a,e)}return e}static formatValue(t,e){switch(t){case"date":case"datetime":case"time":case"timesec":if(e==="")return"";const s=p.fromISO(String(e));if(!s.isValid)throw new Error("Invalid ISO8601 DateTime string: "+e);return s.toISO({suppressMilliseconds:!0,includeOffset:!1});case"password":return e?r.passwordDummy:"";default:return e.toString()}}static updateInputField(t){const e=u.querySelector(d`[name="${t}"]`),s=u.querySelector(d`[data-formengine-input-name="${t}"]`);if(e.dataset.config!==void 0){const a=JSON.parse(e.dataset.config),l=r.processByEvals(a,s.value),o=r.formatByEvals(a,l);e.value!==l&&(e.disabled&&e.dataset.enableOnModification&&(e.disabled=!1),e.value=l,e.dispatchEvent(new Event("change"))),s.value!==o&&(s.value=o)}}static validateField(t){if(t.dataset.formengineValidationRules===void 0)return;let e=t.value||"";const s=JSON.parse(t.dataset.formengineValidationRules);let a=!1,l=0,o,n,c;Array.isArray(e)||(e=e.trimStart());for(const i of s){if(a)break;switch(i.type){case"required":e===""&&(a=!0,t.classList.add(r.errorClass),t.closest(r.markerSelector)?.querySelector(r.labelSelector)?.classList.add(r.errorClass));break;case"range":if(e!==""){if((i.minItems||i.maxItems)&&(o=u.querySelector(d`[name="${t.dataset.relatedfieldname}"]`),o!==null?l=h.trimExplode(",",o.value).length:l=parseInt(t.value,10),i.minItems!==void 0&&(n=i.minItems*1,!isNaN(n)&&l<n&&(a=!0)),i.maxItems!==void 0&&(c=i.maxItems*1,!isNaN(c)&&l>c&&(a=!0))),i.lower!==void 0)if(t.dataset.inputType==="datetimepicker"){const f=p.fromISO(e,{zone:"utc"}),I=p.fromISO(i.lower,{zone:"utc"});(!f.isValid||f<I.minus(I.second*1e3))&&(a=!0)}else{const f=i.lower*1;!isNaN(f)&&parseInt(e,10)<f&&(a=!0)}if(i.upper!==void 0)if(t.dataset.inputType==="datetimepicker"){const f=p.fromISO(e,{zone:"utc"}),I=p.fromISO(i.upper,{zone:"utc"});(!f.isValid||f>I.plus((59-I.second)*1e3))&&(a=!0)}else{const f=i.upper*1;!isNaN(f)&&parseInt(e,10)>f&&(a=!0)}}break;case"select":case"category":(i.minItems||i.maxItems)&&(o=u.querySelector(d`[name="${t.dataset.relatedfieldname}"]`),o!==null?l=h.trimExplode(",",o.value).length:t instanceof HTMLSelectElement?l=t.querySelectorAll("option:checked").length:l=t.querySelectorAll("input[value]:checked").length,i.minItems!==void 0&&(n=i.minItems*1,!isNaN(n)&&l<n&&(a=!0)),i.maxItems!==void 0&&(c=i.maxItems*1,!isNaN(c)&&l>c&&(a=!0)));break;case"group":case"folder":(i.minItems||i.maxItems)&&(l=h.trimExplode(",",t.value).length,i.minItems!==void 0&&(n=i.minItems*1,!isNaN(n)&&l<n&&(a=!0)),i.maxItems!==void 0&&(c=i.maxItems*1,!isNaN(c)&&l>c&&(a=!0)));break;case"inline":(i.minItems||i.maxItems)&&(l=h.trimExplode(",",t.value).length,i.minItems!==void 0&&(n=i.minItems*1,!isNaN(n)&&l<n&&(a=!0)),i.maxItems!==void 0&&(c=i.maxItems*1,!isNaN(c)&&l>c&&(a=!0)));break;case"min":(t instanceof HTMLInputElement||t instanceof HTMLTextAreaElement)&&t.value.length>0&&t.value.length<t.minLength&&(a=!0);break;case"null":break;default:break}}const m=!a;t.classList.toggle(r.errorClass,!m),t.closest(r.markerSelector)?.querySelector(r.labelSelector)?.classList.toggle(r.errorClass,!m),r.markParentTab(t,m),u.dispatchEvent(new CustomEvent("t3-formengine-postfieldvalidation",{detail:{field:t,isValid:m},cancelable:!1,bubbles:!0}))}static processByEvals(t,e){if(t.evalList!==void 0){const s=h.trimExplode(",",t.evalList);for(const a of s)e=r.processValue(a,e,t)}return e}static processValue(t,e,s){let a="",l="",o=0,n=e;switch(t){case"alpha":case"num":case"alphanum":case"alphanum_x":for(a="",o=0;o<e.length;o++){const c=e.substr(o,1);let m=c==="_"||c==="-",i=c>="a"&&c<="z"||c>="A"&&c<="Z",f=c>="0"&&c<="9";switch(t){case"alphanum":m=!1;break;case"alpha":f=!1,m=!1;break;case"num":i=!1,m=!1;break;default:break}(i||f||m)&&(a+=c)}a!==e&&(n=a);break;case"is_in":if(s.is_in){l=""+e,s.is_in=s.is_in.replace(/[-[\]{}()*+?.,\\^$|#\s]/g,"\\$&");const c=new RegExp("[^"+s.is_in+"]+","g");a=l.replace(c,"")}else a=l;n=a;break;case"nospace":n=(""+e).replace(/ /g,"");break;case"md5":e!==""&&(n=g.hash(e));break;case"upper":n=e.toUpperCase();break;case"lower":n=e.toLowerCase();break;case"integer":e!==""&&(n=r.parseInt(e).toString());break;case"decimal":e!==""&&(n=r.parseDouble(e));break;case"trim":n=String(e).trim();break;case"time":case"timesec":e!==""&&(n=p.fromISO(e).set({year:1970,month:1,day:1}).toISO({suppressMilliseconds:!0,includeOffset:!1}));break;case"year":if(e!==""){let c=parseInt(e,10);isNaN(c)&&(c=new Date().getUTCFullYear()),n=c.toString(10)}break;case"null":break;case"password":break;default:b.has(t)?n=b.get(t).call(null,e):typeof TBE_EDITOR=="object"&&TBE_EDITOR.customEvalFunctions!==void 0&&typeof TBE_EDITOR.customEvalFunctions[t]=="function"&&(n=TBE_EDITOR.customEvalFunctions[t](e))}return n}static validate(t){(typeof t>"u"||t instanceof Document)&&u.querySelectorAll(r.markerSelector+", .t3js-tabmenu-item").forEach(s=>{s.classList.remove(r.validationErrorClass)});const e=t||document;for(const s of e.querySelectorAll(r.rulesSelector))s.closest(".t3js-flex-section-deleted, .t3js-inline-record-deleted, .t3js-file-reference-deleted")===null&&r.validateField(s)}static markFieldAsChanged(t){t.classList.add("has-change");const e=t.closest(".t3js-formengine-palette-field")?.querySelector(".t3js-formengine-label");e!==null&&e.classList.add("has-change")}static parseInt(t){const e=""+t;if(!t)return 0;const s=parseInt(e,10);return isNaN(s)?0:s}static parseDouble(t,e=2){let s=""+t;s=s.replace(/[^0-9,.-]/g,"");const a=s.startsWith("-");s=s.replace(/-/g,""),s=s.replace(/,/g,"."),s.indexOf(".")===-1&&(s+=".0");const l=s.split("."),o=l.pop();let n=+(l.join("")+"."+o);return a&&(n*=-1),s=n.toFixed(e),s}static markParentTab(t,e){w.parents(t,".tab-pane").forEach(a=>{e&&(e=a.querySelector(".has-error")===null);const l=a.id;u.querySelector('[data-bs-target="#'+l+'"]').closest(".t3js-tabmenu-item").classList.toggle(r.validationErrorClass,!e)})}static suspend(){S=!0}static resume(){S=!1}static isValid(){return document.querySelector("."+r.errorClass)===null}static showErrorModal(){const t=y.confirm(TYPO3.lang.alert||"Alert",TYPO3.lang["FormEngine.fieldsMissing"],v.error,[{text:TYPO3.lang["button.ok"]||"OK",active:!0,btnClass:"btn-default",name:"ok"}]);t.addEventListener("button.clicked",()=>t.hideModal())}static registerSubmitCallback(){new N(u).addPreSubmitCallback(()=>S||r.isValid()?!0:(r.showErrorModal(),!1))}}export{r as default};
