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
var __decorate=this&&this.__decorate||function(e,t,i,o){var n,r=arguments.length,s=r<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,i):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(e,t,i,o);else for(var a=e.length-1;a>=0;a--)(n=e[a])&&(s=(r<3?n(s):r>3?n(t,i,s):n(t,i))||s);return r>3&&s&&Object.defineProperty(t,i,s),s};define(["require","exports","lit","lit/decorators","lit/directives/unsafe-html","lit/directives/until","../Enum/IconTypes","../Icons","TYPO3/CMS/Backend/Element/SpinnerElement"],(function(e,t,i,o,n,r,s,a){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.IconElement=void 0;const l=(e,t)=>i.css`
  :host([size=${e}]),
  :host([raw]) .icon-size-${e} {
    font-size: ${t}px;
  }
`;let c=class extends i.LitElement{constructor(){super(...arguments),this.size=s.Sizes.default,this.state=s.States.default,this.overlay=null,this.markup=s.MarkupIdentifiers.inline,this.raw=null}render(){if(this.raw)return i.html`${(0,n.unsafeHTML)(this.raw)}`;if(!this.identifier)return i.html``;const e=a.getIcon(this.identifier,this.size,this.overlay,this.state,this.markup).then(e=>i.html`
          ${(0,n.unsafeHTML)(e)}
        `);return i.html`${(0,r.until)(e,i.html`<typo3-backend-spinner></typo3-backend-spinner>`)}`}};c.styles=[i.css`
      :host {
        display: flex;
        width: 1em;
        height: 1em;
        line-height: 0;
      }

      .icon {
        position: relative;
        display: block;
        overflow: hidden;
        white-space: nowrap;
        height: 1em;
        width: 1em;
        line-height: 1;
      }

      .icon svg,
      .icon img {
        display: block;
        height: 1em;
        width: 1em;
        transform: translate3d(0, 0, 0);
      }

      .icon * {
        display: block;
        line-height: inherit;
      }

      .icon-markup {
        position: absolute;
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
      }

      .icon-overlay {
        position: absolute;
        bottom: 0;
        right: 0;
        font-size: 0.6875em;
        text-align: center;
      }

      .icon-color {
        fill: currentColor;
      }

      .icon-state-disabled .icon-markup {
        opacity: .5;
      }

      .icon-unify {
        font-size: ${.86}em;
        line-height: ${1/.86};
      }

      .icon-spin .icon-markup {
        animation: icon-spin 2s infinite linear;
      }

      @keyframes icon-spin {
        0% {
          transform: rotate(0deg);
        }

        100% {
          transform: rotate(360deg);
        }
      }
    `,l((0,i.unsafeCSS)(s.Sizes.small),16),l((0,i.unsafeCSS)(s.Sizes.default),32),l((0,i.unsafeCSS)(s.Sizes.large),48),l((0,i.unsafeCSS)(s.Sizes.mega),64)],__decorate([(0,o.property)({type:String})],c.prototype,"identifier",void 0),__decorate([(0,o.property)({type:String})],c.prototype,"size",void 0),__decorate([(0,o.property)({type:String})],c.prototype,"state",void 0),__decorate([(0,o.property)({type:String})],c.prototype,"overlay",void 0),__decorate([(0,o.property)({type:String})],c.prototype,"markup",void 0),__decorate([(0,o.property)({type:String})],c.prototype,"raw",void 0),c=__decorate([(0,o.customElement)("typo3-backend-icon")],c),t.IconElement=c}));