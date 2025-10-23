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
import{property as c,customElement as p}from"lit/decorators.js";import{PseudoButtonLitElement as y}from"@typo3/backend/element/pseudo-button.js";import{SeverityEnum as h}from"@typo3/backend/enum/severity.js";import m from"@typo3/backend/modal.js";import{lll as u}from"@typo3/core/lit-helper.js";var s=function(n,t,e,l){var i=arguments.length,o=i<3?t:l===null?l=Object.getOwnPropertyDescriptor(t,e):l,r;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")o=Reflect.decorate(n,t,e,l);else for(var f=n.length-1;f>=0;f--)(r=n[f])&&(o=(i<3?r(o):i>3?r(t,e,o):r(t,e))||o);return i>3&&o&&Object.defineProperty(t,e,o),o},d;(function(n){n.formatSelector=".t3js-record-download-format-selector",n.formatOptions=".t3js-record-download-format-option"})(d||(d={}));let a=class extends y{buttonActivated(){this.showDownloadConfigurationModal()}showDownloadConfigurationModal(){if(!this.url)return;const t=m.advanced({content:this.url,title:this.subject||"Download records",severity:h.notice,size:m.sizes.small,type:m.types.ajax,buttons:[{text:this.close||u("button.close")||"Close",active:!0,btnClass:"btn-default",name:"cancel",trigger:()=>t.hideModal()},{text:this.ok||u("button.ok")||"Download",btnClass:"btn-primary",name:"download",trigger:()=>{t.querySelector("form")?.submit(),t.hideModal()}}],ajaxCallback:()=>{const e=t.querySelector(d.formatSelector),l=t.querySelectorAll(d.formatOptions);e===null||!l.length||e.addEventListener("change",i=>{const o=i.target.value;l.forEach(r=>{r.dataset.formatname!==o?r.classList.add("hide"):r.classList.remove("hide")})})}})}};s([c({type:String})],a.prototype,"url",void 0),s([c({type:String})],a.prototype,"subject",void 0),s([c({type:String})],a.prototype,"ok",void 0),s([c({type:String})],a.prototype,"close",void 0),a=s([p("typo3-recordlist-record-download-button")],a);export{a as RecordDownloadButton};
