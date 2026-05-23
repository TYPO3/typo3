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
import{property as c,customElement as p}from"lit/decorators.js";import{PseudoButtonLitElement as y}from"@typo3/backend/element/pseudo-button.js";import{SeverityEnum as b}from"@typo3/backend/enum/severity.js";import f from"@typo3/backend/modal.js";import u from"~labels/core.mod_web_list";var d=function(e,t,r,n){var l=arguments.length,o=l<3?t:n===null?n=Object.getOwnPropertyDescriptor(t,r):n,s;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")o=Reflect.decorate(e,t,r,n);else for(var a=e.length-1;a>=0;a--)(s=e[a])&&(o=(l<3?s(o):l>3?s(t,r,o):s(t,r))||o);return l>3&&o&&Object.defineProperty(t,r,o),o},m;(function(e){e.formatSelector=".t3js-record-download-format-selector",e.formatOptions=".t3js-record-download-format-option"})(m||(m={}));let i=class extends y{buttonActivated(){this.showDownloadConfigurationModal()}showDownloadConfigurationModal(){if(!this.url)return;const t=f.advanced({content:this.url,title:this.subject||"Download records",severity:b.notice,size:f.sizes.small,type:f.types.ajax,buttons:[{text:this.close||u.get("button.close"),active:!0,btnClass:"btn-default",name:"cancel",trigger:()=>t.hideModal()},{text:this.ok||u.get("button.ok"),btnClass:"btn-primary",name:"download",form:"downloadSettingsForm"}],ajaxCallback:()=>{const r=t.querySelector("form"),n=t.querySelector(m.formatSelector),l=t.querySelectorAll(m.formatOptions);r?.addEventListener("submit",()=>t.hideModal()),!(n===null||!l.length)&&n.addEventListener("change",o=>{const s=o.target.value;l.forEach(a=>{a.dataset.formatname!==s?a.classList.add("hide"):a.classList.remove("hide")})})}})}};d([c({type:String})],i.prototype,"url",void 0),d([c({type:String})],i.prototype,"subject",void 0),d([c({type:String})],i.prototype,"ok",void 0),d([c({type:String})],i.prototype,"close",void 0),i=d([p("typo3-recordlist-record-download-button")],i);export{i as RecordDownloadButton};
