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
import a from"@typo3/backend/modal.js";import n from"@typo3/core/document-service.js";import s from"@typo3/backend/date-time-picker.js";import"@typo3/backend/input/clearable.js";import{MessageUtility as m}from"@typo3/backend/utility/message-utility.js";class o{constructor(){this.clearableElements=null,this.dateTimePickerElements=null,this.elementBrowserElements=null,n.ready().then(()=>{this.clearableElements=document.querySelectorAll(".t3js-clearable"),this.dateTimePickerElements=document.querySelectorAll(".t3js-datetimepicker"),this.elementBrowserElements=document.querySelectorAll(".t3js-element-browser"),this.initializeClearableElements(),this.initializeDateTimePickerElements(),this.initializeElementBrowserElements(),this.initializeElementBrowserEventListener()})}initializeClearableElements(){this.clearableElements?.forEach(e=>e.clearable())}initializeDateTimePickerElements(){this.dateTimePickerElements?.forEach(e=>s.initialize(e))}initializeElementBrowserElements(){this.elementBrowserElements?.forEach(e=>{const t=document.getElementById(e.dataset.triggerFor??"");t!==null&&(e.dataset.fieldReference=t.name,e.dataset.allowedTypes="pages",e.addEventListener("click",r=>{r.preventDefault();const i=r.currentTarget,l=new URLSearchParams({mode:i.dataset.mode??"",fieldReference:i.dataset.fieldReference??"",allowedTypes:i.dataset.allowedTypes??""});a.advanced({type:a.types.iframe,content:i.dataset.target+"&"+l.toString(),size:a.sizes.large})}))})}initializeElementBrowserEventListener(){window.addEventListener("message",e=>{if(!m.verifyOrigin(e.origin)||e.data.actionName!=="typo3:elementBrowser:elementAdded"||typeof e.data.fieldName!="string"||typeof e.data.value!="string")return;const t=document.querySelector('input[name="'+e.data.fieldName+'"]');t&&(t.value=e.data.value.split("_").pop())})}}var d=new o;export{d as default};
