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
var __decorate=function(e,t,i,o){var n,r=arguments.length,s=r<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,i):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(e,t,i,o);else for(var c=e.length-1;c>=0;c--)(n=e[c])&&(s=(r<3?n(s):r>3?n(t,i,s):n(t,i))||s);return r>3&&s&&Object.defineProperty(t,i,s),s};import{html,css,unsafeCSS,LitElement}from"lit";import{customElement,property}from"lit/decorators.js";import{unsafeHTML}from"lit/directives/unsafe-html.js";import{until}from"lit/directives/until.js";import{Sizes,States,MarkupIdentifiers}from"@typo3/backend/enum/icon-types.js";import Icons from"@typo3/backend/icons.js";import"@typo3/backend/element/spinner-element.js";const iconUnifyModifier=.86,iconSize=(e,t)=>css`
  :host([size=${e}]),
  :host([raw]) .icon-size-${e} {
    font-size: ${t}px;
  }
`;let IconElement=class extends LitElement{constructor(){super(...arguments),this.size=Sizes.default,this.state=States.default,this.overlay=null,this.markup=MarkupIdentifiers.inline,this.raw=null}render(){if(this.raw)return html`${unsafeHTML(this.raw)}`;if(!this.identifier)return html``;const e=Icons.getIcon(this.identifier,this.size,this.overlay,this.state,this.markup).then(e=>html`
          ${unsafeHTML(e)}
        `);return html`${until(e,html`<typo3-backend-spinner></typo3-backend-spinner>`)}`}};IconElement.styles=[css`
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
    `,iconSize(unsafeCSS(Sizes.small),16),iconSize(unsafeCSS(Sizes.default),32),iconSize(unsafeCSS(Sizes.large),48),iconSize(unsafeCSS(Sizes.mega),64)],__decorate([property({type:String})],IconElement.prototype,"identifier",void 0),__decorate([property({type:String})],IconElement.prototype,"size",void 0),__decorate([property({type:String})],IconElement.prototype,"state",void 0),__decorate([property({type:String})],IconElement.prototype,"overlay",void 0),__decorate([property({type:String})],IconElement.prototype,"markup",void 0),__decorate([property({type:String})],IconElement.prototype,"raw",void 0),IconElement=__decorate([customElement("typo3-backend-icon")],IconElement);export{IconElement};