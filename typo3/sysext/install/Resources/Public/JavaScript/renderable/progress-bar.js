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
var __decorate=function(r,e,t,s){var o,a=arguments.length,i=a<3?e:null===s?s=Object.getOwnPropertyDescriptor(e,t):s;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)i=Reflect.decorate(r,e,t,s);else for(var p=r.length-1;p>=0;p--)(o=r[p])&&(i=(a<3?o(i):a>3?o(e,t,i):o(e,t))||i);return a>3&&i&&Object.defineProperty(e,t,i),i};import Severity from"@typo3/install/renderable/severity.js";import{customElement,property}from"lit/decorators.js";import{html,LitElement}from"lit";let ProgressBar=class extends LitElement{constructor(){super(...arguments),this.label="Loading...",this.progress="100"}createRenderRoot(){return this}render(){return html`
      <div class="progress progress-bar-${Severity.getCssClass(Severity.loading)}">
        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="${this.progress}" aria-valuemin="0" aria-valuemax="100" style="width: ${this.progress}%">
         <span>${this.label}</span>
        </div>
      </div>
    `}};__decorate([property({type:String})],ProgressBar.prototype,"label",void 0),__decorate([property({type:String})],ProgressBar.prototype,"progress",void 0),ProgressBar=__decorate([customElement("typo3-install-progress-bar")],ProgressBar);export{ProgressBar};