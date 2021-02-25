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
var __decorate=this&&this.__decorate||function(e,t,r,n){var i,s=arguments.length,o=s<3?t:null===n?n=Object.getOwnPropertyDescriptor(t,r):n;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)o=Reflect.decorate(e,t,r,n);else for(var a=e.length-1;a>=0;a--)(i=e[a])&&(o=(s<3?i(o):s>3?i(t,r,o):i(t,r))||o);return s>3&&o&&Object.defineProperty(t,r,o),o};define(["require","exports","lit","lit/decorators","../Enum/IconTypes"],(function(e,t,r,n,i){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.SpinnerElement=void 0;let s=class extends r.LitElement{constructor(){super(...arguments),this.size=i.Sizes.default}render(){return r.html`<div class="spinner"></div>`}};s.styles=r.css`
    :host {
      font-size: 32px;
      width: 1em;
      height: 1em;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .spinner {
      display: block;
      border-style: solid;
      border-color: #212121 #bababa #bababa;
      border-radius: 50%;
      width: 0.625em;
      height: 0.625em;
      border-width: 0.0625em;
      animation: spin 1s linear infinite;
    }
    :host([size=small]) .spinner {
      font-size: 16px;
    }
    :host([size=large]) .spinner {
      font-size: 48px;
    }
    :host([size=mega]) .spinner {
      font-size: 64px;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  `,__decorate([n.property({type:String})],s.prototype,"size",void 0),s=__decorate([n.customElement("typo3-backend-spinner")],s),t.SpinnerElement=s}));