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
var __decorate=this&&this.__decorate||function(e,t,i,r){var o,n=arguments.length,s=n<3?t:null===r?r=Object.getOwnPropertyDescriptor(t,i):r;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(e,t,i,r);else for(var a=e.length-1;a>=0;a--)(o=e[a])&&(s=(n<3?o(s):n>3?o(t,i,s):o(t,i))||s);return n>3&&s&&Object.defineProperty(t,i,s),s};define(["require","exports","lit","lit/decorators","../Enum/IconTypes"],(function(e,t,i,r,o){"use strict";var n;Object.defineProperty(t,"__esModule",{value:!0}),t.SpinnerElement=void 0,function(e){e.light="light",e.dark="dark"}(n||(n={}));let s=class extends i.LitElement{constructor(){super(...arguments),this.size=o.Sizes.default,this.variant=n.dark}render(){return i.html`
      <div class="icon">
        <svg viewBox="0 0 16 16">
          <path d="M8 15c-3.86 0-7-3.141-7-7 0-3.86 3.14-7 7-7 3.859 0 7 3.14 7 7 0 3.859-3.141 7-7 7zM8 3C5.243 3 3 5.243 3 8s2.243 5 5 5 5-2.243 5-5 -2.243-5-5-5z" opacity=".3"/>
          <path d="M14 9a1 1 0 0 1-1-1c0-2.757-2.243-5-5-5a1 1 0 0 1 0-2c3.859 0 7 3.14 7 7a1 1 0 0 1-1 1z" transform-origin="center center">
            <animateTransform attributeName="transform" attributeType="XML" type="rotate" from="0" to="360" begin="0s" dur="1s" repeatCount="indefinite" />
          </path>
        </svg>
      </div>
    `}};s.styles=i.css`
    :host {
      display: flex;
      width: 1em;
      height: 1em;
      line-height: 0;
    }
    .icon {
      position: relative;
      display: block;
      height: 1em;
      width: 1em;
      line-height: 1;
    }
    svg {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      display: block;
      height: 1em;
      width: 1em;
      transform: translate3d(0, 0, 0);
      fill: currentColor;
    }
    :host([variant=dark]) svg {
      fill: #212121;
    }
    :host([variant=light]) svg {
      fill: #fff;
    }
    :host([size=small]) {
      font-size: 16px;
    }
    :host([size=default]) {
      font-size: 32px;
    }
    :host([size=large]) {
      font-size: 48px;
    }
    :host([size=mega]) {
      font-size: 64px;
    }
  `,__decorate([(0,r.property)({type:String})],s.prototype,"size",void 0),__decorate([(0,r.property)({type:String})],s.prototype,"variant",void 0),s=__decorate([(0,r.customElement)("typo3-backend-spinner")],s),t.SpinnerElement=s}));