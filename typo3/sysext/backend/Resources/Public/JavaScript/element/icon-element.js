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
var __decorate=function(e,i,t,o){var n,r=arguments.length,s=r<3?i:null===o?o=Object.getOwnPropertyDescriptor(i,t):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)s=Reflect.decorate(e,i,t,o);else for(var c=e.length-1;c>=0;c--)(n=e[c])&&(s=(r<3?n(s):r>3?n(i,t,s):n(i,t))||s);return r>3&&s&&Object.defineProperty(i,t,s),s};import{html,css,unsafeCSS,LitElement}from"lit";import{customElement,property}from"lit/decorators.js";import{unsafeHTML}from"lit/directives/unsafe-html.js";import{until}from"lit/directives/until.js";import{Sizes,States,MarkupIdentifiers}from"@typo3/backend/enum/icon-types.js";import Icons from"@typo3/backend/icons.js";import"@typo3/backend/element/spinner-element.js";const iconSize=e=>css`
  :host([size=${e}]) .icon-size-${e},
  :host([raw]) .icon-size-${e} {
    --icon-size: var(--icon-size-${e})
  }
  :host([size=${e}]) .icon-size-${e} .icon-unify,
  :host([raw]) .icon-size-${e} .icon-unify {
    line-height: var(--icon-size);
    font-size: calc(var(--icon-size) * var(--icon-unify-modifier))
  }
  :host([size=${e}]) .icon-size-${e} .icon-overlay .icon-unify,
  :host([raw]) .icon-size-${e} .icon-overlay .icon-unify {
    line-height: calc(var(--icon-size) / 1.6);
    font-size: calc((var(--icon-size) / 1.6) * var(--icon-unify-modifier))
  }
`;let IconElement=class extends LitElement{constructor(){super(...arguments),this.size=null,this.state=States.default,this.overlay=null,this.markup=MarkupIdentifiers.inline,this.raw=null}render(){if(this.raw)return html`${unsafeHTML(this.raw)}`;if(!this.identifier)return html``;const e=Icons.getIcon(this.identifier,this.size,this.overlay,this.state,this.markup).then((e=>html`
          ${unsafeHTML(e)}
        `));return html`${until(e,html`<typo3-backend-spinner></typo3-backend-spinner>`)}`}};IconElement.styles=[css`
      :host {
        --icon-color-primary: currentColor;
        --icon-size-small: 16px;
        --icon-size-medium: 32px;
        --icon-size-large: 48px;
        --icon-size-mega: 64px;
        --icon-unify-modifier: 0.86;
        --icon-opacity-disabled: 0.5
      }

      .icon {
        position: relative;
        display: inline-flex;
        overflow: hidden;
        white-space: nowrap;
        height: var(--icon-size, 1em);
        width: var(--icon-size, 1em);
        line-height: var(--icon-size, 1em);
        flex-shrink: 0;
      }

      .icon img, .icon svg {
        display: block;
        height: 100%;
        width: 100%
      }

      .icon * {
        display: block;
        line-height: inherit
      }

      .icon-markup {
        position: absolute;
        display: block;
        text-align: center;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0
      }

      .icon-overlay {
        position: absolute;
        bottom: 0;
        right: 0;
        height: 68.75%;
        width: 68.75%;
        text-align: center
      }

      .icon-color {
        fill: var(--icon-color-primary)
      }

      .icon-spin .icon-markup {
        -webkit-animation: icon-spin 2s infinite linear;
        animation: icon-spin 2s infinite linear
      }

      @keyframes icon-spin {
        0% {
          transform: rotate(0)
        }
        100% {
          transform: rotate(360deg)
        }
      }

      .icon-state-disabled .icon-markup {
        opacity: var(--icon-opacity-disabled)
      }
    `,iconSize(unsafeCSS(Sizes.small)),iconSize(unsafeCSS(Sizes.default)),iconSize(unsafeCSS(Sizes.large)),iconSize(unsafeCSS(Sizes.mega))],__decorate([property({type:String})],IconElement.prototype,"identifier",void 0),__decorate([property({type:String,reflect:!0})],IconElement.prototype,"size",void 0),__decorate([property({type:String})],IconElement.prototype,"state",void 0),__decorate([property({type:String})],IconElement.prototype,"overlay",void 0),__decorate([property({type:String})],IconElement.prototype,"markup",void 0),__decorate([property({type:String})],IconElement.prototype,"raw",void 0),IconElement=__decorate([customElement("typo3-backend-icon")],IconElement);export{IconElement};