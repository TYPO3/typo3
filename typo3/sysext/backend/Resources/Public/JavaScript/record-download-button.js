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
import{property as d,customElement as p}from"lit/decorators.js";import{PseudoButtonLitElement as h}from"@typo3/backend/element/pseudo-button.js";import{SeverityEnum as b}from"@typo3/backend/enum/severity.js";import m from"@typo3/backend/modal.js";import f from"~labels/core.mod_web_list";var c=function(e,t,r,n){var l=arguments.length,o=l<3?t:n===null?n=Object.getOwnPropertyDescriptor(t,r):n,i;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")o=Reflect.decorate(e,t,r,n);else for(var s=e.length-1;s>=0;s--)(i=e[s])&&(o=(l<3?i(o):l>3?i(t,r,o):i(t,r))||o);return l>3&&o&&Object.defineProperty(t,r,o),o},u;(function(e){e.formatSelector=".t3js-record-download-format-selector",e.formatOptions=".t3js-record-download-format-option"})(u||(u={}));let a=class extends h{constructor(){super(...arguments),this.url="",this.subject="",this.ok="",this.close=""}buttonActivated(){this.showDownloadConfigurationModal()}showDownloadConfigurationModal(){if(!this.url)return;const t=m.advanced({content:this.url,title:this.subject||"Download records",severity:b.notice,size:m.sizes.small,type:m.types.ajax,buttons:[{text:this.close||f.get("button.close"),active:!0,btnClass:"btn-default",name:"cancel",trigger:()=>t.hideModal()},{text:this.ok||f.get("button.ok"),btnClass:"btn-primary",name:"download",form:"downloadSettingsForm"}],ajaxCallback:()=>{const r=t.querySelector("form"),n=t.querySelector(u.formatSelector),l=t.querySelectorAll(u.formatOptions);r?.addEventListener("submit",()=>t.hideModal()),!(n===null||!l.length)&&n.addEventListener("change",o=>{const i=o.target.value;l.forEach(s=>{s.dataset.formatname!==i?s.classList.add("hide"):s.classList.remove("hide")})})}})}};c([d({type:String})],a.prototype,"url",void 0),c([d({type:String})],a.prototype,"subject",void 0),c([d({type:String})],a.prototype,"ok",void 0),c([d({type:String})],a.prototype,"close",void 0),a=c([p("typo3-recordlist-record-download-button")],a);export{a as RecordDownloadButton};
