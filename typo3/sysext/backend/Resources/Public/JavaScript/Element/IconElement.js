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
var __decorate=this&&this.__decorate||function(e,t,i,n){var o,r=arguments.length,s=r<3?t:null===n?n=Object.getOwnPropertyDescriptor(t,i):n;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(e,t,i,n);else for(var a=e.length-1;a>=0;a--)(o=e[a])&&(s=(r<3?o(s):r>3?o(t,i,s):o(t,i))||s);return r>3&&s&&Object.defineProperty(t,i,s),s};define(["require","exports","lit-element","lit-html/directives/unsafe-html","lit-html/directives/until","../Enum/IconTypes","../Icons","TYPO3/CMS/Backend/Element/SpinnerElement"],(function(e,t,i,n,o,r,s){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.IconElement=void 0;let a=class extends i.LitElement{constructor(){super(...arguments),this.size=r.Sizes.default,this.state=r.States.default,this.overlay=null,this.markup=r.MarkupIdentifiers.inline,this.raw=null}static get styles(){const e=(e,t)=>i.css`
      :host([size=${e}]),
      :host([raw]) .icon-size-${e} {
        font-size: ${t}px;
      }
    `;return[i.css`
        :host {
          display: flex;
          font-size: 1em;
          width: 1em;
          height: 1em;
          line-height: 0;
        }

        typo3-backend-spinner {
          font-size: 1em;
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

      `,e(i.unsafeCSS(r.Sizes.small),16),e(i.unsafeCSS(r.Sizes.default),32),e(i.unsafeCSS(r.Sizes.large),48),e(i.unsafeCSS(r.Sizes.mega),64)]}render(){if(this.raw)return i.html`${n.unsafeHTML(this.raw)}`;if(!this.identifier)return i.html``;const e=s.getIcon(this.identifier,this.size,this.overlay,this.state,this.markup).then(e=>i.html`
          ${n.unsafeHTML(e)}
        `);return i.html`${o.until(e,i.html`<typo3-backend-spinner></typo3-backend-spinner>`)}`}};__decorate([i.property({type:String})],a.prototype,"identifier",void 0),__decorate([i.property({type:String,reflect:!0})],a.prototype,"size",void 0),__decorate([i.property({type:String})],a.prototype,"state",void 0),__decorate([i.property({type:String})],a.prototype,"overlay",void 0),__decorate([i.property({type:String})],a.prototype,"markup",void 0),__decorate([i.property({type:String})],a.prototype,"raw",void 0),a=__decorate([i.customElement("typo3-backend-icon")],a),t.IconElement=a}));