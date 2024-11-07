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
var __decorate=function(e,o,t,r){var n,c=arguments.length,i=c<3?o:null===r?r=Object.getOwnPropertyDescriptor(o,t):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)i=Reflect.decorate(e,o,t,r);else for(var l=e.length-1;l>=0;l--)(n=e[l])&&(i=(c<3?n(i):c>3?n(o,t,i):n(o,t))||i);return c>3&&i&&Object.defineProperty(o,t,i),i};import{customElement,property,query}from"lit/decorators.js";import{css,html,LitElement}from"lit";import Alwan from"alwan";import RegularEvent from"@typo3/core/event/regular-event.js";let Typo3BackendColorPicker=class extends LitElement{constructor(){super(...arguments),this.color="",this.opacity=!1,this.swatches=""}firstUpdated(){const e=this.getInputElement();if(e){if(!e.value&&this.color?e.value=this.color:this.color=e.value,e.disabled||e.readOnly)return;const o=new Alwan(e,{position:"bottom-start",format:"hex",opacity:this.opacity,swatches:this.swatches?this.swatches.split(";"):[],preset:!1,color:this.color});o.on("color",(o=>{this.color=o.hex,e.value=this.color,e.dispatchEvent(new Event("blur"))})),["input","change"].forEach((t=>{new RegularEvent(t,(e=>{const t=e.target;this.color=t.value,o.setColor(this.color)})).bindTo(e)}))}}render(){return html`
      <slot></slot>
      <span style="--color: ${this.color}" class="color-picker-preview"><span class="color-picker-preview-color"></span></span>
    `}getInputElement(){const e=this.slotEl.assignedNodes();for(const o of e)if(o instanceof HTMLInputElement)return o;return console.warn("No input element found in the slot."),null}};Typo3BackendColorPicker.styles=css`
    .color-picker-preview {
      display: block;
      position: absolute;
      width: 1.25rem;
      height: 1.25rem;
      top: 50%;
      inset-inline-start: var(--typo3-input-padding-x);
      z-index: 10;
      transform: translate(0, -50%);
      background: var(--alwan-pattern);
      border-radius: 3px !important;
      pointer-events: none;
    }

    .color-picker-preview-color {
      position: absolute;
      inset: 0;
      border-radius: 2px;
      background-color: var(--color, transparent);
    }
    `,__decorate([property({type:String})],Typo3BackendColorPicker.prototype,"color",void 0),__decorate([property({type:Boolean})],Typo3BackendColorPicker.prototype,"opacity",void 0),__decorate([property({type:String})],Typo3BackendColorPicker.prototype,"swatches",void 0),__decorate([query("slot")],Typo3BackendColorPicker.prototype,"slotEl",void 0),Typo3BackendColorPicker=__decorate([customElement("typo3-backend-color-picker")],Typo3BackendColorPicker);export{Typo3BackendColorPicker};