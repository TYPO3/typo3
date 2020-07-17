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
var __decorate=this&&this.__decorate||function(e,t,r,i){var n,s=arguments.length,o=s<3?t:null===i?i=Object.getOwnPropertyDescriptor(t,r):i;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)o=Reflect.decorate(e,t,r,i);else for(var d=e.length-1;d>=0;d--)(n=e[d])&&(o=(s<3?n(o):s>3?n(t,r,o):n(t,r))||o);return s>3&&o&&Object.defineProperty(t,r,o),o};define(["require","exports","lit-element"],(function(e,t,r){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.SpinnerElement=void 0;let i=class extends r.LitElement{constructor(){super(...arguments),this.size="small"}static get styles(){return r.css`
      :host {
        display: block;
      }
      .spinner {
        display: block;
        margin: 2px;
        border-style: solid;
        border-color: #212121 #bababa #bababa;
        border-radius: 50%;
        animation: spin 1s linear infinite;
      }
      .spinner.small {
        border-width: 2px;
        width: 10px;
        height: 10px;
      }
      .spinner.medium {
        border-width: 3px;
        width: 14px;
        height: 14px;
      }
      .spinner.large {
        border-width: 4px;
        width: 20px;
        height: 20px;
      }
      @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }
    `}render(){return r.html`<div class="spinner ${this.size}"></div>`}};__decorate([r.property({type:String})],i.prototype,"size",void 0),i=__decorate([r.customElement("typo3-backend-spinner")],i),t.SpinnerElement=i}));