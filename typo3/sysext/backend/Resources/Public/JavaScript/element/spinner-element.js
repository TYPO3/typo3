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
var __decorate=function(e,t,n,r){var o,s=arguments.length,i=s<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,n):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)i=Reflect.decorate(e,t,n,r);else for(var c=e.length-1;c>=0;c--)(o=e[c])&&(i=(s<3?o(i):s>3?o(t,n,i):o(t,n))||i);return s>3&&i&&Object.defineProperty(t,n,i),i};import{html,LitElement}from"lit";import{customElement,property}from"lit/decorators.js";import{Sizes}from"@typo3/backend/enum/icon-types.js";import{IconStyles}from"@typo3/backend/icons.js";let SpinnerElement=class extends LitElement{constructor(){super(...arguments),this.size=Sizes.default}render(){return html`
      <div class="icon-wrapper">
        <span class="icon icon-size-${this.size} icon-state-default icon-spin">
          <span class="icon-markup">
            <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 16 16">
              <g fill="currentColor">
                <path d="M8 15c-3.86 0-7-3.141-7-7 0-3.86 3.14-7 7-7 3.859 0 7 3.14 7 7 0 3.859-3.141 7-7 7zM8 3C5.243 3 3 5.243 3 8s2.243 5 5 5 5-2.243 5-5-2.243-5-5-5z" opacity=".3"/><path d="M14 9a1 1 0 0 1-1-1c0-2.757-2.243-5-5-5a1 1 0 0 1 0-2c3.859 0 7 3.14 7 7a1 1 0 0 1-1 1z"/>
              </g>
            </svg>
          </span>
        </span>
      </div>
    `}};SpinnerElement.styles=IconStyles.getStyles(),__decorate([property({type:String})],SpinnerElement.prototype,"size",void 0),SpinnerElement=__decorate([customElement("typo3-backend-spinner")],SpinnerElement);export{SpinnerElement};