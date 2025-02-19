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
import{property as m,customElement as h}from"lit/decorators.js";import{LitElement as p,html as d,nothing as f}from"lit";var c=function(l,t,e,n){var i=arguments.length,a=i<3?t:n===null?n=Object.getOwnPropertyDescriptor(t,e):n,s;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")a=Reflect.decorate(l,t,e,n);else for(var r=l.length-1;r>=0;r--)(s=l[r])&&(a=(i<3?s(a):i>3?s(t,e,a):s(t,e))||a);return i>3&&a&&Object.defineProperty(t,e,a),a};let o=class extends p{constructor(){super(...arguments),this.data=null,this.TYPO3lang=null}createRenderRoot(){return this}render(){return d`<form>${this.data.sendMailTo!==void 0&&this.data.sendMailTo.length>0?d`<label class=form-label>${this.TYPO3lang["window.sendToNextStageWindow.itemsWillBeSentTo"]}</label> ${this.renderRecipientCheckboxes()}`:f} ${this.data.additional!==void 0?d`<div class=form-group><label for=additional class=form-label>${this.TYPO3lang["window.sendToNextStageWindow.additionalRecipients"]}</label> <textarea class=form-control name=additional id=additional>${this.data.additional.value}</textarea><div class=form-text>${this.TYPO3lang["window.sendToNextStageWindow.additionalRecipients.hint"]}</div></div>`:f}<div class=form-group><label for=comments class=form-label>${this.TYPO3lang["window.sendToNextStageWindow.comments"]}</label> <textarea class=form-control name=comments id=comments>${this.data.comments.value}</textarea></div></form>`}renderRecipientCheckboxes(){const t=[];return this.data.sendMailTo?.forEach(e=>{t.push(d`<div class=form-check><input type=checkbox name=recipients class="form-check-input t3js-workspace-recipient" id=${e.name} value=${e.value} ?checked=${e.checked} ?disabled=${e.disabled}> <label class=form-check-label for=${e.name}>${e.label}</label></div>`)}),t}};c([m({type:Object})],o.prototype,"data",void 0),c([m({type:Object})],o.prototype,"TYPO3lang",void 0),o=c([h("typo3-workspaces-send-to-stage-form")],o);export{o as SendToStageFormElement};
